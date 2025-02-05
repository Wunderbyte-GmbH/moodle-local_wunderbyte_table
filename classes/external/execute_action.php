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

use context_system;
use external_api;
use external_function_parameters;
use external_value;
use external_single_structure;
use local_wunderbyte_table\event\action_executed;
use local_wunderbyte_table\wunderbyte_table;

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
class execute_action extends external_api {

    /**
     * Describes the parameters this webservice.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'methodname'  => new external_value(PARAM_TEXT, 'Methodname to be executed.', VALUE_REQUIRED),
            'encodedtable'  => new external_value(PARAM_ALPHANUM, 'Encodedtable', VALUE_DEFAULT, ''),
            'id'  => new external_value(PARAM_INT, 'Id, normally of affected row.', VALUE_DEFAULT, 0),
            'data'  => new external_value(PARAM_RAW, 'Data package as json', VALUE_DEFAULT, '{}'),
        ]);
    }

    /**
     * Execute this webservice.
     *
     * @param string $methodname
     * @param string $encodedtable
     * @param int $id
     * @param string $data
     *
     * @return external_function_parameters
     *
     */
    public static function execute(
        string $methodname,
        string $encodedtable,
        int $id,
        string $data
    ) {

        global $USER, $PAGE;

        $params = [
            'encodedtable' => $encodedtable,
            'methodname' => $methodname,
            'id' => $id,
            'data' => $data,
        ];

        $params = self::validate_parameters(self::execute_parameters(), $params);

        $table = wunderbyte_table::instantiate_from_tablecache_hash($params['encodedtable']);

        $context = context_system::instance();

        // Normally, this webservice is only allowed for logged in users with some capabilites.
        // But this can be turned off for given tables.
        if ($table->requirelogin) {
            try {
                self::validate_context($context);
                require_capability($table->requirecapability, $context);
            } catch (Exception $e) {
                return [
                    'template' => '',
                    'content' => '',
                    'filterjson' => '',
                ];
            }
        } else {
            // We allow for this webservice to be executed without login, if specifically set so.
            // Therefore, we need to use Page->set_context().
            $PAGE->set_context($context);
        }

        $event = action_executed::create([
            'context' => $context,
            'userid' => $USER->id,
            'other' => [
                'tablename' => $table->uniqueid,
                'methodname' => $params['methodname'],
            ],
        ]);
        $event->trigger();

        if (method_exists($table, 'action_' . $params['methodname']) ) {
            $result = $table->{'action_' . $params['methodname']}($params['id'], $params['data']);
        } else {
            $result = [
                'success' => 0,
                'message' => get_string('functiondoesntexist', 'local_wunderbyte_table'),
            ];
        }

        return $result;
    }

    /**
     * Returns description of method result value.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'success' => new external_value(PARAM_INT, '1 is success, 0 isn\'t'),
            'message' => new external_value(PARAM_RAW, 'Message to be displayed', VALUE_DEFAULT, ''),
            'reload' => new external_value(PARAM_INT, 'Reload table', VALUE_DEFAULT, 0),
        ]);
    }
}
