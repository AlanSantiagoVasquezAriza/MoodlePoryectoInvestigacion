<?php
// This file is part of Moodle - http://moodle.org/

/**
 * English language strings for mod_codelab.
 *
 * @package   mod_codelab
 * @copyright 2026 ProyectoEditorMoodle
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname']        = 'CodeLab - Code Editor';
$string['pluginadministration'] = 'CodeLab Administration';
$string['modulename']        = 'CodeLab';
$string['modulenameplural']  = 'CodeLabs';
$string['modulename_help']   = 'CodeLab allows students to write, run and submit code directly in Moodle. Teachers can define test cases for automatic grading.';

$string['codeeditor_settings']  = 'Editor Settings';
$string['default_language']     = 'Default language';
$string['languages_allowed']    = 'Allowed languages';
$string['languages_allowed_help'] = 'Select the programming languages students can use for this activity.';
$string['starter_code']         = 'Starter code';
$string['starter_code_help']    = 'Code shown in the editor when the student first opens the activity. Useful for providing a template or base structure.';
$string['runner_code']          = 'Runner code (auto wrapper)';
$string['runner_code_help']     = 'Code that automatically wraps the student\'s solution. Use {{CODE}} as a placeholder where the student\'s code will be inserted. Lets students only write their function/logic without needing input() or print().';
$string['execution_limits']     = 'Execution Limits';
$string['time_limit']           = 'Time limit per execution';
$string['memory_limit']         = 'Memory limit';
$string['seconds']              = '{$a} seconds';
$string['submission_settings']  = 'Submission Settings';
$string['max_attempts']         = 'Maximum attempts';
$string['unlimited']            = 'Unlimited';
$string['grading_method']       = 'Grading method';
$string['grading_best']         = 'Best attempt';
$string['grading_last']         = 'Last attempt';
$string['grading_first']        = 'First attempt';
$string['grading_average']      = 'Average of attempts';
$string['testcases_visible']    = 'Show test cases to students';
$string['testcases_visible_help'] = 'If enabled, students can see the name, input, and expected output of each test case. Cases marked as "hidden" are never visible.';
$string['allow_submissions_from_date'] = 'Allow submissions from';
$string['duedate']              = 'Due date';
$string['cutoffdate']           = 'Cut-off date (no submissions accepted after this date)';
$string['languages']            = 'Languages';

$string['testcases']            = 'Test Cases';
$string['testcase']             = 'Test case';
$string['add_testcase']         = '+ Add test case';
$string['tc_name']              = 'Name';
$string['tc_stdin']             = 'Input (stdin)';
$string['tc_expected_output']   = 'Expected output';
$string['tc_points']            = 'Points';
$string['tc_hidden']            = 'Hidden';
$string['hidden_testcase']      = 'Hidden case';

$string['last_submission']      = 'Last submission';
$string['grade']                = 'Grade';
$string['attempts']             = 'Attempts';
$string['pending']              = 'Pending';
$string['past_due']             = '⚠️ The due date has passed. You can still submit, but it may affect your grade.';
$string['past_cutoff']          = '❌ The activity is closed. No more submissions are accepted.';

$string['submissions']          = 'Submissions';
$string['statistics']           = 'Statistics';
$string['preview']              = 'Preview';
$string['student']              = 'Student';
$string['submitted']            = 'Submitted';
$string['language']             = 'Language';
$string['tests_passed']         = 'Tests passed';
$string['actions']              = 'Actions';
$string['view']                 = 'View';
$string['no_submissions_yet']   = 'No submissions yet for this activity.';
$string['preview_mode_notice']  = 'Preview mode: you are viewing the activity as a student.';
$string['total_students']       = 'Students';
$string['total_submissions']    = 'Total submissions';
$string['avg_grade']            = 'Average grade';
$string['pass_rate']            = 'Pass rate';
$string['testcase_pass_rates']  = 'Test case pass rates';
$string['passed']               = 'Passed';
$string['failed']               = 'Failed';

$string['language_not_allowed'] = 'The selected language is not allowed in this activity.';
$string['code_too_large']       = 'The code is too large (maximum 64KB).';
$string['no_testcases']         = 'This activity has no test cases configured.';
$string['max_attempts_reached'] = 'You have reached the maximum number of allowed attempts.';
$string['submission_saved']     = 'Your submission has been successfully recorded!';
$string['executor_connection_error'] = 'Could not connect to the code execution server. Please contact your administrator.';
$string['executor_timeout']     = 'The execution server took too long to respond. Please try again later.';

$string['admin_settings']       = 'CodeLab Settings';
$string['judge0_api_url']       = 'Judge0 API URL';
$string['judge0_api_url_desc']  = 'Base URL of your Judge0 instance (e.g. https://judge0-ce.p.rapidapi.com or your self-hosted server).';
$string['judge0_api_key']       = 'Judge0 API Key';
$string['judge0_api_key_desc']  = 'Authentication key. For RapidAPI this is the X-RapidAPI-Key. For self-hosted it is the X-Auth-Token.';
$string['judge0_api_host']      = 'RapidAPI Host';
$string['judge0_api_host_desc'] = 'Only needed if using Judge0 via RapidAPI (e.g. judge0-ce.p.rapidapi.com).';
$string['judge0_self_hosted']   = 'Self-hosted instance';
$string['judge0_self_hosted_desc'] = 'Enable this if you are running your own Judge0 server with Docker instead of RapidAPI.';

$string['privacy:metadata:codelab_submissions']              = 'Student code submissions.';
$string['privacy:metadata:codelab_submissions:userid']       = 'The ID of the user who made the submission.';
$string['privacy:metadata:codelab_submissions:code']         = 'The source code submitted by the student.';
$string['privacy:metadata:codelab_submissions:language']     = 'Programming language used.';
$string['privacy:metadata:codelab_submissions:grade']        = 'Grade obtained.';
$string['privacy:metadata:codelab_submissions:timecreated']  = 'Submission creation date.';
$string['privacy:metadata:codelab_submissions:timemodified'] = 'Last modification date.';
$string['privacy:metadata:judge0_api']       = 'Student code and standard input are sent to the external Judge0 server for execution.';
$string['privacy:metadata:judge0_api:code']  = 'The source code being executed.';
$string['privacy:metadata:judge0_api:stdin'] = 'The standard input provided to the program.';
$string['privacy:path:submissions']          = 'Code submissions';
