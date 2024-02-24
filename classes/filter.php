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
use local_wunderbyte_table\filters\base;
use local_wunderbyte_table\filters\types\datepicker;
use local_wunderbyte_table\filters\types\hourlist;
use local_wunderbyte_table\filters\types\standardfilter;
use local_wunderbyte_table\filters\types\weekdays;

use coding_exception;
use core_component;
use dml_exception;

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
            $lang = current_language();

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
            return;
        }

        $filterclasses = core_component::get_component_classes_in_namespace(
            "local_wunderbyte_table",
            'filters\types'
        );

        // Here, we create the filter first like this:
        // For every field we want to filter for, we look in our rawdata...
        // ... to fetch all the available values once.
        foreach ($filtersettings as $key => $value) {

            // Instead of, like previously, fetching rawdata once and iterating multiple times over it, we make another sql.
            // We just use the distinct method.

            // We won't generate a filter for the id column, but it will be present because we need it as dataset.
            if (strtolower($key) == 'id') {
                continue;
            }

            $rawdata = false;
            foreach ($filterclasses as $classname => $namespace) {

                // Some filters might have a special way of retrieving their options.
                if (isset($value[$classname])) {
                    $rawdata = $classname::get_data_for_filter_options($table, $key);
                    break;
                }
            }
            // Some filters might want us to continue here.
            if (isset($rawdata['continue'])) {
                continue;
            } else if ($rawdata === false) {
                // This is the standard way to optain the filter results.
                $rawdata = base::get_data_for_filter_options($table, $key);
            }

            $filtercolumns[$key] = [];

            foreach ($rawdata as $row) {

                // Do not use empty(...) here because we want to show 0 values.
                if ($row->{$key} === null || $row->{$key} === '') {
                    // Here the check if entries are set.
                    continue;
                }

                if (!isset($filtercolumns[$key][$row->{$key}])) {
                    $filtercolumns[$key][$row->{$key}] = $row->count ?? true;
                }
            }
        }

        $filterjson = ['categories' => []];

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

            // We give every filterclass the chance to take care of adding the array for the template.
            foreach ($filterclasses as $classname => $namespace) {
                $classname::add_to_categoryobject($categoryobject, $filtersettings, $fckey, $values);
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
        $sql = " SELECT $key, COUNT($key)
                FROM {$table->sql->from}
                WHERE {$table->sql->where} AND $key IS NOT NULL
                GROUP BY $key ";

        $records = $DB->get_records_sql($sql, $table->sql->params);

        return $records;
    }

    /**
     * Makes sql requests.
     * @param wunderbyte_table $table
     * @param string $key
     * @return array
     */
    public static function get_db_filter_column_hours(wunderbyte_table $table, string $key) {

        global $DB;

        $databasetype = $DB->get_dbfamily();

        // The $key param is the name of the table in the column, so we can safely use it directly without fear of injection.
        switch ($databasetype) {
            case 'postgres':
                $sql = "SELECT $key, COUNT($key)
                        FROM ( SELECT EXTRACT(HOUR FROM TIMESTAMP 'epoch' + $key * interval '1 second') AS $key
                        FROM {$table->sql->from}
                        WHERE {$table->sql->where} AND $key IS NOT NULL AND $key <> 0) as hourss1
                        GROUP BY $key ";
                break;
            case 'mysql':
                $sql = "SELECT $key, COUNT($key)
                        FROM ( SELECT EXTRACT(HOUR FROM FROM_UNIXTIME($key)) AS $key
                        FROM {$table->sql->from}
                        WHERE {$table->sql->where} AND $key IS NOT NULL AND $key <> 0) as hourss1
                        GROUP BY $key ";
                break;
            default:
                $sql = '';
                break;
        }

        if (empty($sql)) {
            return [];
        }

        $records = $DB->get_records_sql($sql, $table->sql->params);

        return $records;
    }

    /**
     * Apply the filter for postgres & mariadb DB.
     * @param string $fieldname
     * @param string $param
     * @return string
     */
    public static function apply_hourlist_filter(string $fieldname, string $param) {
        global $DB;

        $databasetype = $DB->get_dbfamily();

        // The $key param is the name of the table in the column, so we can safely use it directly without fear of injection.
        switch ($databasetype) {
            case 'postgres':
                $sql = " EXTRACT(HOUR FROM TIMESTAMP 'epoch' + $fieldname * interval '1 second') = $param";
                break;
            default:
                $sql = " EXTRACT(HOUR FROM FROM_UNIXTIME($fieldname)) = $param";
        }

        return $sql;
    }
}
