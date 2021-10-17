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

require_once("../../config.php");

global $CFG, $PAGE, $COURSE;

require_once("../../config.php");
require_login($COURSE);

require("$CFG->libdir/tablelib.php");

use local_wunderbyte_table\wunderbyte_table;

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url('/wunderbyte_table_base.php');

$params = [];
$download = optional_param('download', '', PARAM_ALPHA);

// TODO: SQL data is missing as parameter
$table = new wunderbyte_table('wunderbyte_table');

// TODO: How should we continue here?
/*
// Should be possible to get all this information via this function.
$params = block_booking::get_search_params_from_form((object)$_GET);

$sqldata = block_booking::search_booking_options_manager_get_sqldata($params);
*/

$table->is_downloading($download, 'export');
// $table->set_sql($sqldata['fields'], $sqldata['from'], $sqldata['where'], $sqldata['params']);
// $table->out(40, true);
