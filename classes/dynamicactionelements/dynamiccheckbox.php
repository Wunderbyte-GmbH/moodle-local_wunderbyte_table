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
 * The Wunderbyte table class is an extension of the tablelib table_sql class.
 *
 * @package local_wunderbyte_table
 * @copyright 2023 Wunderbyte Gmbh <info@wunderbyte.at>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
// phpcs:ignoreFile

namespace local_wunderbyte_table\dynamicactionelements;

defined('MOODLE_INTERNAL') || die();

/**
 * Wunderbyte table demo class.
 */
class dynamiccheckbox {
    /**
     * Returns data format
     * @param string $valueid
     * @param string $uniqueid
     * @return array
     */
    public static function generate_data($valueid, $uniqueid) {
        return [
            'label' => get_string('checkbox', 'local_wunderbyte_table'),
            'class' => 'btn btn-success',
            'href' => '#',
            'iclass' => 'fa fa-edit',
            'id' => $valueid.'-'.$uniqueid,
            'name' => $uniqueid.'-'. $valueid,
            'methodname' => 'togglecheckbox',
            'ischeckbox' => true,
            'data' => [
                'id' => $valueid,
                'labelcolumn' => 'username',
            ]
        ];
    }
}
