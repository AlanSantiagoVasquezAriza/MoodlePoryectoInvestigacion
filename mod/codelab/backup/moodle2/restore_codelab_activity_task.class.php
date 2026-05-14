<?php
// This file is part of Moodle - http://moodle.org/

/**
 * Tarea de restauración para mod_codelab.
 *
 * @package   mod_codelab
 * @copyright 2026 ProyectoEditorMoodle
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/codelab/backup/moodle2/restore_codelab_stepslib.php');

class restore_codelab_activity_task extends restore_activity_task {

    protected function define_my_settings(): void {
    }

    protected function define_my_steps(): void {
        $this->add_step(new restore_codelab_activity_structure_step('codelab_structure', 'codelab.xml'));
    }

    public static function define_decode_contents(): array {
        $contents = [];
        $contents[] = new restore_decode_content('codelab', ['intro'], 'codelab');
        return $contents;
    }

    public static function define_decode_rules(): array {
        $rules = [];
        $rules[] = new restore_decode_rule('CODELABVIEWBYID', '/mod/codelab/view.php?id=$1', 'course_module');
        return $rules;
    }

    public static function define_restore_log_rules(): array {
        $rules = [];
        return $rules;
    }
}
