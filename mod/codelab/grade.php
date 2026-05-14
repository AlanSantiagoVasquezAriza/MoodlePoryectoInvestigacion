<?php
// This file is part of Moodle - http://moodle.org/

/**
 * Procesa la calificación manual de una entrega.
 *
 * @package   mod_codelab
 * @copyright 2026 ProyectoEditorMoodle
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once(__DIR__ . '/lib.php');

$cmid = required_param('id', PARAM_INT);
$sid  = required_param('sid', PARAM_INT);

$cm      = get_coursemodule_from_id('codelab', $cmid, 0, false, MUST_EXIST);
$course  = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$codelab = $DB->get_record('codelab', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/codelab:grade', $context);
require_sesskey();

$submission = $DB->get_record('codelab_submissions', ['id' => $sid, 'codelabid' => $codelab->id], '*', MUST_EXIST);

$grade_override = optional_param('grade_override', null, PARAM_FLOAT);
$feedback       = optional_param('feedback', '', PARAM_TEXT);

$submission->feedback       = $feedback;
$submission->timemodified   = time();

if ($grade_override !== null && $grade_override >= 0 && $grade_override <= $codelab->grade) {
    $submission->grade_override = $grade_override;
    $grade_to_set = $grade_override;
} else {
    $submission->grade_override = null;
    $grade_to_set = (float)($submission->grade ?? 0);
}

$DB->update_record('codelab_submissions', $submission);

// Actualizar libro de notas.
$grades = [
    $submission->userid => [
        'userid'   => $submission->userid,
        'rawgrade' => $grade_to_set,
    ],
];
codelab_grade_item_update($codelab, $grades);

redirect(
    new moodle_url('/mod/codelab/submission.php', ['id' => $cmid, 'sid' => $sid]),
    'Calificación guardada correctamente.',
    null,
    \core\output\notification::NOTIFY_SUCCESS
);
