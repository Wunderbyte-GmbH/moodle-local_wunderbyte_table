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
 * This class contains a list of webservice functions related to Wunderbyte Table.
 *
 * @package    local_wunderbyte_table
 * @copyright  2023 Wunderbyte GmbH
 * @author     Georg Maißer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace local_wunderbyte_table\external;

use cache;
use Exception;
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
 * @copyright 2023 Wunderbyte GmbH {@link http://www.wunderbyte.at}
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
        return new external_function_parameters([
            'encodedtable'  => new external_value(PARAM_ALPHANUM, 'eoncodedtable', VALUE_DEFAULT, ''),
            'page'  => new external_value(PARAM_INT, 'page', VALUE_REQUIRED),
            'tsort'   => new external_value(PARAM_TEXT, 'sort value', VALUE_REQUIRED),
            'thide'   => new external_value(PARAM_TEXT, 'hide value', VALUE_REQUIRED),
            'tshow'   => new external_value(PARAM_RAW, 'show value', VALUE_REQUIRED),
            'tdir'    => new external_value(PARAM_INT, 'dir value', VALUE_REQUIRED),
            'treset'  => new external_value(PARAM_INT, 'reset value', VALUE_REQUIRED),
            'wbtfilter'  => new external_value(PARAM_RAW, 'reset value', VALUE_REQUIRED),
            'searchtext'  => new external_value(PARAM_TEXT, 'reset value', VALUE_REQUIRED),
        ]);
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
     * @return array
     */
    public static function execute(
        $encodedtable = null,
        $page = null,
        $tsort = null,
        $thide = null,
        $tshow = null,
        $tdir = null,
        $treset = null,
        $filterobjects = null,
        $searchtext = null
    ) {

        global $PAGE;

        $params = [
                'encodedtable' => $encodedtable,
                'page' => $page,
                'tsort' => $tsort,
                'thide' => $thide,
                'tshow' => $tshow,
                'tdir' => $tdir,
                'treset' => $treset,
                'wbtfilter' => $filterobjects,
                'searchtext' => $searchtext ?? "",
        ];

        $params = self::validate_parameters(self::execute_parameters(), $params);

        $table = wunderbyte_table::instantiate_from_tablecache_hash($params['encodedtable']);

        // Normally, this webservice is only allowed for logged in users with some capabilites.
        // But this can be turned off for given tables.
        $context = $table->get_context();
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

        // If the table was cached with filter or searchtext, we need to recache it.
        $recachetable = false;
        if (!empty($table->urlfilter)) {
            $params['wbtfilter'] = $table->urlfilter;
            $table->urlfilter = '';
            $recachetable = true;
        }
        if (!empty($table->urlsearch)) {
            $params['searchtext'] = $table->urlsearch ?? "";
            $table->urlsearch = '';
            $recachetable = true;
        }
        if ($recachetable) {
            $cache = cache::make('local_wunderbyte_table', 'encodedtables');
            $cache->delete($params['encodedtable']);
            $table->return_encoded_table(true);
        }

        if (empty($table->baseurl)) {
            if (!empty($table->baseurlstring)) {
                $table->define_baseurl($table->baseurlstring);
            } else {
                // Fallback, wunderbyte_table doesn't really need the baseurl anyways.
                $table->baseurl = new moodle_url("/local/wunderbyte_table/download.php");
            }
        }

        // We need to support both keys, for legacy reasons.
        $params['wbtsearch'] = $params['searchtext'];

        // The table lib class expects $_POST variables to be present, so we have to set them.
        foreach ($params as $key => $value) {
            $_POST[$key] = $value;
        }

        if (!empty($params['tsort'])) {
            $table->unset_sorting_settings();
        }

        // Make sure the chosen template is marked as selected.
        if (!empty($table->switchtemplates['templates'])) {
            foreach ($table->switchtemplates['templates'] as &$t) {
                if (
                    ($t['template'] == get_user_preferences('wbtable_chosen_template_' . $table->uniqueid))
                    && ($t['viewparam'] == get_user_preferences(
                        'wbtable_chosen_template_viewparam_' . $table->uniqueid
                    ))
                ) {
                    $t['selected'] = true;
                } else {
                    unset($t['selected']);
                }
            }
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
        return new external_single_structure([
            'template' => new external_value(PARAM_TEXT, 'template name'),
            'content' => new external_value(PARAM_RAW, 'json content'),
            'filterjson' => new external_value(PARAM_RAW, 'filter json to create checkboxes', VALUE_OPTIONAL, ''),
        ]);
    }
}
