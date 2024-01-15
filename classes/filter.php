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
            $key = str_replace(' ', '', $table->uniqueid);

            // This is the cachekey at a moment when sql->where and sql->filter are not yet joined.
            $cachekey = $table->create_cachekey(true);
            $key = $key . $cachekey . $lang . '_filterjson';
            $totalrecordskey = $key . '_totalrecords';

            $cache = \cache::make($table->cachecomponent, $table->rawcachename);

            // See if we have the filter json.
            $table->filterjson = $cache->get($key);

            if (!$table->filterjson) {
                // Now we create the filter json from the unfiltered json.
                // Todo: This can be relayed to an ad hoc task or delegated to an ajax call...
                // ... to further improve performance.
                $table->filterjson = self::return_filterjson($table);
                $cache->set($key, $table->filterjson);
                $cache->set($totalrecordskey, $table->totalrecords);
            }
        }
    }

    /**
     * Returns a json for rendering the filter elements.
     * @param wunderbyte_table $table
     * @return string
     * @throws dml_exception
     */
    public static function return_filterjson(wunderbyte_table $table) {

        $filtercolumns = [];

        // We have stored the columns to filter in the subcolumn "datafields".
        if (!isset($table->subcolumns['datafields'])) {
            return '';
        }

        // Here, we create the filter first like this:
        // For every field we want to filter for, we look in our rawdata...
        // ... to fetch all the available values once.
        foreach ($table->subcolumns['datafields'] as $key => $value) {

            // Instead of, like previously, fetching rawdata once and iterating multiple times over it, we make another sql.
            // We just use the distinct method.

            // We won't generate a filter for the id column, but it will be present because we need it as dataset.
            if (strtolower($key) == 'id') {
                continue;
            }

            if (isset($value['datepicker'])) {
                $filtercolumns[$key] = 'datepicker';
                continue;
            } else if (isset($value['hourlist'])) {
                $filtercolumns[$key] = 'hourlist';
                $rawdata = self::get_db_filter_column_hours($table, $key);
            } else {
                $rawdata = self::get_db_filter_column($table, $key);
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
            if (isset($table->subcolumns['datafields'][$fckey]['localizedname'])) {
                $localizedname = $table->subcolumns['datafields'][$fckey]['localizedname'];
                unset($table->subcolumns['datafields'][$fckey]['localizedname']);
            } else {
                $localizedname = $fckey;
            }

            $categoryobject = [
                'name' => $localizedname, // Localized name.
                'columnname' => $fckey, // The column name.
                'collapsed' => 'collapsed',
            ];

            if (is_string($values) && $values === 'datepicker') {

                $datepickerarray = $table->subcolumns['datafields'][$fckey];

                foreach ($datepickerarray['datepicker'] as $labelkey => $object) {

                    if (!isset($object['columntimestart'])) {
                        $defaulttimestamp = $datepickerarray['datepicker'][$labelkey]['defaultvalue'];

                        $datepickerobject = [
                            'label' => $labelkey,
                            'operator' => $datepickerarray['datepicker'][$labelkey]['operator'],
                            'timestamp' => $defaulttimestamp,
                            'datereadable' => $defaulttimestamp === 'now' ? 'now' : date('Y-m-d', $defaulttimestamp),
                            'timereadable' => $defaulttimestamp === 'now' ? 'now' : date('H:i', $defaulttimestamp),
                            'checkboxlabel' => $datepickerarray['datepicker'][$labelkey]['checkboxlabel'],
                        ];

                    } else { // Inbetween Filter applied.
                        // Prepare the array for output.
                        if (empty($datepickerarray['datepicker'][$labelkey]['possibleoperations'])) {
                            $datepickerarray['datepicker'][$labelkey]['possibleoperations'] =
                                ['within', 'overlapboth', 'overlapstart', 'overlapend', 'before', 'after', 'flexoverlap'];
                        }
                        $operationsarray = array_map(fn($y) => [
                            'operator' => $y,
                            'label' => get_string($y, 'local_wunderbyte_table'),
                        ], $datepickerarray['datepicker'][$labelkey]['possibleoperations']);

                        $datepickerobject = [
                            'label' => $labelkey,
                            'startcolumn' => $datepickerarray['datepicker'][$labelkey]['columntimestart'],
                            'starttimestamp' => $datepickerarray['datepicker'][$labelkey]['defaultvaluestart'],
                            'startdatereadable' => $datepickerarray['datepicker'][$labelkey]['defaultvaluestart'] === 'now' ?
                                'now' : date('Y-m-d', $datepickerarray['datepicker'][$labelkey]['defaultvaluestart']),
                            'starttimereadable' => $datepickerarray['datepicker'][$labelkey]['defaultvaluestart'] === 'now' ?
                                'now' : date('H:i', $datepickerarray['datepicker'][$labelkey]['defaultvaluestart']),
                            'endcolumn' => $datepickerarray['datepicker'][$labelkey]['columntimeend'],
                            'endtimestamp' => $datepickerarray['datepicker'][$labelkey]['defaultvalueend'],
                            'enddatereadable' => $datepickerarray['datepicker'][$labelkey]['defaultvalueend'] === 'now' ?
                                'now' : date('Y-m-d', $datepickerarray['datepicker'][$labelkey]['defaultvalueend']),
                            'endtimereadable' => $datepickerarray['datepicker'][$labelkey]['defaultvalueend'] === 'now' ?
                                'now' : date('H:i', $datepickerarray['datepicker'][$labelkey]['defaultvalueend']),
                            'checkboxlabel' => $datepickerarray['datepicker'][$labelkey]['checkboxlabel'],
                            'possibleoperations' => $operationsarray, // Array.
                        ];
                    }

                    $categoryobject['datepicker']['datepickers'][] = $datepickerobject;
                }

            } else if (is_array($values)) {
                // We might need to explode values, because of a multi-field.
                if (isset($table->subcolumns['datafields'][$fckey]['explode'])
                    || self::check_if_multi_customfield($fckey)) {

                    // We run through the array of values and explode each item.
                    foreach ($values as $keytoexplode => $valuetoexplode) {

                        $separator = $table->subcolumns['datafields'][$fckey]['explode'] ?? ',';

                        $explodedarray = explode($separator, $keytoexplode);

                        // Only if we have more than one item, we unset key and insert all the new keys we got.
                        if (count($explodedarray) > 1) {
                            // Run through all the keys.
                            foreach ($explodedarray as $explodeditem) {

                                // Make sure we don't have any empty values.
                                $explodeditem = trim($explodeditem);

                                if (empty($explodeditem)) {
                                    continue;
                                }

                                $values[$explodeditem] = true;
                            }
                            // We make sure the strings with more than one values are not treated anymore.
                            unset($values[$keytoexplode]);
                        }
                    }

                    unset($table->subcolumns['datafields'][$fckey]['explode']);
                }

                // If we have JSON, we need special treatment.
                if (!empty($table->subcolumns['datafields'][$fckey]['jsonattribute'])) {
                    $valuescopy = $values;
                    $values = [];

                    // We run through the array of values containing the JSON strings.
                    foreach ($valuescopy as $jsonstring => $boolvalue) {
                        // Convert into an array, so we can handle items with multiple objects.
                        $jsonstring = '[' . $jsonstring . ']';
                        $jsonarray = json_decode($jsonstring);

                        foreach ($jsonarray as $jsonobj) {
                            if (empty($jsonobj)) {
                                continue;
                            }
                            // We only want to show the attribute of the JSON which is relevant for the filter.
                            $searchattribute = $jsonobj->{$table->subcolumns['datafields'][$fckey]['jsonattribute']};
                            $values[$searchattribute] = true;
                        }
                    }

                    unset($table->subcolumns['datafields'][$fckey]['json']);
                }

                // We have to check if we have a sortarray for this filtercolumn.
                if (isset($table->subcolumns['datafields'][$fckey])
                            && count($table->subcolumns['datafields'][$fckey]) > 0) {

                                    $sortarray = $table->subcolumns['datafields'][$fckey];
                } else {
                    $sortarray = null;
                }

                // First we create our sortedarray and add all values in the right order.
                if ($sortarray != null) {
                    $sortedarray = [];
                    foreach ($sortarray as $sortkey => $sortvalue) {
                        if (isset($values[$sortkey])) {
                            $sortedarray[$sortvalue] = $sortkey;

                            unset($values[$sortkey]);
                        }
                    }

                    // Now we make sure we havent forgotten any values.
                    // If so, we sort them and add them at the end.
                    if (count($values) > 0) {
                        // First sort the values first.
                        ksort($values);

                        foreach ($values as $unsortedkey => $unsortedvalue) {
                            $sortedarray[$unsortedkey] = true;
                        }
                    }

                    // Finally, we pass the sorted array to the values back.
                    $values = $sortedarray;
                } else {
                    $values = array_combine(array_keys($values), array_keys($values));
                }

                foreach ($values as $valuekey => $valuevalue) {

                    $itemobject = [
                        // We do not want to show html entities, so replace &amp; with &.
                        'key' => str_replace("&amp;", "&", $valuekey),
                        'value' => $valuevalue === true ? $valuekey : $valuevalue,
                        'category' => $fckey,
                    ];

                    // Count may not be used, so we have an extra check.
                    if (!empty($filtercolumns[$fckey][$valuevalue])) {
                        $itemobject['count'] = $filtercolumns[$fckey][$valuevalue];
                    }

                    $categoryobject['default']['values'][$valuekey] = $itemobject;
                }

                if (!isset($categoryobject['default']) || count($categoryobject['default']['values']) == 0) {
                    continue;
                }

                if ($sortarray == null) {
                    // If we didn't sort otherwise, we do it now.
                    ksort($categoryobject['default']['values']);
                }

                // Make the arrays mustache ready, we have to jump through loops.
                $categoryobject['default']['values'] = array_values($categoryobject['default']['values']);
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
    private static function check_if_multi_customfield($columnname) {
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
    private static function get_db_filter_column(wunderbyte_table $table, string $key) {

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
    private static function get_db_filter_column_hours(wunderbyte_table $table, string $key) {

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
