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
use table_sql;

require_once("../../config.php");

global $CFG, $PAGE;

require_login();

require_once($CFG->dirroot . '/local/wunderbyte_table/classes/wunderbyte_table.php');

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url('/download.php');

$params['encodedtable'] = "eyJpZHN0cmluZyI6ImFkNmY3NDExMWNmNzg0NWJhYWUwMWIyNWRiYzU4OWI5IiwiY2xhc3NuYW1lIjoibG9jYWxfd3VuZGVyYnl0ZV90YWJsZVxcd3VuZGVyYnl0ZV90YWJsZSIsImNvdW50c3FsIjpudWxsLCJjb3VudHBhcmFtcyI6bnVsbCwic3FsIjp7ImZpZWxkcyI6IioiLCJmcm9tIjoie3VzZXJ9Iiwid2hlcmUiOiIxPTEiLCJwYXJhbXMiOltdfSwicmF3ZGF0YSI6bnVsbCwiaXNfc29ydGFibGUiOnRydWUsImlzX2NvbGxhcHNpYmxlIjp0cnVlLCJ1bmlxdWVpZCI6InVuaXF1ZWlkIiwiYXR0cmlidXRlcyI6eyJjbGFzcyI6ImdlbmVyYWx0YWJsZSBnZW5lcmFsYm94In0sImhlYWRlcnMiOltdLCJjb2x1bW5zIjpbXSwiY29sdW1uX3N0eWxlIjpbXSwiY29sdW1uX2NsYXNzIjpbXSwiY29sdW1uX3N1cHByZXNzIjpbXSwiY29sdW1uX25vc29ydCI6WyJ1c2VycGljIl0sInNldHVwIjpmYWxzZSwiYmFzZXVybCI6e30sInJlcXVlc3QiOnsiMSI6InRzb3J0IiwiMiI6InRoaWRlIiwiMyI6InRzaG93IiwiNCI6InRpZmlyc3QiLCI1IjoidGlsYXN0IiwiNiI6InBhZ2UiLCI3IjoidHJlc2V0IiwiOCI6InRkaXIifSwidXNlX3BhZ2VzIjpmYWxzZSwidXNlX2luaXRpYWxzIjpmYWxzZSwibWF4c29ydGtleXMiOjIsInBhZ2VzaXplIjoyLCJjdXJycGFnZSI6MCwidG90YWxyb3dzIjowLCJjdXJyZW50cm93IjowLCJzb3J0X2RlZmF1bHRfY29sdW1uIjpudWxsLCJzb3J0X2RlZmF1bHRfb3JkZXIiOjQsInNob3dkb3dubG9hZGJ1dHRvbnNhdCI6WzFdLCJ1c2VyaWRmaWVsZCI6ImlkIiwiZG93bmxvYWQiOiIiLCJkb3dubG9hZGFibGUiOnRydWUsInN0YXJ0ZWRfb3V0cHV0IjpmYWxzZSwiZXhwb3J0Y2xhc3MiOm51bGx9=";
if (!$decodedlib = base64_decode($params['encodedtable'])) {
  throw new moodle_exception('novalidbase64', 'local_wunderbyte_table', null, null,
          'Invalid base64 string');
}
if (!$lib = json_decode($decodedlib)) {
  throw new moodle_exception('novalidjson', 'local_wunderbyte_table', null, null,
          'Invalid json string');
}


$table = new $lib->classname($lib->uniqid);


$download = optional_param('download', '', PARAM_ALPHA);



// Pass all the variables to new table.
foreach ($lib as $key => $value) {
  if (in_array($key, ['request', 'attributes'])) {
      $table->{$key} = (array)$value;
  } else if (!in_array($key, ['baseurl'])) {
      $table->{$key} = $value;
  }
}

foreach ($params as $key => $value) {
  $_POST[$key] = $value;
}

$table->is_downloading($download,'download','download');
$table->out($table->pagesize, true);




