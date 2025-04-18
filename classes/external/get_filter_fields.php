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
use local_wunderbyte_table\filters\filter_manager;

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
class get_filter_fields extends external_api {
    /**
     * Describes the parameters this webservice.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'filtertype'  => new external_value(PARAM_TEXT, 'Filter type', VALUE_REQUIRED),
        ]);
    }

    /**
     * Execute this webservice.
     * @param string $filtertype
     * @return array
     */
    public static function execute(
        string $filtertype
    ) {
        global $PAGE;
        $PAGE->set_context(\context_system::instance());

        $params = [
            'filtertype' => $filtertype,
        ];
        $params = self::validate_parameters(self::execute_parameters(), $params);
        $filtermanager = new filter_manager();
        $mandatoryfields = $filtermanager->get_mandatory_filter_fields($params['filtertype']);
        return [
            'filteraddfields' => $mandatoryfields->toHtml(),
        ];
    }

    /**
     * Returns description of method result value.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'filteraddfields' => new external_value(PARAM_RAW, 'fields html'),
        ]);
    }
}
