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
 * The Wunderbyte table class is an extension of the tablelib table_sql class
 * and adds wonderful new functionalities.
 *
 * @package local_wunderbyte_table
 * @copyright 2021 onwards Wunderbyte Gmbh <info@wunderbyte.at>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_wunderbyte_table;

defined('MOODLE_INTERNAL') || die();

use gradereport_singleview\local\ui\empty_element;
use moodle_exception;
use table_sql;
use moodle_url;

/**
 * Wunderbyte table class is an extension of table_sql.
 */
class wunderbyte_table extends table_sql
{
    /**
     * Constructor
     * @param int $uniqueid all tables have to have a unique id, this is used
     *      as a key when storing table properties like sort order in the session.
     * @param array $sqldata An associative array with keys ['fields', 'from', 'where', 'params']
     *      to generate the SQL. 'params' is an array itself too.
     * @throws moodle_exception
     */
    function __construct($uniqueid, array $sqldata = []) {
        parent::__construct($uniqueid);

        global $CFG;

        $this->is_downloading(false); // This is necessary for the download button to be shown.
        $this->set_sql($sqldata['fields'], $sqldata['from'], $sqldata['where'], $sqldata['params']);

        if (!empty($sqldata)) {
            $urlparams = [];
            foreach ($sqldata['params'] as $key => $value) {
                $urlparams["$key"] = $value;
            }
            $baseurl = new moodle_url("$CFG->wwwroot/local/wunderbyte_table/wunderbyte_table_base.php", $urlparams);

            $this->define_baseurl($baseurl);
        }
    }
}