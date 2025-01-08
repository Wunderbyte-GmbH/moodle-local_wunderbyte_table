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
 * Mobile cache definitions.
 *
 * @package    local_wunderbyte_table
 * @copyright  2023 Wunderbyte GmbH <info@wunderbyte.at>
 * @author     Georg Maißer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$definitions = [
    'cachedrawdata' => [
        'mode' => cache_store::MODE_APPLICATION,
        'simplekeys' => true,
        'staticacceleration' => true,
        'staticaccelerationsize' => 500,
        'invalidationevents' => ['changesinwunderbytetable'],
    ],
    'cachedfulltable' => [
        'mode' => cache_store::MODE_APPLICATION,
        'simplekeys' => true,
        'staticacceleration' => true,
        'staticaccelerationsize' => 1,
        'invalidationevents' => ['smallchangesinwunderbytetable', 'changesinwunderbytetable'],
    ],
    'encodedtables' => [
        'mode' => cache_store::MODE_APPLICATION,
        'simplekeys' => true,
        'staticacceleration' => true,
        'staticaccelerationsize' => 100,
        'invalidationevents' => ['setbackencodedtables'],
    ],
    'cachedfilters' => [
        'mode' => cache_store::MODE_APPLICATION,
        'simplekeys' => true,
        'staticacceleration' => true,
        'staticaccelerationsize' => 100,
        'invalidationevents' => ['setbackfilters'],
    ],
];
