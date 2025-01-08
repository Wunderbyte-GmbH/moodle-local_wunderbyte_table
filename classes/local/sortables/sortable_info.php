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

namespace local_wunderbyte_table\local\sortables;

use local_wunderbyte_table\wunderbyte_table;

/**
 * Wunderbyte table class is an extension of table_sql.
 */
abstract class sortable_info {
    /**
     * This function applies the sortable classes to the wunderbyte table.
     * This handles the ncessary preparation of the tablelib class as well modifications of the sql.
     *
     * @param wunderbyte_table $table
     *
     * @return [type]
     *
     */
    public static function apply_sortables(wunderbyte_table $table) {

        global $SESSION;

        $sortcolumns = optional_param('tsort', '', PARAM_ALPHANUMEXT);
        if (empty($sortcolumns)) {
            if (isset($SESSION->flextable[$table->uniqueid])) {
                $prefs = $SESSION->flextable[$table->uniqueid];
                $sortcolumns = array_keys($prefs["sortby"]);
            }
        }
        $sortcolumns = !is_array($sortcolumns) ? explode(',', $sortcolumns) : $sortcolumns;

        // There is another way how we can now about sorting.
        foreach ($sortcolumns as $sortcolumn) {
            if (isset($table->sortables[$sortcolumn])) {
                $table->sortables[$sortcolumn]->apply_sorting($table);
            }
        }
    }

}
