<?php
// This file is part of Moodle - http://moodle.org/

/**
 * Cadenas de idioma en Español para mod_codelab.
 *
 * @package   mod_codelab
 * @copyright 2026 ProyectoEditorMoodle
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Información del plugin.
$string['pluginname']        = 'CodeLab - Editor de Código';
$string['pluginadministration'] = 'Administración de CodeLab';
$string['modulename']        = 'CodeLab';
$string['modulenameplural']  = 'CodeLabs';
$string['modulename_help']   = 'CodeLab permite a los estudiantes escribir, ejecutar y entregar código directamente en Moodle. Los profesores pueden definir casos de prueba para calificación automática.';

// Configuración general.
$string['codeeditor_settings']  = 'Configuración del Editor';
$string['default_language']     = 'Lenguaje por defecto';
$string['languages_allowed']    = 'Lenguajes permitidos';
$string['languages_allowed_help'] = 'Selecciona los lenguajes de programación que los estudiantes pueden usar para esta actividad.';
$string['starter_code']         = 'Código inicial';
$string['starter_code_help']    = 'Código que aparece en el editor cuando el estudiante abre la actividad por primera vez. Útil para proporcionar una plantilla o estructura base.';
$string['runner_code']          = 'Código envolvente (runner automático)';
$string['runner_code_help']     = 'Código que envuelve automáticamente la solución del estudiante. Usa {{CODE}} como marcador donde se insertará el código del estudiante. Permite que los estudiantes solo escriban su función/lógica sin necesitar input() ni print().';
$string['execution_limits']     = 'Límites de Ejecución';
$string['time_limit']           = 'Límite de tiempo por ejecución';
$string['memory_limit']         = 'Límite de memoria';
$string['seconds']              = '{$a} segundos';
$string['submission_settings']  = 'Configuración de Entrega';
$string['max_attempts']         = 'Intentos máximos';
$string['unlimited']            = 'Ilimitado';
$string['grading_method']       = 'Método de calificación';
$string['grading_best']         = 'Mejor intento';
$string['grading_last']         = 'Último intento';
$string['grading_first']        = 'Primer intento';
$string['grading_average']      = 'Promedio de intentos';
$string['testcases_visible']    = 'Mostrar casos de prueba a estudiantes';
$string['testcases_visible_help'] = 'Si está activo, los estudiantes pueden ver el nombre, la entrada y la salida esperada de cada caso de prueba. Los casos marcados como "oculto" nunca serán visibles.';
$string['allow_submissions_from_date'] = 'Permitir entregas desde';
$string['duedate']              = 'Fecha de entrega';
$string['cutoffdate']           = 'Fecha de corte (sin entregas después de esta fecha)';
$string['languages']            = 'Lenguajes';

// Casos de prueba.
$string['testcases']            = 'Casos de Prueba';
$string['testcase']             = 'Caso de prueba';
$string['add_testcase']         = '+ Agregar caso de prueba';
$string['tc_name']              = 'Nombre';
$string['tc_stdin']             = 'Entrada (stdin)';
$string['tc_expected_output']   = 'Salida esperada';
$string['tc_points']            = 'Puntos';
$string['tc_hidden']            = 'Oculto';
$string['hidden_testcase']      = 'Caso oculto';

// Vista del estudiante.
$string['last_submission']      = 'Última entrega';
$string['grade']                = 'Calificación';
$string['attempts']             = 'Intentos';
$string['pending']              = 'Pendiente';
$string['past_due']             = '⚠️ La fecha de entrega ha pasado. Puedes seguir enviando, pero podría afectar tu calificación.';
$string['past_cutoff']          = '❌ La actividad está cerrada. No se aceptan más entregas.';

// Vista del profesor.
$string['submissions']          = 'Entregas';
$string['statistics']           = 'Estadísticas';
$string['preview']              = 'Vista previa';
$string['student']              = 'Estudiante';
$string['submitted']            = 'Enviado';
$string['language']             = 'Lenguaje';
$string['tests_passed']         = 'Casos correctos';
$string['actions']              = 'Acciones';
$string['view']                 = 'Ver';
$string['no_submissions_yet']   = 'Aún no hay entregas para esta actividad.';
$string['preview_mode_notice']  = 'Modo vista previa: estás viendo la actividad como un estudiante.';
$string['total_students']       = 'Estudiantes';
$string['total_submissions']    = 'Total entregas';
$string['avg_grade']            = 'Nota promedio';
$string['pass_rate']            = 'Tasa de aprobación';
$string['testcase_pass_rates']  = 'Rendimiento por caso de prueba';
$string['passed']               = 'Aprobados';
$string['failed']               = 'Fallidos';

// Ejecución y evaluación.
$string['language_not_allowed'] = 'El lenguaje seleccionado no está permitido en esta actividad.';
$string['code_too_large']       = 'El código es demasiado largo (máximo 64KB).';
$string['no_testcases']         = 'Esta actividad no tiene casos de prueba configurados.';
$string['max_attempts_reached'] = 'Has alcanzado el límite de intentos permitidos.';
$string['submission_saved']     = '¡Tu entrega ha sido registrada exitosamente!';
$string['executor_connection_error'] = 'No se pudo conectar con el servidor de ejecución de código. Contacta al administrador.';
$string['executor_timeout']     = 'El servidor de ejecución tardó demasiado. Inténtalo más tarde.';

// Configuración de administración.
$string['admin_settings']       = 'Configuración de CodeLab';
$string['judge0_api_url']       = 'URL de la API Judge0';
$string['judge0_api_url_desc']  = 'URL base de tu instancia Judge0 (ej: https://judge0-ce.p.rapidapi.com o tu servidor auto-alojado).';
$string['judge0_api_key']       = 'Clave API de Judge0';
$string['judge0_api_key_desc']  = 'Clave de autenticación. Para RapidAPI es la X-RapidAPI-Key. Para servidor propio es el X-Auth-Token.';
$string['judge0_api_host']      = 'Host de RapidAPI';
$string['judge0_api_host_desc'] = 'Solo necesario si usas Judge0 via RapidAPI (ej: judge0-ce.p.rapidapi.com).';
$string['judge0_self_hosted']   = 'Instancia auto-alojada';
$string['judge0_self_hosted_desc'] = 'Activa esto si estás usando tu propio servidor Judge0 con Docker, en lugar de RapidAPI.';

// Privacidad.
$string['privacy:metadata:codelab_submissions']              = 'Entregas de código de los estudiantes.';
$string['privacy:metadata:codelab_submissions:userid']       = 'ID del usuario que realiza la entrega.';
$string['privacy:metadata:codelab_submissions:code']         = 'El código fuente enviado por el estudiante.';
$string['privacy:metadata:codelab_submissions:language']     = 'Lenguaje de programación utilizado.';
$string['privacy:metadata:codelab_submissions:grade']        = 'Calificación obtenida.';
$string['privacy:metadata:codelab_submissions:timecreated']  = 'Fecha de creación de la entrega.';
$string['privacy:metadata:codelab_submissions:timemodified'] = 'Fecha de última modificación.';
$string['privacy:metadata:judge0_api']       = 'El código del estudiante y la entrada estándar se envían al servidor externo Judge0 para su ejecución.';
$string['privacy:metadata:judge0_api:code']  = 'El código fuente que se ejecuta.';
$string['privacy:metadata:judge0_api:stdin'] = 'La entrada estándar proporcionada al programa.';
$string['privacy:path:submissions']          = 'Entregas de código';
