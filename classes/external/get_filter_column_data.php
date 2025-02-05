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
 * This class contains a list of webservice functions related to the Wunderbyte Table.
 *
 * @package    local_wunderbyte_table
 * @copyright  2023 Wunderbyte GmbH
 * @author     Georg Maißer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace local_wunderbyte_table\external;

use external_api;
use external_function_parameters;
use external_value;
use external_single_structure;
use local_wunderbyte_table\filters\column_manager;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

/**
 * External Service for local wunderbyte_table to (re)load data.
 *
 * @package   local_wunderbyte_table
 * @copyright 2023 Wunderbyte GmbH {@link http://www.wunderbyte.at}
 * @author    Georg Maißer
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_filter_column_data extends external_api {
    /**
     * Describes the parameters this webservice.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'filtercolumn'  => new external_value(PARAM_TEXT, 'Filter column', VALUE_REQUIRED),
            'encodedtable'  => new external_value(PARAM_TEXT, 'Encodedtable', VALUE_REQUIRED),
        ]);
    }

    /**
     * Execute this webservice.
     * @param string $filtercolumn
     * @param string $encodedtable
     * @return array
     */
    public static function execute(
        string $filtercolumn,
        string $encodedtable
    ) {
        global $PAGE;
        $PAGE->set_context(\context_system::instance());

        $params = [
            'filtercolumn' => $filtercolumn,
            'encodedtable' => $encodedtable,
        ];
        $params = self::validate_parameters(self::execute_parameters(), $params);
        $columnmanager = new column_manager($params);
        $filteredcolumnforms = $columnmanager->get_filtered_column_form();
        return $filteredcolumnforms;
    }

    /**
     * Returns description of method result value.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'filtereditfields' => new external_value(PARAM_RAW, 'fields html'),
            'filteraddfields' => new external_value(PARAM_RAW, 'fields html'),
        ]);
    }
}
