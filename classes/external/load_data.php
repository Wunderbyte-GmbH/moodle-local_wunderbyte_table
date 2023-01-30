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
 * This class contains a list of webservice functions related to the catquiz Module by Wunderbyte.
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
use local_wunderbyte_table\wunderbyte_table;
use moodle_url;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

/**
 * External Service for local wunderbyte_table to (re)load data.
 *
 * @package   local_wunderbyte_table
 * @copyright 2022 Wunderbyte GmbH {@link http://www.wunderbyte.at}
 * @author    Georg Maißer
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class load_data extends external_api {

    /**
     * Describes the parameters this webservice.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters(array(
            'encodedtable'  => new external_value(PARAM_RAW, 'eoncodedtable', VALUE_DEFAULT, ''),
            'page'  => new external_value(PARAM_INT, 'page', VALUE_REQUIRED),
            'tsort'   => new external_value(PARAM_TEXT, 'sort value', VALUE_REQUIRED),
            'thide'   => new external_value(PARAM_TEXT, 'hide value', VALUE_REQUIRED),
            'tshow'   => new external_value(PARAM_RAW, 'show value', VALUE_REQUIRED),
            'tdir'    => new external_value(PARAM_INT, 'dir value', VALUE_REQUIRED),
            'treset'  => new external_value(PARAM_INT, 'reset value', VALUE_REQUIRED),
            'filterobjects'  => new external_value(PARAM_TEXT, 'reset value', VALUE_REQUIRED),
            'searchtext'  => new external_value(PARAM_TEXT, 'reset value', VALUE_REQUIRED),
            )
        );
    }

    /**
     * Execute this webservice.
     * @param string $encodedtable
     * @param integer $page
     * @param string $tsort
     * @param string $thide
     * @param string $tshow
     * @param integer $tdir
     * @param integer $treset
     * @param string $filterobjects
     * @param string $searchtext
     * @return external_function_parameters
     */
    public static function execute($encodedtable = null,
            $page = null,
            $tsort = null,
            $thide = null,
            $tshow = null,
            $tdir = null,
            $treset = null,
            $filterobjects = null,
            $searchtext = null) {

        global $CFG, $PAGE;

        $context = context_system::instance();
        $PAGE->set_context($context);

        $params = array(
                'encodedtable' => $encodedtable,
                'page' => $page,
                'tsort' => $tsort,
                'thide' => $thide,
                'tshow' => $tshow,
                'tdir' => $tdir,
                'treset' => $treset,
                'filterobjects' => $filterobjects,
                'searchtext' => $searchtext
        );

        $params = self::validate_parameters(self::execute_parameters(), $params);

        $lib = wunderbyte_table::decode_table_settings($params['encodedtable']);
        $table = new $lib['classname']($lib['uniqueid']);
        $table->update_from_json($lib);

        if (empty($table->baseurl)) {

            if (!empty($table->baseurlstring)) {
                $table->define_baseurl($table->baseurlstring);
            } else {
                // Fallback, wunderbyte_table doesn't really need the baseurl anyways.
                $table->baseurl = new moodle_url("/local/wunderbyte_table/download.php");
            }
        }

        // The table lib class expects $_POST variables to be present, so we have to set them.
        foreach ($params as $key => $value) {
            $_POST[$key] = $value;
        }

        if (!empty($params['filterobjects'])) {
            $table->apply_filter($params['filterobjects']);
        }
        if (!empty($params['searchtext'])) {
            $table->apply_searchtext($params['searchtext']);
        }

        // No we return the json object and the matching method.
        $tableobject = $table->printtable($table->pagesize, $table->useinitialsbar, $table->downloadhelpbutton);
        $output = $PAGE->get_renderer('local_wunderbyte_table');
        $tabledata = $tableobject->export_for_template($output);

        if ($tabledata) {
            $result['template'] = $table->tabletemplate;
            $result['content'] = json_encode($tabledata);
            $result['filterjson'] = $table->filterjson ?? '';
        }

        return $result;
    }

    /**
     * Returns description of method result value.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure(array(
            'template' => new external_value(PARAM_TEXT, 'template name'),
            'content' => new external_value(PARAM_RAW, 'json content'),
            'filterjson' => new external_value(PARAM_RAW, 'filter json to create checkboxes', VALUE_OPTIONAL, '')
            )
        );
    }
}
