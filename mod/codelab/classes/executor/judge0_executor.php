<?php
// This file is part of Moodle - http://moodle.org/

/**
 * Ejecutor de código usando la API de Judge0.
 *
 * Judge0 CE es un sistema de ejecución de código open-source:
 * https://github.com/judge0/judge0
 *
 * Se puede auto-alojar con Docker o usar su API pública (requiere clave).
 *
 * @package   mod_codelab
 * @copyright 2026 ProyectoEditorMoodle
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_codelab\executor;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/base_executor.php');

class judge0_executor extends base_executor {

    /** @var string URL base de la API Judge0 */
    private string $api_url;
    /** @var string Clave API (para servicio en la nube) */
    private string $api_key;
    /** @var string Host del header X-RapidAPI-Host */
    private string $api_host;
    /** @var bool Si usar X-Auth-Token (Judge0 auto-alojado) o RapidAPI */
    private bool $self_hosted;

    /** Reintentos máximos esperando resultado */
    private const MAX_POLL_ATTEMPTS = 10;
    /** Tiempo de espera entre reintentos (microsegundos) */
    private const POLL_INTERVAL_US = 500000; // 0.5 seg

    public function __construct(int $time_limit = 5, int $memory_limit = 128) {
        parent::__construct($time_limit, $memory_limit);

        $this->api_url     = get_config('mod_codelab', 'judge0_api_url')
                             ?: 'https://judge0-ce.p.rapidapi.com';
        $this->api_key     = get_config('mod_codelab', 'judge0_api_key') ?: '';
        $this->api_host    = get_config('mod_codelab', 'judge0_api_host')
                             ?: 'judge0-ce.p.rapidapi.com';
        $this->self_hosted = (bool)get_config('mod_codelab', 'judge0_self_hosted');
    }

    /**
     * Ejecuta el código en Judge0 y devuelve el resultado.
     */
    public function execute(string $code, string $language, string $stdin = ''): execution_result {
        $language_id = $this->get_language_id($language);

        $payload = [
            'source_code'     => base64_encode($code),
            'language_id'     => $language_id,
            'stdin'           => base64_encode($stdin),
            'cpu_time_limit'  => $this->time_limit,
            'memory_limit'    => $this->memory_limit * 1024, // Judge0 usa KB
            'encode_output'   => false,
        ];

        // Enviar la tarea de ejecución.
        $token = $this->submit_code($payload);
        if (!$token) {
            return new execution_result(
                'internal_error',
                message: get_string('executor_connection_error', 'mod_codelab')
            );
        }

        // Esperar el resultado.
        return $this->poll_result($token);
    }

    /**
     * Envía el código a Judge0 y retorna el token de la tarea.
     */
    private function submit_code(array $payload): ?string {
        $url = rtrim($this->api_url, '/') . '/submissions?base64_encoded=true&wait=false';

        $response = $this->http_post($url, json_encode($payload));
        if (!$response) {
            return null;
        }

        $data = json_decode($response, true);
        return $data['token'] ?? null;
    }

    /**
     * Consulta el resultado hasta obtener respuesta final.
     */
    private function poll_result(string $token): execution_result {
        $url = rtrim($this->api_url, '/') . '/submissions/' . $token . '?base64_encoded=true';

        for ($i = 0; $i < self::MAX_POLL_ATTEMPTS; $i++) {
            usleep(self::POLL_INTERVAL_US);

            $response = $this->http_get($url);
            if (!$response) {
                continue;
            }

            $data = json_decode($response, true);
            $status_id = $data['status']['id'] ?? 0;

            // Status IDs de Judge0:
            // 1 = In Queue, 2 = Processing, 3 = Accepted, 4 = Wrong Answer
            // 5 = TLE, 6 = Compilation Error, 7-12 = Runtime Errors
            if ($status_id <= 2) {
                continue; // Aún procesando.
            }

            return $this->parse_judge0_response($data);
        }

        return new execution_result('internal_error', message: get_string('executor_timeout', 'mod_codelab'));
    }

    /**
     * Parsea la respuesta de Judge0 a un execution_result.
     */
    private function parse_judge0_response(array $data): execution_result {
        $status_id = $data['status']['id'] ?? 0;
        $stdout    = $this->decode_base64($data['stdout'] ?? '');
        $stderr    = $this->decode_base64($data['stderr'] ?? '');
        $compile   = $this->decode_base64($data['compile_output'] ?? '');
        $time      = isset($data['time']) ? (float)$data['time'] : null;
        $memory    = isset($data['memory']) ? (int)$data['memory'] : null;

        $status_map = [
            3  => 'accepted',
            4  => 'wrong_answer',
            5  => 'time_limit_exceeded',
            6  => 'compile_error',
            7  => 'runtime_error',
            8  => 'runtime_error',
            9  => 'runtime_error',
            10 => 'runtime_error',
            11 => 'runtime_error',
            12 => 'runtime_error',
        ];

        $status = $status_map[$status_id] ?? 'internal_error';
        $message = $data['status']['description'] ?? '';

        return new execution_result($status, $stdout, $stderr, $compile ?: null, $time, $memory, $message);
    }

    /**
     * Realiza una petición HTTP POST.
     */
    private function http_post(string $url, string $body): ?string {
        $headers = $this->get_headers();
        $headers[] = 'Content-Type: application/json';

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $response = curl_exec($ch);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($error) {
            debugging('CodeLab Judge0 POST error: ' . $error, DEBUG_DEVELOPER);
            return null;
        }
        return $response ?: null;
    }

    /**
     * Realiza una petición HTTP GET.
     */
    private function http_get(string $url): ?string {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => $this->get_headers(),
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $response = curl_exec($ch);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($error) {
            debugging('CodeLab Judge0 GET error: ' . $error, DEBUG_DEVELOPER);
            return null;
        }
        return $response ?: null;
    }

    /**
     * Construye los headers HTTP según el modo (self-hosted o RapidAPI).
     */
    private function get_headers(): array {
        if ($this->self_hosted) {
            $headers = ['Accept: application/json'];
            if (!empty($this->api_key)) {
                $headers[] = 'X-Auth-Token: ' . $this->api_key;
            }
            return $headers;
        }

        return [
            'Accept: application/json',
            'X-RapidAPI-Key: ' . $this->api_key,
            'X-RapidAPI-Host: ' . $this->api_host,
        ];
    }

    /**
     * Decodifica base64 de forma segura.
     */
    private function decode_base64(string $value): string {
        if (empty($value)) {
            return '';
        }
        $decoded = base64_decode($value, true);
        return $decoded !== false ? $decoded : $value;
    }
}
