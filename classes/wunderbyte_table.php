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

use cache_helper;
use Exception;
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
class wunderbyte_table extends table_sql {

    // This variable overrides the one in table_sql. We also need the filter field.
    /**
     * @var object sql for querying db. Has fields 'fields', 'from', 'where', 'filter', 'params'.
     */
    public $sql = null;


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
     *
     * @var array The string of a json object to output the filter.
     */
    public $filterjson = null;

    /**
     *
     * @var string component where cache defintion is to be found.
     */
    public $cachecomponent = 'local_wunderbyte_table';

    /**
     *
     * @var string name of the cache definition in the above defined component.
     */
    public $rawcachename = 'cachedrawdata';

    /**
     *
     * @var string name of the cache definition in the above defined component.
     */
    public $renderedcachename = 'cachedfulltable';

    /**
     *
     * @var string template for table.
     */
    public $tabletemplate = 'local_wunderbyte_table/table';

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
    public function out($pagesize, $useinitialsbar, $downloadhelpbutton = '') {

        list($idnumber, $encodedtable, $html) = $this->outhtml($pagesize, $useinitialsbar, $downloadhelpbutton);

        echo $html;
    }

    /**
     * With this function, the table can be printed without lazy loading.
     * Can be overridden in child class with own renderer.
     *
     * @param int $pagesize
     * @param bool $useinitialsbar
     * @param string $downloadhelpbutton
     * @return string
     */
    public function nolazyout($pagesize, $useinitialsbar, $downloadhelpbutton = '') {

        global $PAGE, $CFG;
        $this->pagesize = $pagesize;
        $this->useinitialsbar = $useinitialsbar;
        $this->downloadhelpbutton = $downloadhelpbutton;

        $tableobject = $this->printtable($pagesize, $useinitialsbar);
        $output = $PAGE->get_renderer('local_wunderbyte_table');
        return $output->render_table($tableobject);
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
        }
        $this->pagesize = $pagesize;
        $this->setup();
        // First we query without the filter.
        $this->query_db_cached($pagesize, $useinitialsbar);

