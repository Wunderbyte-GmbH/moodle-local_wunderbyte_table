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

namespace local_wunderbyte_table\filters;

use local_wunderbyte_table\wunderbyte_table;
use local_wunderbyte_table\editfilter;
use local_wunderbyte_table\filter;

/**
 * Handles the filter classes.
 * @package local_wunderbyte_table
 */
abstract class filtersettings {
    /**
     * Validation.
     * @param string $filteredcolumnform
     */
    public static function get_filtersettings($encodedtable) {
        $table = wunderbyte_table::instantiate_from_tablecache_hash($encodedtable);
        $lang = filter::current_language();
        $key = $table->tablecachehash . $lang . '_filterjson';
        return editfilter::return_filtersettings($table, $key);
    }
}