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
 * English plugin strings are defined here.
 *
 * @package     local_wunderbyte_table
 * @category    string
 * @copyright   2023 Wunderbyte GmbH <info@wunderbyte.at>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Wunderbyte Table';
$string['loading'] = 'Loading...';
$string['search'] = 'Search';
$string['sortby'] = 'Sort by...';
$string['changesortorder'] = "Change sort order";
$string['orderup'] = "Sorted from A to Z";
$string['orderdown'] = "Sorted from Z to A";

$string['cachedef_cachedrawdata'] = "Wunderbyte standard cache";

$string['noentriesfound'] = "No entries found";

$string['reload'] = "Reload table";
$string['print'] = "Download table";
$string['downloadas'] = "Download table data as";

// Cache.
$string['cachedef_cachedfulltable'] = 'Cached full table';

$string['countlabel'] = '{$a->filteredrecords} of {$a->totalrecords} records found';

$string['checkallcheckbox'] = "Check all";
$string['tableheadercheckbox'] = '<input type="checkbox" class="tableheadercheckbox">';
$string['functiondoesntexist'] = "Function of action button doesn\'t exist.";

// Test and example strings.
$string['deletedatatitle'] = 'Do you really want to delete this data?';
$string['deletedatabody'] = 'You are about to submit this data: <br> "{$a->data}"';
$string['deletedatasubmit'] = 'Delete';

$string['generictitle'] = 'Do you really want to treat this data?';
$string['genericbody'] = 'You are about to treat this rows: <br> "{$a->data}"';
$string['noselectionbody'] = 'You are about make an action';
$string['genericsubmit'] = 'Confirm';

$string['somethingwentwrong'] = 'Something went wrong. Please contact your admin.';
$string['nocheckboxchecked'] = 'No checkbox checked';
$string['checkbox'] = 'Checkbox';

$string['pagelabel'] = 'Show {$a} rows';

$string['fullname'] = 'Full Name';
$string['shortname'] = 'Short Name';
$string['action'] = 'Action';

$string['table1name'] = 'Users';
$string['table2name'] = 'Course';
$string['table3name'] = 'Course_Modules';
$string['table4name'] = 'Users_InfiniteScroll';
$string['id'] = 'ID';