<?php
// This file is part of Moodle - http://moodle.org/

/**
 * Tarea de backup para mod_codelab.
 *
 * @package   mod_codelab
 * @copyright 2026 ProyectoEditorMoodle
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/codelab/backup/moodle2/backup_codelab_stepslib.php');

class backup_codelab_activity_task extends backup_activity_task {

    protected function define_my_settings(): void {
    }

    protected function define_my_steps(): void {
        $this->add_step(new backup_codelab_activity_structure_step('codelab_structure', 'codelab.xml'));
    }

    public static function encode_content_links(string $content): string {
        global $CFG;

        $base = preg_quote($CFG->wwwroot, '/');

        // Vista de la actividad.
        $pattern     = "/({$base}\/mod\/codelab\/view\.php\?id=)([0-9]+)/";
        $replacement = '$@CODELABVIEWBYID*$2@$';
        $content = preg_replace($pattern, $replacement, $content);

        return $content;
    }
}
