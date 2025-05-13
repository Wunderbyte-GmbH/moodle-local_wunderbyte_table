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
 * Webservice to reload table.
 *
 * @package     local_wunderbyte_table
 * @category    upgrade
 * @copyright   2023 Wunderbyte GmbH <info@wunderbyte.at>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
        'local_wunderbyte_table_load_data' => [
                'classname' => 'local_wunderbyte_table\external\load_data',
                'description' => 'Ajax load table',
                'type' => 'read',
                'capabilities' => '',
                'ajax' => true,
                'loginrequired' => false,
        ],
        'local_wunderbyte_table_execute_action' => [
                'classname' => 'local_wunderbyte_table\external\execute_action',
                'description' => 'Executes an action button',
                'type' => 'write',
                'capabilities' => '',
                'ajax' => true,
                'loginrequired' => false,
        ],
        'local_wunderbyte_table_get_filter_fields' => [
                'classname' => 'local_wunderbyte_table\external\get_filter_fields',
                'description' => 'Get mandatory fields for a specific filter type',
                'type' => 'write',
                'capabilities' => '',
                'ajax' => true,
                'loginrequired' => false,
        ],
        'local_wunderbyte_table_get_filter_specific_fields' => [
                'classname' => 'local_wunderbyte_table\external\get_filter_specific_fields',
                'description' => 'Get mandatory fields for a specific filter type',
                'type' => 'write',
                'capabilities' => '',
                'ajax' => true,
                'loginrequired' => false,
        ],
        'local_wunderbyte_table_get_filter_column_data' => [
                'classname' => 'local_wunderbyte_table\external\get_filter_column_data',
                'description' => 'Get mandatory fields for a specific filter type',
                'type' => 'write',
                'capabilities' => '',
                'ajax' => true,
                'loginrequired' => false,
        ],
];
