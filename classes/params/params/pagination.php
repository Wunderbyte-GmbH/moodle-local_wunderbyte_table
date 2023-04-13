<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Demofile to see how wunderbyte_table works.
 *
 * @package     local_wunderbyte_table
 * @copyright   2023 Wunderbyte GmbH <info@wunderbyte.at>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

 class Pagination implements Param {


    public $enabled = false;
    public $somevalue = 0;
    public function __construct(bool $enabled, int $somevalue) {
        $this->enabled = $enabled;
        $this->somevalue = $somevalue;
    }

    /**
     * Summary of accept
     * @param visitor $v
     * @return void
     */
    public function accept(visitor $v) {
        $v->visitPagination($this);
    }



    public function returnvalue() {
        if($this->enabled) {
            return "datastring pagination " . $this->somevalue;
        } else {
            return "";
        };
    }

 }