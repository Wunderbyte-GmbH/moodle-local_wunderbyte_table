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

/**
 * The Wunderbyte table class is an extension of the tablelib table_sql class.
 *
 * @package local_wunderbyte_table
 * @copyright 2024 Wunderbyte Gmbh <info@wunderbyte.at>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_wunderbyte_table\local\settings;

use local_wunderbyte_table\wunderbyte_table;

/**
 * Handles the settings of the table.
 * @package local_wunderbyte_table
 */
class tablesettings {

    /**
     * This returns the settings like they were initially programmed for the specific table.
     *
     * @param wunderbyte_table $table
     * @return array
     */
    public static function return_initial_settings(wunderbyte_table $table) {

        $tablesettings['filtersettings'] = $table->subcolumns['datafields'];

        return $tablesettings;
    }

    /**
     * Find filterjson for user. If a userid is transmitted...
     * ... we first look for an individual setting for the user.
     * If there is no individual setting for the specific user, we fall back to the general one.
     * If the userid is precisely 0, we always get the general one.
     * @param int $id
     * @param string $hash
     * @param int $userid
     * @return string
     * @throws dml_exception
     */
    public static function return_jsontablesettings_from_db(int $id = 0, string $hash = '', int $userid = -1) {

        global $DB;

        if (empty(get_config('local_wunderbyte_table', 'savesettingstodb'))) {
            return '{}';
        }

        // When the userid is 0, this is the general setting.
        $searcharray = [0];
        if ($userid > 0) {
            $searcharray[] = $userid;

            $orderby = "ORDER BY userid DESC
                        LIMIT 1";
        } else {
            $orderby = '';
        }

        list($inorequal, $params) = $DB->get_in_or_equal($searcharray, SQL_PARAMS_NAMED);

        if (!empty($hash)) {
            $params['hash'] = $hash;
            $where = "hash = :hash";
        } else if (!empty($id)) {
            $params['id'] = $id;
            $where = "id = :id";
        }

        $sql = "SELECT jsonstring
                FROM {local_wunderbyte_table}
                WHERE userid $inorequal AND $where
                $orderby ";

        $json = $DB->get_field_sql($sql, $params);

        return $json ?? '{}';
    }
}
