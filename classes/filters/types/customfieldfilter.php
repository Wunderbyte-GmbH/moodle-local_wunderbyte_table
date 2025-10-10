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
 * @author Mahdi Poustini
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_wunderbyte_table\filters\types;
use local_wunderbyte_table\filters\base;
use local_wunderbyte_table\wunderbyte_table;

/**
 * Wunderbyte table class is an extension of table_sql.
 */
class customfieldfilter extends standardfilter {
    /**
     * Apply the filter of hierachical class. Logic of Standardfilter can be applied here.
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
        [$column, $subquerytablename, $subquerycolumnname] = explode(',', $columnname);
        $filter .= " ( ";
        $filter .= "{$column}
                        IN (
                            SELECT sqtable.id
                            FROM {$subquerytablename} sqtable
                        ";
        foreach ($categoryvalue as $key => $value) {
            $filter .= $filtercounter == 1 ? " WHERE " : " OR ";
            $filter .= "sqtable.{$subquerycolumnname} LIKE '%{$value}%' ";
            $filtercounter++;
        }
        $filter .= ") ) ";
    }
}
