<?php
// This file is part of Moodle - http://moodle.org/

/**
 * Vista detallada de una entrega individual (para el profesor).
 *
 * @package   mod_codelab
 * @copyright 2026 ProyectoEditorMoodle
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once(__DIR__ . '/lib.php');
require_once(__DIR__ . '/classes/submission_manager.php');

$cmid = required_param('id', PARAM_INT);
$sid  = required_param('sid', PARAM_INT);

$cm      = get_coursemodule_from_id('codelab', $cmid, 0, false, MUST_EXIST);
$course  = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$codelab = $DB->get_record('codelab', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/codelab:viewallsubmissions', $context);

$PAGE->set_url('/mod/codelab/submission.php', ['id' => $cmid, 'sid' => $sid]);
$PAGE->set_title(format_string($codelab->name) . ' - ' . get_string('submissions', 'mod_codelab'));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);
$PAGE->requires->css('/mod/codelab/styles.css');

$submission_manager = new \mod_codelab\submission_manager($codelab, $context);
$data = $submission_manager->get_submission_with_results($sid);

if (!$data) {
    throw new moodle_exception('invalidsubmission', 'mod_codelab');
}

$submission = $data['submission'];
$results    = $data['results'];

$student = $DB->get_record('user', ['id' => $submission->userid]);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('submissions', 'mod_codelab') . ': ' . fullname($student));

// Navegación de regreso.
echo html_writer::link(
    new moodle_url('/mod/codelab/view.php', ['id' => $cmid]),
    '← ' . get_string('submissions', 'mod_codelab'),
    ['class' => 'btn btn-secondary btn-sm mb-3']
);

// Información de la entrega.
echo '<div class="card mb-3">';
echo '<div class="card-header"><strong>' . get_string('last_submission', 'mod_codelab') . '</strong></div>';
echo '<div class="card-body">';
echo '<div class="row">';
echo '<div class="col-md-3"><strong>Estudiante:</strong> ' . fullname($student) . '</div>';
echo '<div class="col-md-3"><strong>' . get_string('language', 'mod_codelab') . ':</strong> ' . strtoupper($submission->language) . '</div>';
echo '<div class="col-md-3"><strong>' . get_string('submitted', 'mod_codelab') . ':</strong> ' . userdate($submission->timemodified) . '</div>';
echo '<div class="col-md-3"><strong>' . get_string('grade', 'mod_codelab') . ':</strong> ';

$grade = $submission->grade_override ?? $submission->grade;
if ($grade !== null) {
    echo '<span class="badge badge-' . ($grade >= $codelab->grade * 0.6 ? 'success' : 'warning') . '">';
    echo number_format((float)$grade, 1) . ' / ' . $codelab->grade;
    echo '</span>';
} else {
    echo '<span class="text-muted">Sin calificar</span>';
}
echo '</div>';
echo '</div>';

// Tests summary.
echo '<div class="mt-2">';
$passed = $submission->tests_passed;
$total  = $submission->tests_total;
$pct    = $total > 0 ? round($passed / $total * 100) : 0;
echo "<strong>Casos de prueba:</strong> {$passed}/{$total} ({$pct}%)";
echo ' <div class="progress mt-1" style="height:8px;max-width:300px;">';
echo "<div class='progress-bar bg-" . ($pct >= 60 ? 'success' : 'warning') . "' style='width:{$pct}%'></div>";
echo '</div>';
echo '</div>';

echo '</div></div>';

// Resultados de casos de prueba.
if (!empty($results)) {
    echo '<h5>' . get_string('testcases', 'mod_codelab') . '</h5>';
    foreach ($results as $r) {
        $status_class = $r->status === 'passed' ? 'success' : 'danger';
        $status_icon  = $r->status === 'passed' ? 'check-circle' : 'times-circle';

        echo "<div class='card mb-2 border-{$status_class}'>";
        echo "<div class='card-header d-flex justify-content-between align-items-center'>";
        echo "<span><i class='fa fa-{$status_icon} text-{$status_class}'></i> <strong>" . htmlspecialchars($r->testcase_name) . "</strong>";
        if ($r->is_hidden) {
            echo " <span class='badge badge-secondary'>oculto</span>";
        }
        echo "</span>";
        echo "<span class='badge badge-{$status_class}'>{$r->points} pts</span>";
        echo "</div>";
        echo "<div class='card-body py-2'>";

        if (!empty($r->stdin)) {
            echo "<div class='mb-1'><strong class='text-muted small'>Entrada:</strong><br><code>" . htmlspecialchars($r->stdin) . "</code></div>";
        }
        echo "<div class='mb-1'><strong class='text-muted small'>Salida esperada:</strong><br><code>" . htmlspecialchars($r->expected_output) . "</code></div>";
        if ($r->actual_output !== null) {
            $match_class = $r->status === 'passed' ? '' : 'text-danger';
            echo "<div class='mb-1'><strong class='text-muted small'>Salida obtenida:</strong><br><code class='{$match_class}'>" . htmlspecialchars($r->actual_output) . "</code></div>";
        }
        if (!empty($r->compile_output)) {
            echo "<div class='mb-1'><strong class='text-danger small'>Error de compilación:</strong><br><pre class='text-danger' style='font-size:0.8rem'>" . htmlspecialchars($r->compile_output) . "</pre></div>";
        }
        if (!empty($r->stderr)) {
            echo "<div class='mb-1'><strong class='text-warning small'>Stderr:</strong><br><pre class='text-warning' style='font-size:0.8rem'>" . htmlspecialchars($r->stderr) . "</pre></div>";
        }
        if ($r->execution_time) {
            echo "<small class='text-muted'><i class='fa fa-clock-o'></i> " . $r->execution_time . "s";
            if ($r->memory_used) {
                echo " &nbsp; <i class='fa fa-microchip'></i> " . round($r->memory_used / 1024, 1) . " MB";
            }
            echo "</small>";
        }

        echo "</div></div>";
    }
}

// Código del estudiante.
echo '<h5 class="mt-4">Código enviado</h5>';
echo '<div class="position-relative">';
echo '<button class="btn btn-sm btn-outline-secondary position-absolute" style="top:8px;right:8px;z-index:1;" '
    . 'onclick="navigator.clipboard.writeText(document.getElementById(\'sub-code\').textContent);this.textContent=\'✓ Copiado\';setTimeout(()=>this.textContent=\'Copiar\',2000)">Copiar</button>';
echo '<pre id="sub-code" class="codelab-submission-code-block">' . htmlspecialchars($submission->code) . '</pre>';
echo '</div>';

// Formulario de retroalimentación y nota manual.
echo '<div class="card mt-4">';
echo '<div class="card-header"><strong>Retroalimentación del profesor (opcional)</strong></div>';
echo '<div class="card-body">';
$action_url = new moodle_url('/mod/codelab/grade.php', ['id' => $cmid, 'sid' => $sid]);
echo html_writer::start_tag('form', ['method' => 'post', 'action' => $action_url]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);

echo '<div class="form-group">';
echo '<label for="grade_override">Nota manual (deja vacío para usar la automática):</label>';
echo html_writer::empty_tag('input', [
    'type'  => 'number',
    'id'    => 'grade_override',
    'name'  => 'grade_override',
    'class' => 'form-control',
    'min'   => 0,
    'max'   => $codelab->grade,
    'step'  => '0.01',
    'value' => $submission->grade_override ?? '',
    'placeholder' => 'Nota automática: ' . number_format((float)($submission->grade ?? 0), 1),
    'style' => 'max-width:200px;',
]);
echo '</div>';

echo '<div class="form-group">';
echo '<label for="feedback">Comentarios:</label>';
echo html_writer::tag('textarea', htmlspecialchars($submission->feedback ?? ''), [
    'id'    => 'feedback',
    'name'  => 'feedback',
    'class' => 'form-control',
    'rows'  => 4,
    'placeholder' => 'Escribe retroalimentación para el estudiante...',
]);
echo '</div>';

echo html_writer::empty_tag('input', [
    'type'  => 'submit',
    'value' => 'Guardar calificación',
    'class' => 'btn btn-primary',
]);
echo html_writer::end_tag('form');
echo '</div></div>';

echo $OUTPUT->footer();
