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

require_once("../../config.php");

global $CFG, $PAGE;

require_login();

require_once($CFG->dirroot . '/local/wunderbyte_table/classes/wunderbyte_table.php');

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url('/index.php');

$download = optional_param('download', '', PARAM_ALPHA);

$table = new wunderbyte_table('uniqueid');

$table->is_downloading($download, 'test', 'testing123');

if (!$table->is_downloading()) {
    // Only print headers if not asked to download data
    // Print the page header
    $PAGE->set_title('Testing');
    $PAGE->set_heading('Testing table class');
    $PAGE->navbar->add('Testing table class', new moodle_url('/test.php'));
    echo $OUTPUT->header();
}

// Work out the sql for the table.
$table->set_sql('*', "{user}", 'id<20');

$table->define_baseurl("$CFG->wwwroot/test.php");
echo '<div class="test">';
$table->outwithajax(6, false);
echo '</div>';
$table->baseurl = "$CFG->wwwroot/download.php";
/*$table2 = new wunderbyte_table('uniqueid1123');

$table2->is_downloading($download, 'test', 'testing123');


// Work out the sql for the table.
$table2->set_sql('*', "{user}", '1=1');

$table2->define_baseurl("$CFG->wwwroot/test1.php");

$table2->outwithajax(20, true);


$table3 = new wunderbyte_table('uniqueid1adsaasd123');

$table3->is_downloading($download, 'test12', 'testing12312');


// Work out the sql for the table.
$table3->set_sql('*', "{user}", '1=1');

$table3->define_baseurl("$CFG->wwwroot/test2.php");


echo '
<div class="mt-4">
<button type="button" class="btn btn-primary" data-toggle="modal" data-target="#exampleModal">
  Launch demo modal
</button>
</div>
<!-- Modal -->
<div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel">Modal title</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">';
      $table3->outwithajax(3, true);
echo '
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary">Save changes</button>
      </div>
    </div>
  </div>
</div>
'; */

if (!$table->is_downloading()) {
    echo $OUTPUT->footer();
}
