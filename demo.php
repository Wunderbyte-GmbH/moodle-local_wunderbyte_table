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

use local_wunderbyte_table\output\demo;

require_once(__DIR__ . '/../../config.php');
require_login();

$syscontext = context_system::instance();

// Make sure only an admin can see this.
if (!has_capability('moodle/site:config', $syscontext)) {
    die;
}

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url('/local/wunderbyte_table/demo.php');

echo $OUTPUT->header();

$outputdemo = new demo();

echo $OUTPUT->render_from_template('local_wunderbyte_table/demo', $outputdemo->return_as_array());

echo $OUTPUT->footer();
