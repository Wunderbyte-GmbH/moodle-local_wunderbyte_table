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

namespace local_wunderbyte_table\filters\types;
use local_wunderbyte_table\filter;
use local_wunderbyte_table\filters\base;
use local_wunderbyte_table\wunderbyte_table;

/**
 * Wunderbyte table class is an extension of table_sql.
 */
class standardfilter extends base {

    /**
     * This function takes a key value pair of options.
     * Only if there are actual results in the table, these options will be displayed.
     * The keys are the results, the values are the localized strings.
     * For the standard filter, it's not necessary to provide these options...
     * They will be gathered automatically.
     *
     * @param array $options
     * @return void
     */
    public function add_options(array $options = []) {

        foreach ($options as $key => $value) {
            $this->options[$key] = $value;
        }
    }

    /**
     * Apply the filter of standardfilter class.
     *
     * @param string $filter
     * @param string $columnname
     * @param mixed $categoryvalue
     * @param wunderbyte_table $table
     *
     * @return void
     *
     */
    public function apply_filter(
        string &$filter,
        string $columnname,
        $categoryvalue,
        wunderbyte_table &$table
    ): void {
        global $DB;
        $filtercounter = 1;
        $filter .= " ( ";
        foreach ($categoryvalue as $key => $value) {
            $filter .= $filtercounter == 1 ? "" : " OR ";
            // Apply special filter here.
            if (
                isset($table->subcolumns['datafields'][$columnname]['jsonattribute'])
            ) {
                    $paramsvaluekey = $table->set_params("%" . $value ."%");
                    $filter .= $DB->sql_like("$columnname", ":$paramsvaluekey", false);
            } else if (
                is_numeric($value)
                && isset($table->subcolumns['datafields'][$columnname]['local_wunderbyte_table\filters\types\hourlist'])
            ) {
                // Here we check if it's an hourslist filter.
                $paramsvaluekey = $table->set_params((string) ($value + $delta), false);
                $filter .= filter::apply_hourlist_filter($columnname, ":$paramsvaluekey");
                $delta = filter::get_timezone_offset();
            } else {
                // We want to find the value in an array of values.
                // Therefore, we have to use or as well.
                // First, make sure we have enough params we can use..
                $separator = $table->subcolumns['datafields'][$columnname]['explode'] ?? ",";
                $paramsvaluekey = $table->set_params('%' . $separator . $value . $separator . '%', true);
                $escapecharacter = wunderbyte_table::return_escape_character($value);
                $concatvalue = $DB->sql_concat("'$separator'", $columnname, "'$separator'");
                $filter .= $DB->sql_like("$concatvalue", ":$paramsvaluekey", false, false, false, $escapecharacter);
            }
            $filtercounter++;
        }
        $filter .= " ) ";
    }

}
