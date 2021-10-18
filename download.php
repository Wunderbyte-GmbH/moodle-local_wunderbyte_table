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
 * Simple file test.php to drop into root of Moodle installation.
 * This is the skeleton code to print a downloadable, paged, sorted table of
 * data from a sql query.
 */

use local_wunderbyte_table\wunderbyte_table;
// use table_sql;

require_once("../../config.php");

global $CFG, $PAGE;

require_login();

require_once($CFG->dirroot . '/local/wunderbyte_table/classes/wunderbyte_table.php');

$download = optional_param('download', '', PARAM_ALPHA);
$encodedtable = optional_param('encodedtable', '', PARAM_ALPHA);

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url('/download.php');

$lib = wunderbyte_table::decode_table_settings($params['encodedtable']);

$table = new $lib->classname($lib->uniqid);

$table->update_from_json($lib);

foreach ($params as $key => $value) {
    $_POST[$key] = $value;
}

$table->is_downloading($download, 'download', 'download');
$table->printtable($table->pagesize, true);
