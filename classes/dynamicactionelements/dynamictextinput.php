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
class dynamictextinput {
    /**
     * Returns data format for text input
     * @param string $valueid
     * @param string $uniqueid
     * @return array
     */
    public static function generate_data($valueid, $uniqueid) {
        return [
            'istextinput' => true,
            'id' => $valueid . '-' . $uniqueid,
            'name' => $uniqueid . '-' . $valueid,
            'class' => 'form-control',
            'methodname' => 'textinputchange',
            'data' => [
                'id' => $valueid,
                'placeholder' => 'Enter some text...',
                'maxlength' => 255,
            ]
        ];
    }
}
