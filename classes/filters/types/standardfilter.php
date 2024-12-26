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
     * Property to indicate if class has implemented a callback
     *
     * @var bool
     */
    public $hascallback = false;

    /**
     * Callable function
     *
     * @var callable|null
     */
    public $callback = null;

    /**
     * SQL (including properties field, where, from) to append to table sql.
     *
     * @var \stdClass
     */
    private $sql;

    /**
     * This function adds sql to the table sql.
     *
     * @param wunderbyte_table $table
     *
     * @return void
     *
     */
    public function add_sql(wunderbyte_table $table) {
    }

    /**
     * [Description for define_sql]
     *
     * @param string $field
     * @param string $from
     * @param string $where
     *
     * @return void
     *
     */
    public function define_sql(string $field, string $from, string $where) {
        $sql = [
            'field' => $field,
            'from' => $from,
            'where' => $where,
        ];
        $this->sql = $sql;
    }
}
