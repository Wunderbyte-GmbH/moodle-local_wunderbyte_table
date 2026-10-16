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
 * Behat fixture: a wunderbyte_table with sortable rows (drag and drop reordering).
 *
 * Only used by tests/behat/reordering.feature. Only available while running behat.
 *
 * @package     local_wunderbyte_table
 * @copyright   2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_wunderbyte_table\demo_table;

require_once(__DIR__ . '/../../../../../config.php');

if (!defined('BEHAT_SITE_RUNNING')) {
    die;
}

require_login();
require_capability('moodle/site:config', context_system::instance());

$PAGE->set_context(context_system::instance());
$PAGE->set_url('/local/wunderbyte_table/tests/behat/fixtures/sortablerows.php');
$PAGE->set_title('Sortable rows');
$PAGE->set_heading('Sortable rows');

echo $OUTPUT->header();

$table = new demo_table('sortablerows_1');
$table->add_template_to_switcher('local_wunderbyte_table/twtable_list', 'List', true);

$columns = [
    'id' => 'ID',
    'username' => get_string('username'),
    'firstname' => get_string('firstname'),
];
$table->define_headers(array_values($columns));
$table->define_columns(array_keys($columns));

$table->sortablerows = true;
$table->sort_default_column = 'id';
$table->sort_default_order = SORT_ASC;
$table->set_filter_sql('*', "{user}", 'id > 0', '');
$table->pageable(true);

echo $table->outhtml(10, true);

echo $OUTPUT->footer();
