<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Funciones principales del módulo CodeLab.
 *
 * @package   mod_codelab
 * @copyright 2026 ProyectoEditorMoodle
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Agrega una nueva instancia de codelab al curso.
 */
function codelab_add_instance(stdClass $codelab, ?mod_codelab_mod_form $mform = null): int {
    global $DB;

    $codelab->timecreated  = time();
    $codelab->timemodified = time();

    $codelab->id = $DB->insert_record('codelab', $codelab);

    codelab_grade_item_update($codelab);

    if (!empty($mform)) {
        codelab_save_testcases($codelab->id, $mform->get_testcases_data());
    }

    return $codelab->id;
}

/**
 * Actualiza una instancia existente de codelab.
 */
function codelab_update_instance(stdClass $codelab, ?mod_codelab_mod_form $mform = null): bool {
    global $DB;

    $codelab->timemodified = time();
    $codelab->id = $codelab->instance;

    $result = $DB->update_record('codelab', $codelab);

    codelab_grade_item_update($codelab);

    if (!empty($mform)) {
        codelab_save_testcases($codelab->id, $mform->get_testcases_data());
    }

    return $result;
}

/**
 * Elimina una instancia de codelab del curso.
 */
function codelab_delete_instance(int $id): bool {
    global $DB;

    if (!$codelab = $DB->get_record('codelab', ['id' => $id])) {
        return false;
    }

    $DB->delete_records('codelab_submission_results', ['submission_id' => $DB->get_fieldset_select(
        'codelab_submissions', 'id', 'codelabid = ?', [$id]
    )]);
    $DB->delete_records('codelab_submissions', ['codelabid' => $id]);
    $DB->delete_records('codelab_testcases', ['codelabid' => $id]);
    $DB->delete_records('codelab', ['id' => $id]);

    codelab_grade_item_delete($codelab);

    return true;
}

/**
 * Devuelve si el usuario puede añadir esta actividad.
 */
function codelab_supports(string $feature): ?bool {
    switch ($feature) {
        case FEATURE_GROUPS:
            return true;
        case FEATURE_GROUPINGS:
            return true;
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            return true;
        case FEATURE_COMPLETION_HAS_RULES:
            return true;
        case FEATURE_GRADE_HAS_GRADE:
            return true;
        case FEATURE_GRADE_OUTCOMES:
            return true;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        case FEATURE_MOD_PURPOSE:
            return MOD_PURPOSE_ASSESSMENT;
        default:
            return null;
    }
}

/**
 * Crea/actualiza el ítem de calificación en el libro de notas.
 */
function codelab_grade_item_update(stdClass $codelab, mixed $grades = null): int {
    global $CFG;
    require_once($CFG->libdir . '/gradelib.php');

    $params = [
        'itemname' => $codelab->name,
        'idnumber' => $codelab->cmidnumber ?? '',
    ];

    if (!isset($codelab->courseid)) {
        $codelab->courseid = $codelab->course;
    }

    if ($codelab->grade > 0) {
        $params['gradetype'] = GRADE_TYPE_VALUE;
        $params['grademax']  = $codelab->grade;
        $params['grademin']  = 0;
    } else if ($codelab->grade < 0) {
        $params['gradetype'] = GRADE_TYPE_SCALE;
        $params['scaleid']   = -$codelab->grade;
    } else {
        $params['gradetype'] = GRADE_TYPE_NONE;
    }

    if ($grades === 'reset') {
        $params['reset'] = true;
        $grades = null;
    }

    return grade_update(
        'mod/codelab',
        $codelab->course,
        'mod',
        'codelab',
        $codelab->id,
        0,
        $grades,
        $params
    );
}

/**
 * Elimina el ítem de calificación del libro de notas.
 */
function codelab_grade_item_delete(stdClass $codelab): int {
    global $CFG;
    require_once($CFG->libdir . '/gradelib.php');

    return grade_update(
        'mod/codelab',
        $codelab->course,
        'mod',
        'codelab',
        $codelab->id,
        0,
        null,
        ['deleted' => 1]
    );
}

/**
 * Actualiza la calificación de un usuario en el libro de notas.
 */
function codelab_update_grades(stdClass $codelab, int $userid = 0): void {
    global $CFG;
    require_once($CFG->libdir . '/gradelib.php');

    if ($grades = codelab_get_user_grades($codelab, $userid)) {
        codelab_grade_item_update($codelab, $grades);
    } else {
        codelab_grade_item_update($codelab);
    }
}

/**
 * Obtiene las calificaciones de usuario para el libro de notas.
 */
function codelab_get_user_grades(stdClass $codelab, int $userid = 0): array|false {
    global $DB;

    $params = ['codelabid' => $codelab->id, 'status' => 'submitted'];
    $sql = "SELECT userid, MAX(grade) AS rawgrade, MAX(timemodified) AS dategraded
              FROM {codelab_submissions}
             WHERE codelabid = :codelabid
               AND status = :status";

    if ($userid) {
        $sql .= " AND userid = :userid";
        $params['userid'] = $userid;
    }

    $sql .= " GROUP BY userid";

    $grades = [];
    $records = $DB->get_records_sql($sql, $params);

    foreach ($records as $record) {
        $grades[$record->userid] = [
            'userid'   => $record->userid,
            'rawgrade' => $record->rawgrade,
            'dategraded' => $record->dategraded,
        ];
    }

    return empty($grades) ? false : $grades;
}

/**
 * Guarda los casos de prueba de una actividad.
 */
function codelab_save_testcases(int $codelabid, array $testcases): void {
    global $DB;

    $DB->delete_records('codelab_testcases', ['codelabid' => $codelabid]);

    foreach ($testcases as $order => $tc) {
        if (empty($tc['expected_output']) && empty($tc['name'])) {
            continue;
        }
        $record = new stdClass();
        $record->codelabid       = $codelabid;
        $record->name            = $tc['name'] ?? get_string('testcase', 'mod_codelab') . ' ' . ($order + 1);
        $record->stdin           = $tc['stdin'] ?? '';
        $record->expected_output = trim($tc['expected_output'] ?? '');
        $record->points          = (float)($tc['points'] ?? 1);
        $record->is_hidden       = (int)($tc['is_hidden'] ?? 0);
        $record->ordering        = $order;
        $record->timecreated     = time();
        $DB->insert_record('codelab_testcases', $record);
    }
}

/**
 * Retorna la información de la actividad para la lista del curso.
 */
function codelab_get_coursemodule_info(stdClass $coursemodule): ?cached_cm_info {
    global $DB;

    if (!$codelab = $DB->get_record('codelab', ['id' => $coursemodule->instance], 'id, name, intro, introformat')) {
        return null;
    }

    $info = new cached_cm_info();
    $info->name = $codelab->name;

    if ($coursemodule->showdescription) {
        $info->content = format_module_intro('codelab', $codelab, $coursemodule->id, false);
    }

    return $info;
}
