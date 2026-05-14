<?php
// This file is part of Moodle - http://moodle.org/

/**
 * Endpoint AJAX para ejecutar código y calificar con casos de prueba.
 *
 * Recibe: POST JSON con { cmid, code, language, action: 'run'|'submit' }
 * Devuelve: JSON con resultados de ejecución y calificación.
 *
 * @package   mod_codelab
 * @copyright 2026 ProyectoEditorMoodle
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);

require('../../config.php');
require_once(__DIR__ . '/lib.php');
require_once(__DIR__ . '/classes/grader.php');
require_once(__DIR__ . '/classes/submission_manager.php');

// Leer el body JSON.
$rawbody = file_get_contents('php://input');
$input   = json_decode($rawbody, true) ?? [];

// confirm_sesskey() lee de $_POST/$_GET; como enviamos JSON hay que exponerlo primero.
if (!empty($input['sesskey'])) {
    $_POST['sesskey'] = $input['sesskey'];
}

// Validar sesskey para proteger contra CSRF.
if (empty($input['sesskey']) || !confirm_sesskey($input['sesskey'])) {
    send_json_error(get_string('invalidsesskey', 'error'));
}

$cmid     = (int)($input['cmid'] ?? 0);
$code     = $input['code'] ?? '';
$language = clean_param($input['language'] ?? 'python', PARAM_ALPHA);
$action   = clean_param($input['action'] ?? 'run', PARAM_ALPHA);

if (!$cmid || empty($code)) {
    send_json_error(get_string('missingparam', 'error', 'cmid/code'));
}

$cm      = get_coursemodule_from_id('codelab', $cmid, 0, false, MUST_EXIST);
$course  = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$codelab = $DB->get_record('codelab', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, false, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/codelab:execute', $context);

// Validar lenguaje permitido.
$allowed = json_decode($codelab->languages_allowed ?? '[]', true) ?: [$codelab->default_language];
if (!in_array($language, $allowed)) {
    send_json_error(get_string('language_not_allowed', 'mod_codelab'));
}

// Validar tamaño de código (máx 64 KB).
if (strlen($code) > 65536) {
    send_json_error(get_string('code_too_large', 'mod_codelab'));
}

// Si hay un runner_code definido, envolver el código del estudiante.
if (!empty(trim($codelab->runner_code ?? ''))) {
    $runner = $codelab->runner_code;
    if (strpos($runner, '{{CODE}}') !== false) {
        $code = str_replace('{{CODE}}', $code, $runner);
    } else {
        // Si no hay marcador, poner el código del estudiante al inicio.
        $code = $code . "\n" . $runner;
    }
}

// Obtener todos los casos de prueba (incluyendo ocultos para la calificación).
$testcases = $DB->get_records('codelab_testcases', ['codelabid' => $codelab->id], 'ordering ASC');

if (empty($testcases)) {
    send_json_error(get_string('no_testcases', 'mod_codelab'));
}

$grader = new \mod_codelab\grader($codelab);
$grading_result = $grader->run_all_testcases($code, $language, $testcases);

// Si es una entrega definitiva, guardar en DB y actualizar calificación.
if ($action === 'submit') {
    require_capability('mod/codelab:submit', $context);

    $submission_manager = new \mod_codelab\submission_manager($codelab, $context);

    if (!$submission_manager->can_submit($USER->id)) {
        send_json_error(get_string('max_attempts_reached', 'mod_codelab'));
    }

    // Verificar fecha de corte.
    if ($codelab->cutoffdate > 0 && time() > $codelab->cutoffdate) {
        send_json_error(get_string('past_cutoff', 'mod_codelab'));
    }

    $submission = $submission_manager->save_draft($USER->id, $code, $language);
    $grader->save_results($submission->id, $grading_result);
    $submission_manager->finalize_submission($submission->id);

    $grading_result['submission_id'] = $submission->id;
    $grading_result['submitted']     = true;
    $grading_result['message']       = get_string('submission_saved', 'mod_codelab');
}

// Filtrar resultados ocultos para la respuesta al estudiante.
$is_teacher = has_capability('mod/codelab:viewallsubmissions', $context);

if (!$is_teacher && !(bool)$codelab->testcases_visible) {
    // Si los casos no son visibles, solo devolver el resumen.
    $response = [
        'success'      => true,
        'tests_passed' => $grading_result['tests_passed'],
        'tests_total'  => $grading_result['tests_total'],
        'grade'        => $grading_result['grade'],
        'max_grade'    => (float)$codelab->grade,
        'results'      => [], // Sin detalle.
        'submitted'    => $grading_result['submitted'] ?? false,
        'message'      => $grading_result['message'] ?? '',
    ];
} else {
    // Ocultar detalles de casos ocultos para estudiantes.
    $visible_results = [];
    foreach ($grading_result['results'] as $r) {
        $tc = $testcases[$r['testcase_id']] ?? null;
        $is_hidden = $tc && $tc->is_hidden;

        if (!$is_teacher && $is_hidden) {
            $visible_results[] = [
                'testcase_id'   => $r['testcase_id'],
                'testcase_name' => get_string('hidden_testcase', 'mod_codelab'),
                'status'        => $r['passed'] ? 'passed' : 'failed',
                'passed'        => $r['passed'],
                'points_earned' => $r['points_earned'],
                'points_possible' => $r['points_possible'],
                'hidden'        => true,
            ];
        } else {
            $visible_results[] = array_merge($r, ['hidden' => false]);
        }
    }

    $response = [
        'success'      => true,
        'tests_passed' => $grading_result['tests_passed'],
        'tests_total'  => $grading_result['tests_total'],
        'grade'        => $grading_result['grade'],
        'max_grade'    => (float)$codelab->grade,
        'results'      => $visible_results,
        'submitted'    => $grading_result['submitted'] ?? false,
        'message'      => $grading_result['message'] ?? '',
    ];
}

header('Content-Type: application/json');
echo json_encode($response);

/**
 * Envía un error JSON y termina la ejecución.
 */
function send_json_error(string $message): never {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => $message]);
    exit;
}
