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

$string['noentriesfound'] = "No entries found";
$string['couldnotloaddata'] = "Could not load data";

$string['filter'] = 'Filter table';
$string['reload'] = "Reload table";
$string['print'] = "Download table";
$string['downloadas'] = "Download table data as";

$string['norecords'] = "No records found.";

// Capabilities.
$string['wunderbyte_table:canaccess'] = 'Can access Wunderbyte Table';

// Caches.
$string['cachedef_cachedfulltable'] = 'Wunderbyte Table: Cached full table';
$string['cachedef_cachedrawdata'] = "Wunderbyte Table: Default cache";
$string['cachedef_encodedtables'] = 'Wunderbyte Table: Cache for encoded tables';
$string['cachedef_cachedfilters'] = 'Wunderbyte Table: Cache for filters';

// Info messages over table.
$string['countlabel'] = '{$a->filteredrecords} of {$a->totalrecords} records found ';
$string['filtercountmessage'] = '| {$a->filtersum} filter(s) on: {$a->filtercolumns} | ';
$string['showallrecords'] = 'Show all records';

$string['checkallcheckbox'] = "Check all";
$string['tableheadercheckbox'] = '<input type="checkbox" class="tableheadercheckbox">';
$string['functiondoesntexist'] = "Function of action button doesn\'t exist.";

// Example strings.
$string['deletedatatitle'] = 'Do you really want to delete this data?';
$string['deletedatabody'] = 'You are about to submit this data: <br> "{$a->data}"';
$string['deletedatasubmit'] = 'Delete';
$string['adddatabody'] = 'You are about to add a row';

$string['generictitle'] = 'Do you really want to treat this data?';
$string['genericbody'] = 'You are about to treat this rows: <br> "{$a->data}"';
$string['noselectionbody'] = 'You are about make an action';
$string['specialbody'] = 'Action will be applied!';
$string['genericsubmit'] = 'Confirm';

$string['somethingwentwrong'] = 'Something went wrong. Please contact your admin.';
$string['nocheckboxchecked'] = 'No checkbox checked';
$string['checkbox'] = 'Checkbox';
$string['module'] = 'Module';
$string['apply_filter'] = 'Apply Filter';

$string['pagelabel'] = 'Show {$a} rows';

// Filter for timespan.
$string['displayrecords'] = 'Display records';
$string['within'] = 'within';
$string['overlapboth'] = 'overlapping both dates';
$string['overlapstart'] = 'overlapping beginning';
$string['overlapend'] = 'overlapping ending';
$string['before'] = 'before';
$string['after'] = 'after';
$string['startvalue'] = 'Start';
$string['endvalue'] = 'End';
$string['selectedtimespan'] = 'selected timespan';
$string['timespan'] = 'Timespan';
$string['flexoverlap'] = 'overlapping';

$string['change'] = 'Change';
$string['hourlastmodified'] = 'Hour, last time updated';

// Events.
$string['table_viewed'] = 'Table viewed';
$string['table_viewed_desc'] = 'The user viewed the table "{$a}"';
$string['action_executed'] = 'Action executed';
$string['action_executed_desc'] = 'The user executed an action "{$a->methodname}" on the table "{$a->tablename}"';

// Settings.
$string['savesettingstodb'] = 'Save Wunderbyte Table settings to db (Experimental)';
$string['logfiltercaches'] = 'Debug feature: Save queries to DB. Has negative impact on performance. (Experimental)';
$string['allowsearchincolumns'] = 'Erlaube Suche in Spalten mittels ":" (Experimental)';

// Bewlow this line, there are only strings for the demo site.
// Action Buttons demo names.
$string['nmmcns'] = 'NoModal, MultipleCall, NoSelection';
$string['nmscns'] = 'NoModal, SingleCall, NoSelection';
$string['ymmcns'] = '+Modal, MultipleCall, NoSelection';
$string['ymscns'] = '+Modal, SingleCall, NoSelection';
$string['nmmcys'] = 'NoModal, MultipleCall, Selection';
$string['nmscys'] = 'NoModal, SingleCall, Selection';
$string['ymmcys'] = '+Modal, MultipleCall, Selection';
$string['ymscys'] = '+Modal, SingleCall, Selection';

$string['table1name'] = 'Demo table 1';
$string['table2name'] = 'Demo table 2';
$string['table3name'] = 'Demo table 3';
$string['table4name'] = 'Demo table 4';
$string['id'] = 'ID';

// GDPR
$string['privacy:metadata:local_wunderbyte_table'] = 'Store settings for tables';
$string['privacy:metadata:local_wunderbyte_table:id'] = 'Id';
$string['privacy:metadata:local_wunderbyte_table:hash'] = 'Hash';
$string['privacy:metadata:local_wunderbyte_table:tablehash'] = 'Table hash';
$string['privacy:metadata:local_wunderbyte_table:idstring'] = 'ID string';
$string['privacy:metadata:local_wunderbyte_table:userid'] = 'User ID';
$string['privacy:metadata:local_wunderbyte_table:page'] = 'Page';
$string['privacy:metadata:local_wunderbyte_table:jsonstring'] = 'Json string';
$string['privacy:metadata:local_wunderbyte_table:sql'] = 'Sql';
$string['privacy:metadata:local_wunderbyte_table:usermodified'] = 'User modified';
$string['privacy:metadata:local_wunderbyte_table:timecreated'] = 'The time created';
$string['privacy:metadata:local_wunderbyte_table:timemodified'] = 'The time updated';
$string['privacy:metadata:local_wunderbyte_table:count'] = 'Count';
