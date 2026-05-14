<?php
// This file is part of Moodle - http://moodle.org/

/**
 * Configuración de administración del sitio para mod_codelab.
 *
 * @package   mod_codelab
 * @copyright 2026 ProyectoEditorMoodle
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_heading(
        'mod_codelab/judge0_heading',
        get_string('admin_settings', 'mod_codelab'),
        get_string('pluginname', 'mod_codelab')
    ));

    // URL de la API.
    $settings->add(new admin_setting_configtext(
        'mod_codelab/judge0_api_url',
        get_string('judge0_api_url', 'mod_codelab'),
        get_string('judge0_api_url_desc', 'mod_codelab'),
        'https://judge0-ce.p.rapidapi.com',
        PARAM_RAW
    ));

    // Clave de API.
    $settings->add(new admin_setting_configpasswordunmask(
        'mod_codelab/judge0_api_key',
        get_string('judge0_api_key', 'mod_codelab'),
        get_string('judge0_api_key_desc', 'mod_codelab'),
        ''
    ));

    // Host para RapidAPI.
    $settings->add(new admin_setting_configtext(
        'mod_codelab/judge0_api_host',
        get_string('judge0_api_host', 'mod_codelab'),
        get_string('judge0_api_host_desc', 'mod_codelab'),
        'judge0-ce.p.rapidapi.com',
        PARAM_HOST
    ));

    // Modo auto-alojado.
    $settings->add(new admin_setting_configcheckbox(
        'mod_codelab/judge0_self_hosted',
        get_string('judge0_self_hosted', 'mod_codelab'),
        get_string('judge0_self_hosted_desc', 'mod_codelab'),
        0
    ));
}
