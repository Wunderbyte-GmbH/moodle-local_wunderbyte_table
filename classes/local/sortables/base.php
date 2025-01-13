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

use coding_exception;
use local_wunderbyte_table\filter;
use local_wunderbyte_table\wunderbyte_table;
use moodle_exception;
use MoodleQuickForm;
use stdClass;

/**
 * Wunderbyte table class is an extension of table_sql.
 */
abstract class base {
    /**
     * @var string columnidentifier
     */
    protected string $columnidentifier = '';

    /**
     * @var string localizedstring
     */
    protected string $localizedstring = '';

    /**
     * Property to indicate if class has implemented a callback
     *
     * @var bool
     */
    public $hascallback = false;

    /**
     * Expected value.
     *
     * @var string
     */
    public $expectedvalue;

    /**
     * Override Cache name.
     *
     * @var string
     */
    public $rawcachename;

    /**
     * Override Cache component.
     *
     * @var string
     */
    public $cachecomponent;


    /**
     * Callable function
     *
     * @var stdClass
     */
    private $sql = null;

    /**
     * Set the column which should be filtered and possibly localize it.
     * @param string $columnidentifier
     * @param string $localizedstring
     * @return void
     */
    public function __construct(
        string $columnidentifier,
        string $localizedstring = ''
    ) {

        $this->columnidentifier = $columnidentifier;
        $this->localizedstring = empty($localizedstring) ? $columnidentifier : $localizedstring;
    }

    /**
     * Add the filter to the array.
     *
     * @param array $sortables
     *
     * @return void
     *
     */
    public function add_sortable(array &$sortables) {
        $sortables[$this->columnidentifier] = $this->localizedstring;
    }

    /**
     * Getter for column identifier string.
     *
     * @return string
     *
     */
    public function return_columnidentifier() {
        return $this->columnidentifier;
    }

    /**
     * Add the sql needed in order to perform the necessary sorting via sql
     *
     * @param string $select
     * @param string $from
     * @param string $where
     *
     * @return void
     *
     */
    public function define_sql(string $select, string $from, string $where) {

        $this->sql = (object)[
            'select' => $select,
            'from' => $from,
            'where' => $where,
        ];
    }

    /**
     * Possibility to return the whole sql object.
     *
     * @return stdClass
     *
     */
    public function get_sql() {
        return $this->sql;
    }

    /**
     * Function to override cache only when a given sortable is applied.
     * This is useful when a sortable defines additional sql which is not needed in other cases.
     * It allows to add complex sql sortables only when needed.
     * With this method, we support the separte caching cycles for different sortables.
     *
     * @param string $componentname
     * @param string $rawcachename
     * @return void
     */
    public function define_cache(string $componentname, string $rawcachename) {

        if ($rawcachename && $componentname) {
            $this->cachecomponent = $componentname;
            $this->rawcachename = $rawcachename;
        }
    }

    /**
     * This method adds the necessary sql pieces to be able to apply this sorting.
     *
     * @param wunderbyte_table $table
     *
     * @return void
     *
     */
    public function apply_sorting(wunderbyte_table &$table) {
        // Do nothing in base class.
    }
}
