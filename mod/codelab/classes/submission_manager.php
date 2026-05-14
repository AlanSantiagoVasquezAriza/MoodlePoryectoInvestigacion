<?php
// This file is part of Moodle - http://moodle.org/

/**
 * Gestión de entregas de estudiantes para CodeLab.
 *
 * @package   mod_codelab
 * @copyright 2026 ProyectoEditorMoodle
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_codelab;

defined('MOODLE_INTERNAL') || die();

class submission_manager {

    /** @var \stdClass Instancia de la actividad */
    private \stdClass $codelab;
    /** @var \context_module Contexto del módulo */
    private \context_module $context;

    public function __construct(\stdClass $codelab, \context_module $context) {
        $this->codelab = $codelab;
        $this->context = $context;
    }

    /**
     * Crea o actualiza la entrega borrador del estudiante.
     */
    public function save_draft(int $userid, string $code, string $language): \stdClass {
        global $DB;

        $existing = $DB->get_record('codelab_submissions', [
            'codelabid' => $this->codelab->id,
            'userid'    => $userid,
            'status'    => 'draft',
        ]);

        if ($existing) {
            $existing->code         = $code;
            $existing->language     = $language;
            $existing->timemodified = time();
            $DB->update_record('codelab_submissions', $existing);
            return $existing;
        }

        $submission = new \stdClass();
        $submission->codelabid      = $this->codelab->id;
        $submission->userid         = $userid;
        $submission->attempt_number = $this->count_attempts($userid) + 1;
        $submission->code           = $code;
        $submission->language       = $language;
        $submission->status         = 'draft';
        $submission->tests_passed   = 0;
        $submission->tests_total    = 0;
        $submission->timecreated    = time();
        $submission->timemodified   = time();
        $submission->id             = $DB->insert_record('codelab_submissions', $submission);

        return $submission;
    }

    /**
     * Marca una entrega como enviada (submitted) y actualiza el contador.
     */
    public function finalize_submission(int $submission_id): void {
        global $DB;

        $DB->set_field('codelab_submissions', 'status', 'submitted', ['id' => $submission_id]);
        $DB->set_field('codelab_submissions', 'timemodified', time(), ['id' => $submission_id]);
    }

    /**
     * Obtiene la mejor entrega de un usuario (mayor nota).
     */
    public function get_best_submission(int $userid): ?\stdClass {
        global $DB;

        $sql = "SELECT *
                  FROM {codelab_submissions}
                 WHERE codelabid = :codelabid
                   AND userid = :userid
                   AND status = 'submitted'
                 ORDER BY grade DESC, timemodified DESC
                 LIMIT 1";

        return $DB->get_record_sql($sql, [
            'codelabid' => $this->codelab->id,
            'userid'    => $userid,
        ]) ?: null;
    }

    /**
     * Obtiene la última entrega enviada de un usuario.
     */
    public function get_last_submission(int $userid): ?\stdClass {
        global $DB;

        $sql = "SELECT *
                  FROM {codelab_submissions}
                 WHERE codelabid = :codelabid
                   AND userid = :userid
                   AND status = 'submitted'
                 ORDER BY timemodified DESC
                 LIMIT 1";

        return $DB->get_record_sql($sql, [
            'codelabid' => $this->codelab->id,
            'userid'    => $userid,
        ]) ?: null;
    }

    /**
     * Obtiene el borrador activo de un usuario.
     */
    public function get_draft(int $userid): ?\stdClass {
        global $DB;

        return $DB->get_record('codelab_submissions', [
            'codelabid' => $this->codelab->id,
            'userid'    => $userid,
            'status'    => 'draft',
        ]) ?: null;
    }

    /**
     * Cuenta el número de intentos enviados de un usuario.
     */
    public function count_attempts(int $userid): int {
        global $DB;

        return (int)$DB->count_records('codelab_submissions', [
            'codelabid' => $this->codelab->id,
            'userid'    => $userid,
            'status'    => 'submitted',
        ]);
    }

    /**
     * Verifica si el usuario puede enviar según límites configurados.
     */
    public function can_submit(int $userid): bool {
        $max = (int)$this->codelab->max_attempts;
        if ($max === 0) {
            return true;
        }
        return $this->count_attempts($userid) < $max;
    }

    /**
     * Obtiene la última entrega (enviada o borrador) de cada estudiante.
     */
    public function get_all_latest_submissions(): array {
        global $DB;

        $sql = "SELECT s.*
                  FROM {codelab_submissions} s
                  JOIN (
                      SELECT userid, MAX(timemodified) AS latest
                        FROM {codelab_submissions}
                       WHERE codelabid = :codelabid
                         AND status = 'submitted'
                       GROUP BY userid
                  ) latest ON s.userid = latest.userid AND s.timemodified = latest.latest
                 WHERE s.codelabid = :codelabid2
                   AND s.status = 'submitted'
                 ORDER BY s.timemodified DESC";

        return $DB->get_records_sql($sql, [
            'codelabid'  => $this->codelab->id,
            'codelabid2' => $this->codelab->id,
        ]);
    }

    /**
     * Obtiene todas las entregas con sus resultados para una entrega específica.
     */
    public function get_submission_with_results(int $submission_id): ?array {
        global $DB;

        $submission = $DB->get_record('codelab_submissions', ['id' => $submission_id]);
        if (!$submission) {
            return null;
        }

        $results = $DB->get_records_sql(
            "SELECT r.*, tc.name AS testcase_name, tc.expected_output, tc.stdin,
                    tc.is_hidden, tc.points
               FROM {codelab_submission_results} r
               JOIN {codelab_testcases} tc ON tc.id = r.testcase_id
              WHERE r.submission_id = ?
              ORDER BY tc.ordering",
            [$submission_id]
        );

        return [
            'submission' => $submission,
            'results'    => array_values($results),
        ];
    }

    /**
     * Calcula estadísticas globales de la actividad.
     */
    public function get_statistics(): array {
        global $DB;

        $codelabid = $this->codelab->id;

        $total_students = (int)$DB->count_records_sql(
            "SELECT COUNT(DISTINCT userid) FROM {codelab_submissions}
              WHERE codelabid = ? AND status = 'submitted'",
            [$codelabid]
        );

        $total_submissions = (int)$DB->count_records('codelab_submissions', [
            'codelabid' => $codelabid,
            'status'    => 'submitted',
        ]);

        $avg_grade = (float)($DB->get_field_sql(
            "SELECT AVG(grade) FROM {codelab_submissions}
              WHERE codelabid = ? AND status = 'submitted' AND grade IS NOT NULL",
            [$codelabid]
        ) ?? 0);

        $passing_grade = (float)$this->codelab->grade * 0.6;
        $passed_students = (int)$DB->count_records_sql(
            "SELECT COUNT(*) FROM (
                SELECT userid, MAX(grade) AS best_grade
                  FROM {codelab_submissions}
                 WHERE codelabid = ? AND status = 'submitted'
                 GROUP BY userid
                HAVING MAX(grade) >= ?
             ) AS passed",
            [$codelabid, $passing_grade]
        );

        $pass_rate = $total_students > 0
            ? ($passed_students / $total_students) * 100
            : 0;

        // Estadísticas por caso de prueba.
        $testcase_stats = [];
        $testcases = $DB->get_records('codelab_testcases', ['codelabid' => $codelabid], 'ordering');
        foreach ($testcases as $tc) {
            $passed = (int)$DB->count_records('codelab_submission_results', [
                'testcase_id' => $tc->id,
                'status'      => 'passed',
            ]);
            $total = (int)$DB->count_records('codelab_submission_results', ['testcase_id' => $tc->id]);
            $testcase_stats[] = [
                'name'   => $tc->name,
                'passed' => $passed,
                'failed' => $total - $passed,
                'total'  => $total,
            ];
        }

        return [
            'total_students'    => $total_students,
            'total_submissions' => $total_submissions,
            'avg_grade'         => $avg_grade,
            'pass_rate'         => $pass_rate,
            'testcase_stats'    => $testcase_stats,
        ];
    }
}
