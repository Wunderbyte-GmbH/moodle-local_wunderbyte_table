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

namespace local_wunderbyte_table\local\performance;

use mod_booking\local\performance\performance_measurer;

/**
 * Class supports the performance measurements of mod_booking.
 *
 * @package    local_wunderbyte_table
 * @copyright  2025 Wunderbyte GmbH <info@wunderbyte.at>
 * @author     2025 Georg Maißer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class performance {
    /**
     * Starts a measurement.
     * @param string $name
     * @return void
     */
    public static function start_measurement($name) {
        $pluginman = \core_plugin_manager::instance();
        if (
            $pluginman->get_plugin_info('mod_booking')
            && class_exists('mod_booking\local\performance\performance_measurer')
        ) {
            $measurer = performance_measurer::instance();
            if (!$measurer) {
                return;
            }
            $measurer->start($name);
        }
    }


    /**
     * Ends a measurement.
     * @param string $name
     * @return void
     */
    public static function end_measurement($name) {
        $pluginman = \core_plugin_manager::instance();
        if (
            $pluginman->get_plugin_info('mod_booking')
            && class_exists('mod_booking\local\performance\performance_measurer')
        ) {
            $measurer = performance_measurer::instance();
            if (!$measurer) {
                return;
            }
            $measurer->end($name);
        }
    }
}
