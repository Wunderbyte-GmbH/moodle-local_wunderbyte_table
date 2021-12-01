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
 * Testfile to see how wunderbyte_table works.
 *
 * @package     local_wunderbyte_table
 * @copyright   2021 Wunderbyte GmbH <info@wunderbyte.at>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_wunderbyte_table\wunderbyte_table;

require_once(__DIR__ . '/../../config.php');
require_login();

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url('/test.php');

$download = optional_param('download', '', PARAM_ALPHA);

$table = new wunderbyte_table('uniqueid');
$table->is_downloading($download, 'test', 'testing123');

if (!$table->is_downloading()) {
    // Only print headers if not asked to download data
    // Print the page header.
    $PAGE->set_title('Testing');
    $PAGE->set_heading('Testing table class');
    $PAGE->navbar->add('Testing table class', new moodle_url('/test.php'));
    echo $OUTPUT->header();
}

// Work out the sql for the table.
$table->set_sql('*', "{booking_options}", '1=1');

$table->define_baseurl("$CFG->wwwroot/test.php");

// TODO: Override column headers.

$table->addsubcolumns('cardbody', ['id', 'text', 'maxanswers', 'pollurl', 'maxoverbooking']);
$table->addsubcolumns('cardheader', ['text']);
$table->addsubcolumns('cardfooter', ['pollurl']);

// Not in use right now, this is how an image is added to the card.
// With the two lines below, image is shown only in card header.
// The image value should be eg. <img src="..." class="card-img-top d-md-none">.
// $table->addsubcolumns('cardimage', ['image']);

// This adds the width to all normal columns.
$table->addclassestosubcolumns('cardbody', ['columnclass' => 'col-sm']);
// This avoids showing all keys in list view.
$table->addclassestosubcolumns('cardbody', ['columnkeyclass' => 'd-md-none']);

// To hide key in cardheader, set only for special columns.
$table->addclassestosubcolumns('cardheader', ['columnkeyclass' => 'hidden'], ['text']);
$table->addclassestosubcolumns('cardfooter', ['columnkeyclass' => 'hidden'], ['pollurl']);

$table->addclassestosubcolumns('cardbody', ['columnkeyclass' => 'hidden'], ['text']);
$table->addclassestosubcolumns('cardbody', ['columnkeyclass' => 'hidden'], ['pollurl']);

$table->addclassestosubcolumns('cardbody', ['columnkeyclass' => 'hidden'], ['maxoverbooking']);
// To hide value in card body (because this value is shown in header already).
$table->addclassestosubcolumns('cardbody', ['columnvalueclass' => 'd-none d-md-block'], ['text']);
$table->addclassestosubcolumns('cardbody', ['columnvalueclass' => 'd-none d-md-block'], ['pollurl']);
// Set Classes not linked to the individual records or columns but for the container.
$table->settableclass('listheaderclass', 'card d-none d-md-block');
$table->settableclass('cardheaderclass', 'card-header d-md-none bg-warning');
$table->settableclass('cardbodyclass', 'card-body row');
$table->settableclass('cardfooterclass', 'card-footer d-md-none bg-success');

// From here on it's standard table_sql again.

$table->out(40, true);

if (!$table->is_downloading()) {
    echo $OUTPUT->footer();
}
