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

require_once("$CFG->libdir/tablelib.php");

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
     * @var string Id of this table.
     */
    public $idstring = '';

    public function __construct($uniqueid) {
        parent::__construct($uniqueid);

        $this->idstring = md5($uniqueid);
    }

    public function outwithajax($pagesize, $useinitialsbar, $downloadhelpbutton = '') {

        global $PAGE, $CFG;

        $encodedtablelib = json_encode($this);

        $base64encodedtablelib = base64_encode($encodedtablelib);

        $output = $PAGE->get_renderer('local_wunderbyte_table');
        $viewtable = new viewtable($this->idstring, $base64encodedtablelib);
        $out .= $output->render_viewtable($viewtable);

        // Include Javascript to enable AJAX calls.
        $PAGE->requires->js_call_amd('local_wunderbyte_table/init', 'init', [$this->idstring]);

    }
}
