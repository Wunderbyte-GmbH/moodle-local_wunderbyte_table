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

namespace local_wunderbyte_table\local\sortables\types;
use Closure;
use local_wunderbyte_table\local\sortables\base;
use local_wunderbyte_table\wunderbyte_table;

/**
 * Wunderbyte table class is an extension of table_sql.
 */
class callback extends base {
    /**
     * Property to indicate if class has implemented a callback
     *
     * @var bool
     */
    public $hascallback = true;

    /**
     * Callable function
     *
     * @var string
     */
    private $callbackfunction = null;

    /**
     * Setter for static callbackfunctionname including namespace.
     *
     * @param string $functionname
     *
     * @return void
     *
     */
    public function define_callbackfunction(string $functionname) {
        $this->callbackfunction = $functionname;
    }

    /**
     * Function to filter std Class records by the set callback.
     * We have positiv & negativ filter.
     *
     * @param array $records
     * @param bool $not
     *
     * @return array
     *
     */
    public function sort_by_callback(array $records, $not = false) {

        return $records;
    }
}
