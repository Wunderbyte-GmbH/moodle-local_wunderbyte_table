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
 * @copyright 2021 onwards Wunderbyte Gmbh <info@wunderbyte.at>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_wunderbyte_table;

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/tablelib.php");

use gradereport_singleview\local\ui\empty_element;
use moodle_exception;
use table_sql;
use moodle_url;
use local_wunderbyte_table\output\viewtable;
use stdClass;

/**
 * Wunderbyte table class is an extension of table_sql.
 */
class wunderbyte_table extends table_sql
{
    /**
     * @var string Id of this table.
     */
    public $idstring = '';
    /**
     * @var string classname of possible subclass.
     */
    public $classname = '';

    /**
     * Constructor. Does store uniqueid as hashed value and the actual classname.
     *
     * @param string $uniqueid
     */
    public function __construct($uniqueid) {
        parent::__construct($uniqueid);

        $this->idstring = md5($uniqueid);
        $this->classname = get_class($this);
    }

    /**
     * New out function just stores the settings as json in base64 format and creates a table.
     * It also attaches the necessary javascript to fetch the actual table via ajax afterwards.
     *
     * @param int $pagesize
     * @param bool $useinitialsbar
     * @param string $downloadhelpbutton
     * @return void
     */
    public function out($pagesize, $useinitialsbar, $downloadhelpbutton = '') {

        list($idnumber, $encodedtable, $html) = $this->outhtml($pagesize, $useinitialsbar, $downloadhelpbutton);

        echo $html;
    }

    /**
     * A version of the out function which does not actually echo but just returns the html plus the idnumber.
     *
     * @param int $pagesize
     * @param bool $useinitialsbar
     * @param string $downloadhelpbutton
     * @return array
     */
    public function outhtml($pagesize, $useinitialsbar, $downloadhelpbutton = '') {

        global $PAGE, $CFG;
        $this->pagesize = $pagesize;
        $this->useinitialsbar = $useinitialsbar;
        $this->downloadhelpbutton = $downloadhelpbutton;

        // We have to do a few steps here to make sure we can recreate afterwards.
        $encodedtablelib = json_encode($this);

        $jsonobject = json_decode($encodedtablelib);
        $this->add_classnames_to_classes($jsonobject);
        $encodedtablelib = json_encode($jsonobject);

        $base64encodedtablelib = base64_encode($encodedtablelib);

        // We need to urlencode everything to make it proof.
        $base64encodedtablelib = urlencode($base64encodedtablelib);

        $this->base64encodedtablelib = $base64encodedtablelib;
        $output = $PAGE->get_renderer('local_wunderbyte_table');
        $viewtable = new viewtable($this->idstring, $base64encodedtablelib);
        return [$this->idstring, $base64encodedtablelib, $output->render_viewtable($viewtable)];
    }

    /**
     * This is a copy of the old ->out method.
     * We need it to really print the table, when we override the new out with ajax-functions.
     *
     * @param [type] $pagesize
     * @param [type] $useinitialsbar
     * @param string $downloadhelpbutton
     * @return void
     */
    public function printtable($pagesize, $useinitialsbar, $downloadhelpbutton = '') {
        global $DB;
        if (!$this->columns) {
            $onerow = $DB->get_record_sql("SELECT {$this->sql->fields} FROM {$this->sql->from} WHERE {$this->sql->where}",
                $this->sql->params, IGNORE_MULTIPLE);
            // If columns is not set then define columns as the keys of the rows returned.
            // From the db.
            $this->define_columns(array_keys((array)$onerow));
            $this->define_headers(array_keys((array)$onerow));
        }
        $this->pagesize = $pagesize;
        $this->setup();
        $this->query_db($pagesize, $useinitialsbar);
        $this->build_table();
        $this->close_recordset();
        $this->finish_output();
    }


    /**
     * The function to update settings from the json. During encoding, arrays & stds get mixed up sometimes.
     * There, this does the cleaning and attribution.
     *
     * @param [type] $lib
     * @return void
     */
    public function update_from_json($lib) {
        // We have to make sure some fields are properly typed.
        $lib->sql->params = (array)$lib->sql->params;

        // Pass all the variables to new table.
        foreach ($lib as $key => $value) {
            if (in_array($key, ['request', 'attributes', 'headers', 'columns',
                'column_style', 'column_class', 'column_suppress'])) {
                $this->{$key} = (array)$value;
            } else if ($value instanceof stdClass) {
                // We check if this is a coursemodule.
                if (isset($value->cm)
                    && isset($value->cm->id)
                    && isset($value->wbtclassname)
                    && class_exists($value->wbtclassname)) {
                    if ($cm = new $value->wbtclassname($value->cm->id)) {
                        $this->{$key} = $cm;
                    } else {
                        // If we couldn't create an instance, we stick to the stdclass.
                        $this->{$key} = $value;
                    }
                } else {
                    $this->{$key} = $value;
                }
            } else {
                $this->{$key} = $value;
            }
        }
    }

    /**
     * Function to treat decoding of the encoded talbe we receive via ajax or post.
     *
     * @param string $encodedtable
     * @return object
     */
    public static function decode_table_settings(string $encodedtable):object {

        $urldecodedtable = urldecode($encodedtable);

        if (!$decodedlib = base64_decode($urldecodedtable)) {
            throw new moodle_exception('novalidbase64', 'local_wunderbyte_table', null, null,
                    'Invalid base64 string');
        }
        if (!$lib = json_decode($decodedlib)) {
            throw new moodle_exception('novalidjson', 'local_wunderbyte_table', null, null,
                    'Invalid json string');
        }
        return $lib;
    }

    /**
     * This function is necessary to add the classname including the path to the json object.
     * With this information we can reinstantiate the class afterwards.
     *
     * @param object $jsonobject
     * @return void
     */
    private function add_classnames_to_classes(&$jsonobject) {
        // Pass all the variables to new table.
        foreach ($jsonobject as $key => $value) {
            if ($value instanceof stdClass) {
                // We check if this is a coursemodule.
                if (isset($value->cm)
                    && isset($value->cm->id)) {
                        // If so, we need to add the classname to make sure we can instantiate it afterwards.
                        $classname = get_class($this->{$key});
                        $value->wbtclassname = $classname;
                }
            }
        }
    }

}
