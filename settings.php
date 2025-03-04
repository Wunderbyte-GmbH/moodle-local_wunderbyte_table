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
 * Plugin administration pages are defined here.
 *
 * @package     local_wunderbyte_table
 * @category    admin
 * @copyright   2022 Wunderbyte Gmbh <info@wunderbyte.at>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$componentname = "local_wunderbyte_table";

if ($hassiteconfig) {
    // Add the category to the local plugin branch.
    $settings = new admin_settingpage($componentname . '_settings', '');
    $ADMIN->add('localplugins', new admin_category($componentname, get_string('pluginname', $componentname)));
    $ADMIN->add($componentname, $settings);

    $allowedittable = new admin_setting_configcheckbox(
        'local_wunderbyte_table/allowedittable',
        get_string('allowedittable', 'local_wunderbyte_table'),
        '',
        0
    );
    // Make sure, we reset everything, once this checkbox is turned off (or on).
    $allowedittable->set_updatedcallback(function () {
        global $DB;
        cache_helper::purge_by_event('setbackfilters');
        cache_helper::purge_by_event('setbackencodedtables');
        cache_helper::purge_by_event('changesinwunderbytetable');
        $sql = "DELETE FROM {local_wunderbyte_table}
                      WHERE hash LIKE '%_filterjson'
                         OR hash LIKE '%_sqlquery'";
        $DB->execute($sql);
    });
    $settings->add($allowedittable);

    $settings->add(
        new admin_setting_configcheckbox(
            'local_wunderbyte_table/logfiltercaches',
            get_string('logfiltercaches', 'local_wunderbyte_table'),
            '',
            0
        )
    );

    $settings->add(
        new admin_setting_configcheckbox(
            'local_wunderbyte_table/allowsearchincolumns',
            get_string('allowsearchincolumns', 'local_wunderbyte_table'),
            '',
            0
        )
    );

    $settings->add(
        new admin_setting_configcheckbox(
            'local_wunderbyte_table/turnoffcaching',
            get_string('turnoffcaching', 'local_wunderbyte_table'),
            '',
            0
        )
    );
}
