<?php
// This file is part of Moodle - http://moodle.org/

/**
 * Script de actualización de base de datos para mod_codelab.
 *
 * @package   mod_codelab
 * @copyright 2026 ProyectoEditorMoodle
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

function xmldb_codelab_upgrade(int $oldversion): bool {
    global $DB;
    $dbman = $DB->get_manager();

    if ($oldversion < 2026042702) {
        $table = new xmldb_table('codelab');
        $field = new xmldb_field(
            'runner_code',
            XMLDB_TYPE_TEXT,
            null,   // length
            null,   // unsigned
            false,  // notnull
            null,   // sequence
            null,   // default
            'solution_code'  // after this field
        );

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2026042702, 'codelab');
    }

    return true;
}
