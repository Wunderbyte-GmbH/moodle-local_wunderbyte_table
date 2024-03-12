<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace local_wunderbyte_table\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\core_user_data_provider;
use core_privacy\local\request\core_userlist_provider;
use core_privacy\local\request\subsystem\plugin_provider;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

/**
 * Privacy Subsystem for local_wunderbyte_table implementing null_provider.
 *
 * @copyright  2024 Owen Herbert <owenherbert@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements \core_privacy\local\metadata\provider,
        plugin_provider,
        core_user_data_provider,
        core_userlist_provider {

    /**
     * List of tables.
     */
    private static $tables = [
            'local_wunderbyte_table'
    ];

    /**
     * Return the fields which contain personal data.
     *
     * @param collection $collection a reference to the collection to use to store the metadata.
     * @return collection the updated collection of metadata items.
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table(
                'local_wunderbyte_table',
                [
                        'id' => 'privacy:metadata:local_wunderbyte_table:id',
                        'hash' => 'privacy:metadata:local_wunderbyte_table:hash',
                        'tablehash' => 'privacy:metadata:local_wunderbyte_table:tablehash',
                        'idstring' => 'privacy:metadata:local_wunderbyte_table:idstring',
                        'userid' => 'privacy:metadata:local_wunderbyte_table:userid',
                        'page' => 'privacy:metadata:local_wunderbyte_table:page',
                        'jsonstring' => 'privacy:metadata:local_wunderbyte_table:jsonstring',
                        'sql' => 'privacy:metadata:local_wunderbyte_table:sql',
                        'usermodified' => 'privacy:metadata:local_wunderbyte_table:usermodified',
                        'timecreated' => 'privacy:metadata:local_wunderbyte_table:timecreated',
                        'timemodified' => 'privacy:metadata:local_wunderbyte_table:timemodified',
                        'count' => 'privacy:metadata:local_wunderbyte_table:count',
                ],
                'privacy:metadata:local_wunderbyte_table'
        );
        return $collection;
    }


    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param int $userid the userid.
     * @return contextlist the list of contexts containing user info for the user.
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();
        $contextlist->add_system_context();
        return $contextlist;
    }

    /**
     * Export personal data for the given approved_contextlist. User and context information is contained within the contextlist.
     *
     * @param approved_contextlist $contextlist a list of contexts approved for export.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;
        $userid = $contextlist->get_user()->id;
        foreach ($contextlist as $context) {
            if ($context->contextlevel == CONTEXT_SYSTEM) {
                $i = 0;
                $data = [];

                foreach (self::$tables as $table) {
                    $rows = $DB->get_records($table, ['userid' => $userid]);
                    if (count($rows) > 0) {
                        foreach ($rows as $row) {
                            $data[$i] = $row;
                            $i++;
                        }
                    }
                }

                writer::with_context($context)->export_data(
                        [get_string('privacy:metadata:local_wunderbyte_table', 'local_wunderbyte_table')],
                        (object) $data);
            }
        }
    }

    /**
     * Delete all data for all users in the specified context.
     *
     * @param \context $context the context to delete in.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;
        if ($context->contextlevel == CONTEXT_SYSTEM) {
            foreach (self::$tables as $table) {
                // Delete the records from table
                $DB->delete_records($table);
            }
        }
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist a list of contexts approved for deletion.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;
        $userid = $contextlist->get_user()->id;

        foreach ($contextlist as $context) {
            if ($context->contextlevel == CONTEXT_SYSTEM) {
                foreach (self::$tables as $table) {
                    $DB->delete_records($table, ['userid' => $userid]);
                }
            }
        }
    }

    /**
     * Get the list of users who have data within a context.
     *
     * @param userlist $userlist The userlist containing the list of users who have data in this context/plugin combination.
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();
        if ($context->contextlevel == CONTEXT_SYSTEM) {
            foreach (self::$tables as $table) {
                $sql = "SELECT userid FROM {$table}";
                $userlist->add_from_sql('userid', $sql, []);
            }
        }

    }

    /**
     * Delete multiple users within a single context.
     *
     * @param approved_userlist $userlist The approved context and user information to delete information for.
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        $users = $userlist->get_users();
        foreach ($users as $user) {
            $contextlist = new approved_contextlist($user, 'local_wunderbyte_table', [CONTEXT_SYSTEM]);
            self::delete_data_for_user($contextlist);
        }
    }
}
