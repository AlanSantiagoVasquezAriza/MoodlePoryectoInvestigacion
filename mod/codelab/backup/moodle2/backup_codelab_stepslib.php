<?php
// This file is part of Moodle - http://moodle.org/

/**
 * Estructura de backup para mod_codelab.
 *
 * @package   mod_codelab
 * @copyright 2026 ProyectoEditorMoodle
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class backup_codelab_activity_structure_step extends backup_activity_structure_step {

    protected function define_structure(): backup_nested_element {

        $userinfo = $this->get_setting_value('userinfo');

        // Elemento raíz.
        $codelab = new backup_nested_element('codelab', ['id'], [
            'name', 'intro', 'introformat', 'default_language', 'languages_allowed',
            'starter_code', 'time_limit', 'memory_limit', 'max_attempts',
            'grading_method', 'grade', 'testcases_visible',
            'allow_submissions_from_date', 'duedate', 'cutoffdate',
            'timecreated', 'timemodified',
        ]);

        // Casos de prueba.
        $testcases = new backup_nested_element('testcases');
        $testcase  = new backup_nested_element('testcase', ['id'], [
            'name', 'description', 'stdin', 'expected_output',
            'points', 'is_hidden', 'ordering', 'timecreated',
        ]);

        // Entregas (solo si se incluye userinfo).
        $submissions = new backup_nested_element('submissions');
        $submission  = new backup_nested_element('submission', ['id'], [
            'userid', 'attempt_number', 'code', 'language', 'status',
            'grade', 'grade_override', 'feedback',
            'tests_passed', 'tests_total', 'timecreated', 'timemodified',
        ]);

        // Relaciones jerárquicas.
        $codelab->add_child($testcases);
        $testcases->add_child($testcase);

        if ($userinfo) {
            $codelab->add_child($submissions);
            $submissions->add_child($submission);
        }

        // Fuentes de datos.
        $codelab->set_source_table('codelab', ['id' => backup::VAR_ACTIVITYID]);
        $testcase->set_source_table('codelab_testcases', ['codelabid' => backup::VAR_PARENTID], 'ordering ASC');

        if ($userinfo) {
            $submission->set_source_table('codelab_submissions', ['codelabid' => backup::VAR_PARENTID]);
            $submission->annotate_ids('user', 'userid');
        }

        $codelab->annotate_files('mod_codelab', 'intro', null);

        return $this->prepare_activity_structure($codelab);
    }
}
