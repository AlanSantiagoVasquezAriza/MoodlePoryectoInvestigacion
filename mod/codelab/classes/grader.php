<?php
// This file is part of Moodle - http://moodle.org/

/**
 * Sistema de calificación automática para CodeLab.
 *
 * @package   mod_codelab
 * @copyright 2026 ProyectoEditorMoodle
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_codelab;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/executor/base_executor.php');
require_once(__DIR__ . '/executor/judge0_executor.php');

class grader {

    /** @var \stdClass Instancia de la actividad */
    private \stdClass $codelab;
    /** @var \mod_codelab\executor\base_executor Ejecutor de código */
    private \mod_codelab\executor\base_executor $executor;

    public function __construct(\stdClass $codelab) {
        $this->codelab  = $codelab;
        $this->executor = new \mod_codelab\executor\judge0_executor(
            (int)$codelab->time_limit,
            (int)$codelab->memory_limit
        );
    }

    /**
     * Ejecuta todos los casos de prueba y retorna los resultados con la calificación.
     *
     * @param string $code     Código a evaluar
     * @param string $language Lenguaje de programación
     * @param array  $testcases Array de objetos de casos de prueba
     * @return array ['results' => [...], 'grade' => float, 'tests_passed' => int, 'tests_total' => int]
     */
    public function run_all_testcases(string $code, string $language, array $testcases): array {
        $results      = [];
        $total_points = 0;
        $earned_points = 0;
        $tests_passed = 0;
        $tests_total  = count($testcases);

        foreach ($testcases as $tc) {
            $result = $this->executor->execute($code, $language, $tc->stdin ?? '');

            $passed = $result->status === 'accepted'
                      && $result->matches_expected($tc->expected_output);

            $tc_points = (float)($tc->points ?? 1);
            $total_points  += $tc_points;
            if ($passed) {
                $earned_points += $tc_points;
                $tests_passed++;
            }

            $results[] = [
                'testcase_id'    => (int)$tc->id,
                'testcase_name'  => $tc->name,
                'status'         => $passed ? 'passed' : $this->map_status($result->status, $passed),
                'actual_output'  => $result->stdout,
                'expected_output' => $tc->expected_output,
                'stderr'         => $result->stderr,
                'compile_output' => $result->compile_output,
                'execution_time' => $result->time,
                'memory_used'    => $result->memory,
                'passed'         => $passed,
                'points_earned'  => $passed ? $tc_points : 0,
                'points_possible' => $tc_points,
            ];
        }

        // Calcular la nota como proporción sobre la nota máxima.
        $raw_grade = $total_points > 0
            ? ($earned_points / $total_points) * (float)$this->codelab->grade
            : 0;

        return [
            'results'      => $results,
            'grade'        => round($raw_grade, 2),
            'tests_passed' => $tests_passed,
            'tests_total'  => $tests_total,
            'points_earned' => $earned_points,
            'points_total'  => $total_points,
        ];
    }

    /**
     * Guarda los resultados en la base de datos y actualiza el libro de notas.
     */
    public function save_results(int $submission_id, array $grading_result): void {
        global $DB;

        // Guardar resultados individuales.
        $DB->delete_records('codelab_submission_results', ['submission_id' => $submission_id]);

        foreach ($grading_result['results'] as $res) {
            $record = new \stdClass();
            $record->submission_id  = $submission_id;
            $record->testcase_id    = $res['testcase_id'];
            $record->status         = $res['status'];
            $record->actual_output  = $res['actual_output'];
            $record->stderr         = $res['stderr'] ?? '';
            $record->compile_output = $res['compile_output'] ?? '';
            $record->execution_time = $res['execution_time'];
            $record->memory_used    = $res['memory_used'];
            $record->timecreated    = time();
            $DB->insert_record('codelab_submission_results', $record);
        }

        // Actualizar la entrega.
        $submission = $DB->get_record('codelab_submissions', ['id' => $submission_id]);
        $submission->grade        = $grading_result['grade'];
        $submission->tests_passed = $grading_result['tests_passed'];
        $submission->tests_total  = $grading_result['tests_total'];
        $submission->timemodified = time();
        $DB->update_record('codelab_submissions', $submission);

        // Actualizar el libro de notas según el método.
        $this->update_gradebook($submission->userid, $grading_result['grade']);
    }

    /**
     * Actualiza el libro de notas según el método de calificación configurado.
     */
    private function update_gradebook(int $userid, float $new_grade): void {
        global $DB;

        $codelab = $this->codelab;

        switch ($codelab->grading_method) {
            case 'best':
                $current = $DB->get_field_sql(
                    "SELECT MAX(grade) FROM {codelab_submissions}
                      WHERE codelabid = ? AND userid = ? AND status = 'submitted'",
                    [$codelab->id, $userid]
                );
                $grade_to_set = max((float)($current ?? 0), $new_grade);
                break;

            case 'first':
                $existing = $DB->get_records('codelab_submissions', [
                    'codelabid' => $codelab->id,
                    'userid'    => $userid,
                    'status'    => 'submitted',
                ], 'timecreated ASC', '*', 0, 1);
                $first = reset($existing);
                $grade_to_set = $first ? (float)$first->grade : $new_grade;
                break;

            case 'average':
                $avg = $DB->get_field_sql(
                    "SELECT AVG(grade) FROM {codelab_submissions}
                      WHERE codelabid = ? AND userid = ? AND status = 'submitted'",
                    [$codelab->id, $userid]
                );
                $grade_to_set = (float)($avg ?? $new_grade);
                break;

            default: // 'last'
                $grade_to_set = $new_grade;
        }

        $grades = [
            $userid => [
                'userid'   => $userid,
                'rawgrade' => $grade_to_set,
            ],
        ];

        codelab_grade_item_update($codelab, $grades);
    }

    /**
     * Mapea los estados del ejecutor a estados legibles.
     */
    private function map_status(string $executor_status, bool $passed): string {
        if ($passed) {
            return 'passed';
        }
        return match($executor_status) {
            'accepted'             => 'failed',
            'wrong_answer'         => 'failed',
            'time_limit_exceeded'  => 'timeout',
            'compile_error'        => 'compile_error',
            'runtime_error'        => 'error',
            default                => 'error',
        };
    }
}
