<?php
// This file is part of Moodle - http://moodle.org/

/**
 * Proveedor de privacidad para GDPR - mod_codelab.
 *
 * @package   mod_codelab
 * @copyright 2026 ProyectoEditorMoodle
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_codelab\privacy;

defined('MOODLE_INTERNAL') || die();

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\deletion_criteria;
use core_privacy\local\request\helper;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider,
    \core_privacy\local\request\core_userlist_provider {

    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table(
            'codelab_submissions',
            [
                'userid'         => 'privacy:metadata:codelab_submissions:userid',
                'code'           => 'privacy:metadata:codelab_submissions:code',
                'language'       => 'privacy:metadata:codelab_submissions:language',
                'grade'          => 'privacy:metadata:codelab_submissions:grade',
                'timecreated'    => 'privacy:metadata:codelab_submissions:timecreated',
                'timemodified'   => 'privacy:metadata:codelab_submissions:timemodified',
            ],
            'privacy:metadata:codelab_submissions'
        );

        $collection->add_external_location_link(
            'judge0_api',
            [
                'code'  => 'privacy:metadata:judge0_api:code',
                'stdin' => 'privacy:metadata:judge0_api:stdin',
            ],
            'privacy:metadata:judge0_api'
        );

        return $collection;
    }

    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();
        $sql = "SELECT ctx.id
                  FROM {context} ctx
                  JOIN {course_modules} cm ON cm.id = ctx.instanceid AND ctx.contextlevel = :contextlevel
                  JOIN {modules} m ON m.id = cm.module AND m.name = 'codelab'
                  JOIN {codelab_submissions} s ON s.codelabid = cm.instance
                 WHERE s.userid = :userid";
        $contextlist->add_from_sql($sql, ['contextlevel' => CONTEXT_MODULE, 'userid' => $userid]);
        return $contextlist;
    }

    public static function get_users_in_context(userlist $userlist): void {
        $context = $userlist->get_context();
        if (!$context instanceof \context_module) {
            return;
        }
        $sql = "SELECT s.userid
                  FROM {codelab_submissions} s
                  JOIN {course_modules} cm ON cm.instance = s.codelabid
                 WHERE cm.id = :cmid";
        $userlist->add_from_sql('userid', $sql, ['cmid' => $context->instanceid]);
    }

    public static function export_user_data(approved_contextlist $contextlist): void {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        $userid = $contextlist->get_user()->id;

        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof \context_module) {
                continue;
            }
            $cm = get_coursemodule_from_id('codelab', $context->instanceid);
            if (!$cm) {
                continue;
            }

            $submissions = $DB->get_records('codelab_submissions', [
                'codelabid' => $cm->instance,
                'userid'    => $userid,
            ]);

            if (!empty($submissions)) {
                writer::with_context($context)->export_data(
                    [get_string('privacy:path:submissions', 'mod_codelab')],
                    (object)['submissions' => array_values($submissions)]
                );
            }
        }
    }

    public static function delete_data_for_all_users_in_context(\context $context): void {
        global $DB;

        if (!$context instanceof \context_module) {
            return;
        }
        $cm = get_coursemodule_from_id('codelab', $context->instanceid);
        if (!$cm) {
            return;
        }

        $submission_ids = $DB->get_fieldset_select(
            'codelab_submissions', 'id', 'codelabid = ?', [$cm->instance]
        );
        if ($submission_ids) {
            $DB->delete_records_list('codelab_submission_results', 'submission_id', $submission_ids);
        }
        $DB->delete_records('codelab_submissions', ['codelabid' => $cm->instance]);
    }

    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        $userid = $contextlist->get_user()->id;

        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof \context_module) {
                continue;
            }
            $cm = get_coursemodule_from_id('codelab', $context->instanceid);
            if (!$cm) {
                continue;
            }

            $submission_ids = $DB->get_fieldset_select(
                'codelab_submissions', 'id',
                'codelabid = ? AND userid = ?',
                [$cm->instance, $userid]
            );
            if ($submission_ids) {
                $DB->delete_records_list('codelab_submission_results', 'submission_id', $submission_ids);
            }
            $DB->delete_records('codelab_submissions', ['codelabid' => $cm->instance, 'userid' => $userid]);
        }
    }

    public static function delete_data_for_users(approved_userlist $userlist): void {
        global $DB;

        $context = $userlist->get_context();
        if (!$context instanceof \context_module) {
            return;
        }
        $cm = get_coursemodule_from_id('codelab', $context->instanceid);
        if (!$cm) {
            return;
        }

        foreach ($userlist->get_userids() as $userid) {
            $submission_ids = $DB->get_fieldset_select(
                'codelab_submissions', 'id',
                'codelabid = ? AND userid = ?',
                [$cm->instance, $userid]
            );
            if ($submission_ids) {
                $DB->delete_records_list('codelab_submission_results', 'submission_id', $submission_ids);
            }
            $DB->delete_records('codelab_submissions', ['codelabid' => $cm->instance, 'userid' => $userid]);
        }
    }
}
