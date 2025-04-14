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
 * @copyright 2023 Wunderbyte Gmbh <info@wunderbyte.at>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_wunderbyte_table;

use coding_exception;
use core_date;
use DateTime;
use DateTimeZone;
use dml_exception;
use local_wunderbyte_table\local\customfield\wbt_field_controller_info;

/**
 * Wunderbyte table class is an extension of table_sql.
 */
class filter {
    /**
     * Filter creation is an expensive operation which is cached as far as possible.
     * Filter is language specific and tries to serve as many requests as possible.
     * Therefore, we reduce the data which is the base for the sql request as much as possible.
     * @param wunderbyte_table $table
     * @return void
     * @throws coding_exception
     * @throws dml_exception
     */
    public static function create_filter(wunderbyte_table $table) {

        if (!$table->filterjson) {
            // We need to localize the filter for every user.
            $lang = self::current_language();
            $key = $table->tablecachehash . $lang . '_filterjson';

            $table->filterjson = editfilter::get_userspecific_filterjson($table, $key);

            if (!$table->filterjson) {
                // Now we create the filter json from the unfiltered json.
                // Todo: This can be relayed to an ad hoc task or delegated to an ajax call...
                // ... to further improve performance.
                $table->filterjson = self::return_filterjson($table, $key);

                // This needs to be moved below.
                $cache = \cache::make($table->cachecomponent, $table->rawcachename);
                $cache->set($key, $table->filterjson);
            }
        }
    }


    /**
     * Returns a json for rendering the filter elements.
     * @param wunderbyte_table $table
     * @param string $cachekey
     * @return string
     * @throws dml_exception
     */
    public static function return_filterjson(wunderbyte_table $table, string $cachekey) {

        $filtercolumns = [];

        $filtersettings = editfilter::return_filtersettings($table, $cachekey);

        if (empty($filtersettings)) {
            return '';
        }

        // Here, we create the filter first like this:
        // For every field we want to filter for, we look in our rawdata...
        // ... to fetch all the available values once.
        foreach ($filtersettings as $key => $value) {
            // Instead of, like previously, fetching rawdata once and iterating multiple times over it, we make another sql.
            // We just use the distinct method.

            // We won't generate a filter for the id column, but it will be present because we need it as dataset.
            if (strtolower($key) == 'id') {
                // If the id checkbox is not checked, we don't show the filter at all.
                if (empty($value[$key . '_wb_checked'])) {
                    return '';
                }
                continue;
            }

            // If the filter is not checked, we skip it.
            if (empty($value[$key . '_wb_checked'])) {
                continue;
            }

            $rawdata = false;

            $rawdata = $value['wbfilterclass']::get_data_for_filter_options($table, $key);

            // Some filters might want us to continue here.
            if (isset($rawdata['continue'])) {
                continue;
            }

            $filtercolumns[$key] = [];

            foreach ($rawdata as $row) {
                // Do not use empty(...) here because we want to show 0 values.
                if ($row->{$key} === null || $row->{$key} === '') {
                    // Here the check if entries are set.
                    continue;
                }
                if (!isset($filtercolumns[$key][$row->{$key}])) {
                    $filtercolumns[$key][$row->{$key}] = $row->keycount ?? $row->count ?? true;
                }
            }
        }

        $filterjson = ['categories' => []];

        wbt_field_controller_info::instantiate_by_shortnames(array_keys($filtercolumns));

        foreach ($filtercolumns as $fckey => $values) {
            // Special treatment for key localizedname.
            if (isset($filtersettings[$fckey]['localizedname'])) {
                $localizedname = $filtersettings[$fckey]['localizedname'];
                unset($filtersettings[$fckey]['localizedname']);
            } else {
                $localizedname = $fckey;
            }

            $categoryobject = [
                'name' => $localizedname, // Localized name.
                'columnname' => $fckey, // The column name.
                'collapsed' => 'collapsed',
            ];

            if ($classname = $filtersettings[$fckey]['wbfilterclass'] ?? false) {
                $classname::add_to_categoryobject($categoryobject, $filtersettings, $fckey, $values);
                $categoryobject['wbfilterclass'] = $classname;
            }

            $filterjson['categories'][] = $categoryobject;
        }

        // Check if filter display should be hidden on load.
        $filterjson['filterinactive'] = $table->filteronloadinactive;
        $encodedstring = json_encode($filterjson);
        return $encodedstring ? $encodedstring : '';
    }