        $this->build_table();
        $this->close_recordset();
        return $this->finish_output();
    }


    /**
     * You should call this to finish outputting the table data after adding
     * data to the table with add_data or add_data_keyed.
     * @param boolean $closeexportclassdoc
     * @return void
     */
    public function finish_output($closeexportclassdoc = true) {
        if ($this->exportclass !== null) {
            $this->exportclass->finish_table();
            if ($closeexportclassdoc) {
                $this->exportclass->finish_document();
            }
        } else {
            return new table($this);
        }
    }

    /**
     * Override table_sql function and use renderer.
     * (Not used in last revision)
     *
     * @return void
     */
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
        // Pass all the variables to new table.
        foreach ($lib as $key => $value) {

            if (isset($value['cm'])
                    && isset($value['cm']['id'])
                    && isset($value['wbtclassname'])
                    && class_exists($value['wbtclassname'])) {
                if ($cm = new $value['wbtclassname']($value['cm']['id'])) {
                    $this->{$key} = $cm;
                } else {
                    // If we couldn't create an instance, we stick to the stdclass.
                    $this->{$key} = $value;
                }
            } else if (in_array($key, ['sql'])) {
                $this->{$key} = (object)$value;
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
    public static function decode_table_settings(string $encodedtable):array {

        $urldecodedtable = urldecode($encodedtable);

        if (!$decodedlib = base64_decode($urldecodedtable)) {
            throw new moodle_exception('novalidbase64', 'local_wunderbyte_table', null, null,
                    'Invalid base64 string');
        }
        if (!$lib = json_decode($decodedlib, true)) {
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
    public function add_subcolumns(string $subcolumnsidentifier, array $subcolumns) {
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
        // This is necessary to make sure we create the right content.
        parent::define_columns($columns);
    }

    /** This overrides the classic define columns functions.
     * In the new table, one wouldn't use it but expose it here for backward compatibility.
     * @param array $columns
     * @param boolean $usestandardclasses
     * @return void
     */
    public function define_columns($columns, $usestandardclasses = true) {

        $this->add_subcolumns('cardbody', $columns, true);

        // The standardclasses offer for a quick and standard way to configure a responsive table.
        if ($usestandardclasses) {
            // This adds the width to all normal columns.
            $this->add_classes_to_subcolumns('cardbody', ['columnclass' => 'col-sm']);
            // This avoids showing all keys in list view.
            $this->add_classes_to_subcolumns('cardbody', ['columnkeyclass' => 'd-md-none']);

            // Override naming for columns. one could use getstring for localisation here.
            $this->add_classes_to_subcolumns('cardbody', ['keystring' => 'Moodle id'], ['id']);

            // Add some bootstrap to the general table.
            $this->set_tableclass('listheaderclass', 'card d-none d-md-block');
            $this->set_tableclass('cardheaderclass', 'card-header d-md-none bg-warning');
            $this->set_tableclass('cardbodyclass', 'card-body row');
        }
    }

    /** This overrides the classic define columns functions.
     * In the new table, one wouldn't use it but expose it here for backward compatibility.
     *
     * @param array $columns
     * @param boolean $usestandardclasses
     * @return void
     */
    public function define_headers($columns, $usestandardclasses = true) {
        $this->add_subcolumns('cardheader', $columns);
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
    public function add_classes_to_subcolumns(
                string $subcolumnsidentifier,
                array $classes,
                array $subcolumns = null,
                $replace = false) {
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
     * @param string $classidentifier
     * @param string $classname
     * @return void
     */
    public function set_tableclass(string $classidentifier, string $classname) {
        $this->tableclasses[$classidentifier] = $classname;
    }

    /**
     * This is to override original function, but we still format rows.
     * @return void
     */
    public function build_table() {
        foreach ($this->rawdata as $rawrow) {
            $this->formatedrows[] = $this->format_row($rawrow);
        }
    }

    /**
     * Function to set new cache instead of general wunderbyte_table cache.
     * If you use more than one wunderbyte_table in your project, you can use different caches for each table.
     * The rawcache goes caches the sql. This might be enough in most cases.
     * But you also migth need to use a second cache, which goes on the full table.
     * This is needed when you transform your sql results a lot with information with form other tables.
     * Having this cache will acutally avoid running the likely expensive "build_table" function.
     *
     * @param string $componentname
     * @param string|null $rawcachename
     * @param string|null $renderedcachename
     * @return void
     */
    public function define_cache(string $componentname, string $rawcachename = null, string $renderedcachename = null) {

        if ($rawcachename && $componentname) {
            $this->cachecomponent = $componentname;
            $this->rawcachename = $rawcachename;
        } else {
            // It might be that we don't want to use cache in a table.
            $this->cachecomponent = null;
            $this->rawcachename = null;
        }
        // In many cases, everything will work fine without this cache being defined.
        $this->renderedcachename = $renderedcachename;

    }

    /**
     * Define the columns for which an automatic filter should be generated.
     * We just store them as subcolumns of type datafields. In the mustache template these fields must be added to every...
     * ... row or card element, so it can be hidden or shown via the integrated filter mechanism..
     * @param array $filtercolumns
     * @return void
     */
    public function define_filtercolumns(array $filtercolumns) {

        $this->add_subcolumns('datafields', $filtercolumns, false);

    }

    /**
     * This calls the parent query_db function, but only after checking for cached queries.
     * This function can and should be overriden if your plugin needs different cache treatment.
     *
     * @param int $pagesize
     * @param boolean $useinitialsbar
     * @return void
     */
    public function query_db_cached($pagesize, $useinitialsbar=true) {

        // First create hash of all relevant entries.
        $sort = $this->get_sql_sort();
        if ($sort) {
            $sort = "ORDER BY $sort";
        }

        // If we haven't a filter json yet and we have filters defined ans usepages is right now true...
        // ... we set it for a moment to false.
        // We do so because we need to get the whole table for once. We'll run the same function again twice...
        // ... and the next time, the pagination will be applied.
        if (isset($this->subcolumns['datafields'])
            && !$this->filterjson
            && ($this->use_pages = true)) {

            // For the caching to work over all pages, we need to set the currpage to null for the filter request.
            // Else the hash value would not match and we would not have filtering of filterjson over the different pages.
            $currpage = $this->currpage;

            $this->use_pages = false;
            $this->currpage = null;
        } else {
            $this->use_pages = true;
        }

        // Create the query string including params.
        $sql = "SELECT
                {$this->sql->fields}
                FROM {$this->sql->from}
                WHERE {$this->sql->where}
                {$sort}"
                . json_encode($this->sql->params)
                . $pagesize
                . $useinitialsbar
                . $this->download
                . $this->currpage
                . $this->use_pages;

        // Now that we have the string, we hash it with a very fast method.
        $cachekey = crc32($sql);

        // And then we query our cache to see if we have it already.
        if ($this->cachecomponent && $this->rawcachename) {
            $cache = \cache::make($this->cachecomponent, $this->rawcachename);
            $cachedrawdata = $cache->get($cachekey);
        } else {
            $cachedrawdata = false;
        }

        if ($cachedrawdata !== false) {
            // If so, just return it.
            $this->rawdata = (array)$cachedrawdata;
            $pagination = $cache->get($cachekey . '_pagination');
            $this->pagesize = $pagination['pagesize'];
            $this->totalrows = $pagination['totalrows'];
            $this->currpage = $pagination['currpage'];
            $this->use_pages = $pagination['use_pages'];
        } else {
            // If not, we query as usual.
            try {

                $this->query_db($pagesize, $useinitialsbar);

            } catch (Exception $e) {
                $this->rawdata = [];
            }

            // After the query, we set the result to the.
            // But only, if we have a cache by now.
            if ($this->cachecomponent
                && $this->rawcachename
                && $cache) {
                $cache->set($cachekey, $this->rawdata);
                if (isset($this->use_pages)
                            && isset($this->pagesize)
                            && isset($this->totalrows)) {
                    $pagination['pagesize'] = $this->pagesize;
                    $pagination['totalrows'] = $this->totalrows;
                    $pagination['currpage'] = $this->currpage;
                    $pagination['use_pages'] = $this->use_pages;
                    $cache->set($cachekey . '_pagination', $pagination);
                }
            }
        }

        // We have stored the columns to filter in the subcolumn "datafields".
        // If we have filters defines, we need to actually create a filter json.
        // It might exist already in our DB. We have to create this from all data, without filter applied.

        if (isset($this->subcolumns['datafields']) && !$this->filterjson) {
            if (!$this->filterjson = $cache->get($cachekey . '_filterjson')) {
                // Now we create the filter json from the unfiltered json.
                $this->filterjson = $this->return_filterjson();
                $cache->set($cachekey . '_filterjson', $this->filterjson);
            }
        }

        // If there is actually a filter, we need to run this code again, but with the filter applied.
        // The first time, we got rawdata but it's without the filter applied.
        // We still use it.
        if (!$this->use_pages || (!empty($this->sql->filter) && strpos($this->sql->where, $this->sql->filter) == false)) {

            $this->sql->where .= $this->sql->filter ?? '';

            // To avoid another run, we have to set filter to empty now.
            $this->sql->filter = '';

            $this->use_pages = true;
            $this->currpage = $currpage;
            $this->query_db_cached($pagesize, $useinitialsbar);
        }

    }

    /**
     * This overrides standardfunction in table_sql class which would output Name with link.
     * We don't want this here.
     *
     * @param object $row
     * @return string
     */
    public function col_fullname($row) {
        return $row->fullname;
    }

    /**
     * Returns a json for rendering the filter elements.
     *
     * @return void
     */
    public function return_filterjson() {

        $filtercolumns = [];

        // We have stored the columns to filter in the subcolumn "datafields".
        if (!isset($this->subcolumns['datafields'])) {
            return '';
        }

        // Here, we create the filter first like this:
        // For every field we want to filter for, we look in our rawdata...
        // ... to fetch all the available values once.
        foreach ($this->subcolumns['datafields'] as $key => $value) {

            // We won't generate a filter for the id column, but it will be present because we need it as dataset.
            if ($key == 'id') {
                continue;
            }

            $filtercolumns[$key] = [];

            foreach ($this->rawdata as $row) {

                $row = (array)$row;

                if (empty($row[$key])) {
                    continue;
                }

                if (!isset($filtercolumns[$key][$row[$key]])) {

                    $filtercolumns[$key][$row[$key]] = true;
                }
            }
        }

        $filterjson = ['categories' => array()];
        foreach ($filtercolumns as $key => $values) {

            $categoryobject = [
                'name' => $key,
                'values' => []
            ];
            foreach ($values as $valuekey => $valuevalue) {

                $itemobject = [
                    'key' => $valuekey,
                    'value' => $valuekey,
                    'category' => $key
                ];

                $categoryobject['values'][$valuekey] = $itemobject;

            }

            // To sort it and make it mustache ready, we have to jump through loops.
            ksort($categoryobject['values']);
            $categoryobject['values'] = array_values($categoryobject['values']);

            $filterjson['categories'][] = $categoryobject;
        }

        return json_encode($filterjson);
    }

    /**
     * Set the sql to query the db. Query will be :
     *      SELECT $fields FROM $from WHERE $where
     * Of course you can use sub-queries, JOINS etc. by putting them in the
     * appropriate clause of the query.
     * @param string $fields
     * @param string $from
     * @param string $where
     * @param array $params
     * @param string $filter
     * @return void
     */
    public function set_filter_sql(string $fields, string $from, string $where, array $params = array(), string $filter) {
        $this->sql = new stdClass();
        $this->sql->fields = $fields;
        $this->sql->from = $from;
        $this->sql->where = $where;
        $this->sql->filter = $filter;
        $this->sql->params = $params;
    }

    /**
     * Applies the filter we got via webservice as jsonobject to the sql object.
     *
     * @param string $filter
     * @return void
     */
    public function apply_filter(string $filter) {

        global $DB;

        if (!$filterobject = json_decode($filter)) {
            throw new moodle_exception('invalidfilterjson', 'local_wunderbyte_table');
        }

        $filter = '';

        foreach ($filterobject as $key => $value) {

            // Make sure we can use ou
            $paramsvaluekey = 'param';

            while (isset($this->sql->params[$paramsvaluekey])) {
                $paramsvaluekey .= '1';
            }

            $filter .= " AND " . $DB->sql_like("$key", ":$paramsvaluekey");
            $this->sql->params[$paramsvaluekey] = $value;
        }

        if (empty($this->sql->filter)) {
            $this->sql->filter = $filter;
        } else {
            $this->sql->filter .= $filter;
        }
    }


    /**
     * Copy of the parent function, but we don't automatically set the pagesize.
     * @param int $perpage
     * @param int $total
     * @return void
     */
    public function pagesize($perpage, $total) {
        $this->pagesize  = $perpage;
        $this->totalrows = $total;
        // $this->use_pages = true;
    }
}
