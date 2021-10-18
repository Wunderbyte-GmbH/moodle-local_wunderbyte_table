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
 * Wunderbyte table external API
 *
 * @package local_wunderbyte_table
 * @category external
 * @copyright 2021 Wunderbyte Gmbh <info@wunderbyte.at>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_wunderbyte_table\wunderbyte_table;

defined('MOODLE_INTERNAL') || die();

require_once('wunderbyte_table.php');

/**
 * Class local_wunderbyte_table_external
 */
class local_wunderbyte_table_external extends external_api {

    /**
     * Undocumented function
     *
     * @param string $encodedtable
     * @param int|null $pageid
     * @param string|null $tsort
     * @param string|null $thide
     * @param string|null $tshow
     * @param int|null $tdir
     * @param int|null $treset
     * @return void
     */
    public static function load_data(
            $encodedtable = null,
            $page = null,
            $tsort = null,
            $thide = null,
            $tshow = null,
            $tdir = null,
            $treset = null) {
        global $COURSE, $CFG, $PAGE;

        $context = context_system::instance();
        $PAGE->set_context($context);

        $params = array(
                'encodedtable' => $encodedtable,
                'page' => $page,
                'tsort' => $tsort,
                'thide' => $thide,
                'tshow' => $tshow,
                'tdir' => $tdir,
                'treset' => $treset
        );

        $params = self::validate_parameters(self::load_data_parameters(), $params);

        $lib = wunderbyte_table::decode_table_settings($params['encodedtable']);

        $table = new $lib->classname($lib->uniqueid);

        $table->update_from_json($lib);

        $table->define_baseurl("$CFG->wwwroot/local/wunderbyte_table/download.php");

        foreach ($params as $key => $value) {
            $_POST[$key] = $value;
        }

        ob_start();
        $table->printtable($table->pagesize, $table->useinitialsbar, $table->downloadhelpbutton);

        $result['content'] = ob_get_clean();

        return $result;
    }

    /**
     * Describes the paramters for load_data.
     * @return external_function_parameters
     */
    public static function load_data_parameters() {
        return new external_function_parameters(array(
                        'encodedtable'  => new external_value(PARAM_RAW, 'eoncodedtable', VALUE_DEFAULT, ''),
                        'page'  => new external_value(PARAM_INT, 'page', VALUE_OPTIONAL),
                        'tsort'   => new external_value(PARAM_RAW, 'sort value', VALUE_OPTIONAL),
                        'thide'   => new external_value(PARAM_RAW, 'hide value', VALUE_OPTIONAL),
                        'tshow'   => new external_value(PARAM_RAW, 'show value', VALUE_OPTIONAL),
                        'tdir'    => new external_value(PARAM_INT, 'dir value', VALUE_OPTIONAL),
                        'treset'  => new external_value(PARAM_INT, 'reset value', VALUE_OPTIONAL),
                )
        );
    }

    /**
     * Describes the return values for load_data.
     * @return external_multiple_structure
     */
    public static function load_data_returns() {
        return new external_single_structure(array(
                    'content' => new external_value(PARAM_RAW, 'html content')
                )
        );
    }

}