    /**
     * Checks if a config shortname exists and if so, checks for configdata to see, if it's set to multi.
     *
     * @param string $columnname
     * @return bool
     */
    public static function check_if_multi_customfield($columnname) {
        global $DB;

        $configmulti = $DB->sql_like('configdata', ":mcfparam1");
        $params = [
            'mcfparam1' => '%multiselect\":\"1\"%',
            'mcfparam2' => $columnname,
        ];

        $likecolum = $DB->sql_equal('shortname', ':mcfparam2');

        $sql = "SELECT id
                FROM {customfield_field}
                WHERE $likecolum
                AND $configmulti";

        if (!$DB->record_exists_sql($sql, $params)) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Makes sql requests.
     * @param wunderbyte_table $table
     * @param string $key
     * @return array
     */
    public static function get_db_filter_column(wunderbyte_table $table, string $key) {

        global $DB;

        // The $key param is the name of the table in the column, so we can safely use it directly without fear of injection.
        $sql = " SELECT $key, COUNT($key) as keycount
                FROM {$table->sql->from}
                WHERE {$table->sql->where} AND $key IS NOT NULL
                GROUP BY $key ORDER BY $key ASC";

        $records = $DB->get_records_sql($sql, $table->sql->params);

        if (is_array($records)) {
            // Records are sorted correctly, so we just want to omit all empty strings.
            // We can break as soon as the value is not empty.
            foreach ($records as $k => $v) {
                if ($v->{$key} === '') {
                    unset($records[$k]);
                } else {
                    break;
                }
            }
        }

        // If there are only empty strings, we don't want the filter to show.
        if (
            !$records
            || (reset($records)->{$key} === null
            || reset($records)->{$key} === '')
        ) {
            return [
                'continue' => true,
            ];
        } else {
            return $records;
        }
    }

    /**
     * Save settings of Filter.
     * @param wunderbyte_table $table
     * @param string $cachekey
     * @param array $tablesettings
     * @param bool $onlyinsert
     * @return void
     * @throws coding_exception
     * @throws dml_exception
     */
    public static function save_settings(
        wunderbyte_table $table,
        string $cachekey,
        array $tablesettings,
        bool $onlyinsert = true
    ) {

        global $USER, $DB;

        $sql = $table->get_sql_for_cachekey(true);
        $now = time();

        // We use this to avoid unnecessary check.
        if (!$onlyinsert) {
            if (
                $data = $DB->get_record('local_wunderbyte_table', [
                'hash' => $cachekey,
                'userid' => 0,
                ], 'id, timemodified, jsonstring')
            ) {
                $data->timemodified = $now;
                $data->jsonstring = json_encode($tablesettings);

                $DB->update_record('local_wunderbyte_table', $data);
                return;
            }
        }

        // For testing, we save the filter settings at this point.
        $data = (object)[
            'hash' => $cachekey,
            'tablehash' => $table->tablecachehash,
            'idstring' => $table->idstring,
            'userid' => 0,
            'page' => (string)$table->context->id,
            'jsonstring' => (string) json_encode($tablesettings),
            '\'sql\'' => $sql, // SQL is a reserved keyword in MariaDB, so use quotes.
            'usermodified' => (int)$USER->id,
            'timecreated' => $now,
            'timemodified' => $now,
        ];
        $DB->insert_record('local_wunderbyte_table', $data);
    }

    /**
     * Returns the timezone detla between usertime & gmt.
     * @return int
     */
    public static function get_timezone_offset() {

        $now = new DateTime("now", new DateTimeZone('GMT'));
        $gmttime = $now->format('H');

        $now = new DateTime("now", core_date::get_user_timezone_object());
        $userhour = $now->format('H');

        $delta = $gmttime - $userhour;

        return $delta;
    }

    /**
     * As there seems to be the possibility that current_language() does not return the same as used by getstring...
     * ... this function tries to avoid any problems.
     *
     * @return string
     *
     */
    public static function current_language() {
        // We need to localize the filter for every user.
        $lang = get_string('thislanguage', 'langconfig');
        // Convert the string to lowercase.
        $lowercase = strtolower($lang);
        // Remove any character that is not a-z.
        $lang = preg_replace('/[^a-z]/', '', $lowercase);

        if (empty($lang)) {
            $lang = current_language();
        }
        return $lang;
    }
}
