<?php
// This file is part of Moodle - http://moodle.org/

/**
 * Estructura de restauración para mod_codelab.
 *
 * @package   mod_codelab
 * @copyright 2026 ProyectoEditorMoodle
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class restore_codelab_activity_structure_step extends restore_activity_structure_step {

    protected function define_structure(): array {
        $paths = [];

        $userinfo = $this->get_setting_value('userinfo');

        $paths[] = new restore_path_element('codelab', '/activity/codelab');
        $paths[] = new restore_path_element('codelab_testcase', '/activity/codelab/testcases/testcase');

        if ($userinfo) {
            $paths[] = new restore_path_element('codelab_submission', '/activity/codelab/submissions/submission');
        }

        return $this->prepare_activity_structure($paths);
    }

    protected function process_codelab(array $data): void {
        global $DB;

        $data = (object)$data;
        $data->course = $this->get_courseid();

        $oldid  = $data->id;
        $newid  = $DB->insert_record('codelab', $data);
        $this->apply_activity_instance($newid);
        $this->set_mapping('codelab', $oldid, $newid);
    }

    protected function process_codelab_testcase(array $data): void {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->codelabid = $this->get_new_parentid('codelab');
        $newid = $DB->insert_record('codelab_testcases', $data);
        $this->set_mapping('codelab_testcase', $oldid, $newid);
    }

    protected function process_codelab_submission(array $data): void {
        global $DB;

        $data = (object)$data;
        $data->codelabid = $this->get_new_parentid('codelab');
        $data->userid    = $this->get_mappingid('user', $data->userid);
        $DB->insert_record('codelab_submissions', $data);
    }

    protected function after_execute(): void {
        $this->add_related_files('mod_codelab', 'intro', null);
    }
}
