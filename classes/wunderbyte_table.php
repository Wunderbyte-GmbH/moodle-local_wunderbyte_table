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
use local_wunderbyte_table\output\table;
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
     *
     * @var array array of formated rows.
     */
    public $formatedrows = [];



    /**
     * @var array array of supplementary column information. Can be used like below.
     * ['cardheader' => [
     *                  column1 => [
     *                              'classidentifier1' => 'classname1',
     *                              'classidentifier2' => 'classname2']
     *                  ],
     * 'cardfooter' => [
     *                  column1 => [
     *                              'classidentifier1' => 'classname1',
     *                              'classidentifier2' => 'classname2']
     *                  ]
     * ]
     * In mustache template, use like {{classidentifer1}}
     */
    public $subcolumns = [];

    /**
     * array array of [classidentifier => classname] to use in mustache template on table level.
     *
     * @var array
     */
    public $tableclasses = [];

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
    public function outnew($pagesize, $useinitialsbar, $downloadhelpbutton = '') { // function nmae is out.

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
    public function out($pagesize, $useinitialsbar, $downloadhelpbutton = '') { // function name is printtable.
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
     * You should call this to finish outputting the table data after adding
     * data to the table with add_data or add_data_keyed.
     *
     */
    public function finish_output($closeexportclassdoc = true) {
        if ($this->exportclass !== null) {
            $this->exportclass->finish_table();
            if ($closeexportclassdoc) {
                $this->exportclass->finish_document();
            }
        } else {
            $this->finish_html();
        }
    }

    public function finish_html() {
        global $PAGE;

        $output = $PAGE->get_renderer('local_wunderbyte_table');
        $table = new table($this);
        echo $output->render_table($table);
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

    /**
     * @param array $columns an array of identifying names for columns. If
     * columns are sorted then column names must correspond to a field in sql.
     */
    function define_columns($columns) {
        $this->columns = array();
        $this->column_style = array();
        $this->column_class = array();
        $colnum = 0;

        foreach ($columns as $column) {
            $this->columns[$column]             = $colnum++;
            $this->column_style[$column]        = array();
            $this->column_class[$column]        = '';
            $this->cardbodycolumns[$column]    = ''; // This is specific to wunderbyte_table.
            $this->column_suppress[$column]     = false;
        }
    }

    /**
     * Wunderbyte table function to better control output
     *
     * @param string $column
     * @param string $classname
     * @return void
     */
    public function columnkeyclass($column, $classname) {
        if (!isset($this->cardbodycolumns[$column]['keyclass'])) {
            $this->cardbodycolumns[$column]['keyclass'] = $classname; // This space needed so that classnames don't run together in the HTML
        } else {
            $this->cardbodycolumns[$column]['keyclass'] .= ' '.$classname;
        }
    }

    /**
     * Wunderbyte table function to better control output
     *
     * @param string $column
     * @param string $classname
     * @return void
     */
    public function columnvalueclass($column, $classname) {
        if (!isset($this->cardbodycolumns[$column]['valueclass'])) {
            $this->cardbodycolumns[$column]['valueclass'] = $classname; // This space needed so that classnames don't run together in the HTML
        } else {
            $this->cardbodycolumns[$column]['valuelass'] .= ' '.$classname;
        }
    }

    /**
     * Function to set same class to all columns.
     * This will override all previous classes.
     *
     * @param string $classname
     * @return void
     */
    public function column_class_all($classname) {
        foreach (array_keys($this->columns) as $column) {
            $this->column_class[$column] = $classname;
        }
    }



    /**
     * Add one or more columns to a certain subcolumnidentifier.
     *
     * @param string $subcolumnsidentifier
     * @param array $subcolumns
     * @return void
     */
    public function addsubcolumns(string $subcolumnsidentifier, array $subcolumns) {
        if (strlen($subcolumnsidentifier) == 0) {
            throw new moodle_exception('nosubcolumidentifier', 'local_wunderbyte_table', null, null,
                    "You need to specify a columnidentifer like cardheader or cardfooter");
        }
        foreach ($this->columns as $key => $value) {
            $columns[] = $key;
        }
        foreach ($subcolumns as $key => $value) {
            $this->subcolumns[$subcolumnsidentifier][$value] = [];
            $columns[] = $value;
        }
        // this is necessary to make sure we create the right content.
        $this->define_columns($columns);

    }

    /**
     * Add one or more classes to some or all of the columns already specified in special subcolumnidentifier.
     * If no subcolumns are specified, all of them are treated. Classes array nedds to have form of...
     * ... ['classidentifier' => 'classname'] where {{classidentifier}} should be used in mustache template...
     * ... and 'classname' should be something like 'bg-primary md-none' etc.
     *
     * @param string $subcolumnsidentifier
     * @param array $classes
     * @param array|null $subcolumns
     * @param boolean $replace
     * @return void
     */
    public function addclassestosubcolumns(string $subcolumnsidentifier, array $classes, array $subcolumns = null, $replace = false) {
        if (strlen($subcolumnsidentifier) == 0) {
            throw new moodle_exception('nosubcolumidentifier', 'local_wunderbyte_table', null, null,
                    "You need to specify a columnidentifer like cardheader or cardfooter");
        }
        if (!$subcolumns) {
            $subcolumnsarray = $this->subcolumns[$subcolumnsidentifier];
        } else {
            foreach ($subcolumns as $item) {
                $subcolumnsarray[$item] = $item;
            }
        }
        foreach ($subcolumnsarray as $columnkey => $columnkey) {
            foreach ($classes as $key => $value) {
                if (!isset($key) || !isset($value)) {
                    throw new moodle_exception('nokeyvaluepairinclassarray', 'local_wunderbyte_table', null, null,
                    "The classarray has to have the form classidentifier => classname, where {{classidentifier}}
                        needs to be present in your mustache template.");
                }
                if ($replace || !isset($this->subcolumns[$subcolumnsidentifier][$columnkey][$key])) {
                    $this->subcolumns[$subcolumnsidentifier][$columnkey][$key] = $value;
                } else {
                    $this->subcolumns[$subcolumnsidentifier][$columnkey][$key] .= ' ' . $value;
                }

            }
        }
    }

    /**
     * Add any classidentifier and classname to mustache template.
     *
     * @param string $class
     * @param string $classname
     * @return void
     */
    public function settableclass(string $classidentifier, string $classname) {
        $this->tableclasses[$classidentifier] = $classname;
    }

    /**
     * Do nothing. This is just to override original function.
     *
     * @param [type] $row
     * @param string $classname
     * @return void
     */
    public function build_table() {
        foreach ($this->rawdata as $rawrow) {
            $this->formatedrows[] = $this->format_row($rawrow);
        }
    }
}
