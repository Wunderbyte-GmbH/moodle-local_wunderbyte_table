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
 * Plugin upgrade steps are defined here.
 *
 * @package     local_wunderbyte_table
 * @category    upgrade
 * @copyright   2024 Wunderbyte GmbH<info@wunderbyte.at>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Execute local_wunderbyte_table upgrade from the given old version.
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_local_wunderbyte_table_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2024012501) {

        // Define table wunderbyte_table_settings to be created.
        $table = new xmldb_table('local_wunderbyte_table');

        // Adding fields to table wunderbyte_table_settings.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('hash', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('tablehash', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('idstring', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('page', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('jsonstring', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('sql', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table wunderbyte_table_settings.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Conditionally launch create table for wunderbyte_table_settings.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define index hash-userid (unique) to be added to wunderbyte_table_settings.
        $table = new xmldb_table('local_wunderbyte_table');
        $index = new xmldb_index('hash-userid', XMLDB_INDEX_UNIQUE, ['hash', 'userid']);

        // Conditionally launch add index hash-userid.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Wunderbyte_table savepoint reached.
        upgrade_plugin_savepoint(true, 2024012501, 'local', 'wunderbyte_table');
    }

    if ($oldversion < 2024021600) {

        // Define field count to be added to local_wunderbyte_table.
        $table = new xmldb_table('local_wunderbyte_table');
        $field = new xmldb_field('count', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'timemodified');

        // Conditionally launch add field count.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Wunderbyte_table savepoint reached.
        upgrade_plugin_savepoint(true, 2024021600, 'local', 'wunderbyte_table');
    }

    if ($oldversion < 2024042600) {

        // Define field tablehash to be added to local_wunderbyte_table.
        $table = new xmldb_table('local_wunderbyte_table');
        $field = new xmldb_field('tablehash', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'hash');

        // Conditionally launch add field tablehash.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field tablehash to be added to local_wunderbyte_table.
        $field = new xmldb_field('idstring', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'tablehash');

        // Conditionally launch add field tablehash.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Wunderbyte_table savepoint reached.
        upgrade_plugin_savepoint(true, 2024042600, 'local', 'wunderbyte_table');
    }

    // For further information please read {@link https://docs.moodle.org/dev/Upgrade_API}.
    //
    // You will also have to create the db/install.xml file by using the XMLDB Editor.
    // Documentation for the XMLDB Editor can be found at {@link https://docs.moodle.org/dev/XMLDB_editor}.

    return true;
}
