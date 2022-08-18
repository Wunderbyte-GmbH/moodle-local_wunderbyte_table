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

$syscontext = context_system::instance();

// Make sure only an admin can see this.
if (!has_capability('moodle/site:config', $syscontext)) {
    die;
}

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url('/local/wunderbyte_table/test.php');

$download = optional_param('download', '', PARAM_ALPHA);

$table = new wunderbyte_table('uniqueid');
$table->is_downloading($download, 'test', 'testing123');

$table->add_subcolumns('cardbody', ['id', 'fullname', 'shortname', 'idnumber', 'format']);
$table->add_subcolumns('cardheader', ['fullname']);
$table->add_subcolumns('cardfooter', ['shortname']);

// Here you can use add_subcolumns with 'cardfooter" to show content in cardfooter.

// Not in use right now, this is how an image is added to the card.
// With the two lines below, image is shown only in card header.
// The image value should be eg. <img src="..." class="card-img-top d-md-none">.
// Use add_subcolumns with 'cardimage" and image like shown above.

// This adds the width to all normal columns.
$table->add_classes_to_subcolumns('cardbody', ['columnclass' => 'col-sm']);
// This avoids showing all keys in list view.
$table->add_classes_to_subcolumns('cardbody', ['columnkeyclass' => 'd-md-none']);

// Override naming for columns. one could use getstring for localisation here.
$table->add_classes_to_subcolumns('cardbody', ['keystring' => 'Moodle id'], ['id']);

// To hide key in cardheader, set only for special columns.
$table->add_classes_to_subcolumns('cardheader', ['columnkeyclass' => 'hidden'], ['fullname']);

// Keys are already hidden by for lists, but here we also hide some keys for cards.
$table->add_classes_to_subcolumns('cardbody', ['columnkeyclass' => 'hidden'], ['fullname']);
$table->add_classes_to_subcolumns('cardbody', ['columnkeyclass' => 'hidden'], ['shortname']);
// To hide value in card body (because this value is shown in header already).
$table->add_classes_to_subcolumns('cardbody', ['columnvalueclass' => 'd-none d-md-block'], ['fullname']);
// Set Classes not linked to the individual records or columns but for the container.
$table->set_tableclass('listheaderclass', 'card d-none d-md-block');
$table->set_tableclass('cardheaderclass', 'card-header d-md-none bg-warning');
$table->set_tableclass('cardbodyclass', 'card-body row');

if (!$table->is_downloading()) {
    // Only print headers if not asked to download data
    // Print the page header.
    $PAGE->set_title('Testing');
    $PAGE->set_heading('Testing table class');
    $PAGE->navbar->add('Testing table class', new moodle_url('/local/wunderbyte_table/test.php'));
    echo $OUTPUT->header();
}

$table->define_filtercolumns(['id', 'category', 'format']);

// Work out the sql for the table.
$table->set_sql('*', "{course}", '1=1');

$baseurl = new moodle_url(
    $_SERVER['REQUEST_URI'],
    $_GET
);

$table->define_baseurl($baseurl->out());

$table->tabletemplate = 'local_wunderbyte_table/table_card';

$table->infinitescroll = 50;

echo $table->out(10, true);

if (!$table->is_downloading()) {
    echo $OUTPUT->footer();
}
