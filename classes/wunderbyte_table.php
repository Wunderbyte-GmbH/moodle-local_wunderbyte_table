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
 * @copyright 2023 Wunderbyte Gmbh <info@wunderbyte.at>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_wunderbyte_table;

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/tablelib.php");

use cache;
use Exception;
use local_wunderbyte_table\output\lazytable;
use local_wunderbyte_table\output\table;
use moodle_exception;
use table_sql;
use local_wunderbyte_table\output\viewtable;
use moodle_url;
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
     * @var int number of total records found.
     */
    private $totalrecords = 0;

    /**
     * @var string number of filtered records. We need to know if altered or just 0.
     */
    private $filteredrecords = -1;

    /**
     *
     * @var array array of formated rows.
     */
    public $formatedrows = [];

    /**
     *
     * @var string The string of a json object to output the filter.
     */
    public $filterjson = null;

    /**
     *
     * @var int Specify the number of records which should be loaded at once (like on a page). 0 is don't use it.
     */
    public $infinitescroll = 0;

    /**
     *
     * @var bool Show a label where number of totalrows and filtered rows are displayed.
     */
    public $showcountlabel = false;

    /**
     *
     * @var bool Show elements to download the table.
     */
    public $showdownloadbutton = false;

    /**
     *
     * @var bool Show elements to reload the table.
     */
    public $showreloadbutton = false;

    /**
     *
     * @var string Set height of table.
     */
    public $tableheight = '';

    /**
     *
     * @var bool Use sticky header.
     */
    public $stickyheader = false;

    /**
     *
     * @var bool Add checkboxes to select single columns.
     */
    public $addcheckboxes = false;

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
    public $tabletemplate = 'local_wunderbyte_table/twtable_list';

    /**
     *
     * @var moodle_url fallback url for downloading.
     */
    public $baseurl = null;

    /**
     * Card sort is a special sort element
     * Used when there are now table headers displayed.
     * This can only be determined manually.
     */
    public $cardsort = false;

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
     * array array of [classidentifier => classname] to use in mustache template on table level.
     *
     * @var array
     */
    public $fulltextsearchcolumns = [];

    /**
     * tabellib saves the string in the moodle_url class, which we can't encode, so we have to translate it.
     *
     * @var string
     */
    public $baseurlstring = '';

    /**
     * Sortable columns.
     *
     * @var array
     */
    public $sortablecolumns = [];

    /**
     * Filtersortorder columns.
     *
     * @var array
     */
    public $filtersortorder = [];

    /**
     * Legacy from table_sql.
     *
     * @var bool
     */
    public $useinitialsbar = true;

    /**
     *
     * @var bool
     */
    public $downloadhelpbutton = true;

    /**
     *
     * @var string
     */
    public $tablecachehash = '';

    /**
     *
     * @var array $actionbuttons
     */
    public $actionbuttons = [];

  /**
     * Errormessage in case of.
     *
     * @var string
     */
    public $errormessage = '';

  /**
     * Number of rows diplayed per page in table.
     *
     * @var boolean
     */
    public $showrowcountselect = false;


    /**
     * Constructor. Does store uniqueid as hashed value and the actual classname.
     *
     * @param string $uniqueid
     */
    public function __construct($uniqueid) {
        parent::__construct($uniqueid);

        $this->idstring = md5($uniqueid);
        $this->classname = get_class($this);

        // This is a fallback for the downloading function. A different baseurl can be defined later in the process.
        $this->define_baseurl(new moodle_url('/local/wunderbyte_table/download.php'));
    }

    /**
     * New lazyout function just stores the settings as json in base64 format and creates a table.
     * It also attaches the necessary javascript to fetch the actual table via ajax afterwards.
     *
     * @param int $pagesize
     * @param bool $useinitialsbar
     * @param string $downloadhelpbutton
     * @return void
     */
    public function lazyout($pagesize, $useinitialsbar, $downloadhelpbutton = '') {

        list($idnumber, $encodedtable, $html) = $this->lazyouthtml($pagesize, $useinitialsbar, $downloadhelpbutton);

        echo $html;
    }

    /**
     * With this function, the table can be printed without lazy loading.
     *
     * @param int $pagesize
     * @param bool $useinitialsbar
     * @param string $downloadhelpbutton
     * @return string
     */
    public function out($pagesize, $useinitialsbar, $downloadhelpbutton = '') {

        echo self::outhtml($pagesize, $useinitialsbar, $downloadhelpbutton);
    }

    /**
     * With this function, the table can be returned as html without lazy loading.
     * Can be overridden in child class with own renderer.
     *
     * @param int $pagesize
     * @param bool $useinitialsbar
     * @param string $downloadhelpbutton
     * @return string
     */
    public function outhtml($pagesize, $useinitialsbar, $downloadhelpbutton = '') {

        global $PAGE, $CFG;
        $this->pagesize = $pagesize;
        $this->useinitialsbar = $useinitialsbar;
        $this->downloadhelpbutton = $downloadhelpbutton;

        // In the following function we return the template we want to use.
        // This function also checks, if there is a special container template present. If so, we use it instead.
        list($component, $template) = $this->return_component_and_template();

        $tableobject = $this->printtable($pagesize, $useinitialsbar);
        $output = $PAGE->get_renderer('local_wunderbyte_table');
        return $output->render_table($tableobject, $component . "/" . $template);
    }

    /**
     * A version of the out function which does not actually echo but just returns the html plus the idnumber.
     * This is only used for lazy loading.
     *
     * @param int $pagesize
     * @param bool $useinitialsbar
     * @param string $downloadhelpbutton
     * @return array
     */
    public function lazyouthtml($pagesize, $useinitialsbar, $downloadhelpbutton = '') {

        global $PAGE, $CFG;
        $this->pagesize = $pagesize;
        $this->useinitialsbar = $useinitialsbar;
        $this->downloadhelpbutton = $downloadhelpbutton;

        // Retrieve the encoded table.
        $tablecachehash = $this->return_encoded_table();

        $this->tablecachehash = $tablecachehash;
        $output = $PAGE->get_renderer('local_wunderbyte_table');
        $data = new lazytable($this->idstring, $tablecachehash);
        return [$this->idstring, $tablecachehash, $output->render_lazytable($data)];
    }

    /**
     * This is a copy of the old ->out method.
     * We need it to really print the table, when we override the new out with ajax-functions.
     *
     * @param [type] $pagesize
     * @param [type] $useinitialsbar
     * @param string $downloadhelpbutton
     * @return table
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

        // At this point, we check if we need to add the checkboxes.
        if ($this->addcheckboxes && !$this->is_downloading()) {
            $columns = array_keys($this->columns);
            $headers = $this->headers;
            array_unshift($columns, 'wbcheckbox');
            array_unshift($headers, get_string('tableheadercheckbox', 'local_wunderbyte_table'));
            $this->columns = [];
            $this->define_columns($columns);
            $this->headers = $headers;
        }

        $this->pagesize = $pagesize;
        $this->setup();

        $this->apply_filter_and_search_from_url();

        $encodedtable = $this->return_encoded_table();

        // First we query without the filter.
        $this->query_db_cached($this->pagesize, $useinitialsbar);

        $this->build_table();
        $this->close_recordset();
        return $this->finish_output(true, $encodedtable);
    }

    /**
     * You should call this to finish outputting the table data after adding
     * data to the table with add_data or add_data_keyed.
     * @param boolean $closeexportclassdoc
     * @param string $encodedtable
     * @return table
     */
    public function finish_output($closeexportclassdoc = true, $encodedtable = '') {
        if ($this->exportclass !== null) {
            $this->exportclass->finish_table();
            if ($closeexportclassdoc) {
                $this->exportclass->finish_document();
            }
        } else {
            return new table($this, $encodedtable);
        }
    }

    /**
     * Sets $this->baseurl.
     * @param moodle_url|string $url the url with params needed to call up this page
     */
    public function define_baseurl($url) {
        if (gettype($url) === 'string') {
            $this->baseurl = new moodle_url($url);
        } else {
            $this->baseurl = $url;
        }

        $this->baseurlstring = $this->baseurl->out();
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
     * @param bool $addtocolumns
     * @return void
     */
    public function add_subcolumns(string $subcolumnsidentifier, array $subcolumns, bool $addtocolumns = true) {
        if (strlen($subcolumnsidentifier) == 0) {
            throw new moodle_exception('nosubcolumidentifier', 'local_wunderbyte_table', null, null,
                    "You need to specify a columnidentifer like cardheader or cardfooter");
        }
        foreach ($this->columns as $key => $value) {
            $columns[] = $key;
        }

        foreach ($subcolumns as $key => $value) {

            if (gettype($value) == 'array') {
                $this->subcolumns[$subcolumnsidentifier][$key] = $value;
                $columns[] = $key;
            } else {
                $this->subcolumns[$subcolumnsidentifier][$value] = [];
                $columns[] = $value;
            }
        }

        if ($addtocolumns) {
             // This is necessary to make sure we create the right content.
            parent::define_columns($columns);
        }
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
            $this->add_classes_to_subcolumns('cardbody', ['columnclass' => 'columnclass']);
            // This avoids showing all keys in list view.
            $this->add_classes_to_subcolumns('cardbody', ['columnkeyclass' => 'columnkeyclass']);
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
        $this->add_subcolumns('cardheader', $columns, false);

        $this->headers = $columns;
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
        $this->formatedrows = [];
        foreach ($this->rawdata as $key => $rawrow) {
            $formattedrow = $this->format_row($rawrow);
            $this->formatedrows[$key] = $formattedrow;

            if ($this->is_downloading()) {
                $this->add_data_keyed($formattedrow,
                $this->get_row_class($rawrow));
            }
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
     *
     * The filtercolumns can, suppelementary to just the values...
     * ... also hold more information about the way the categories are displayed.
     * First we can add a sortorder, second we can add a string for localisation.
     * @param array $filtercolumns
     * @return void
     */
    public function define_filtercolumns(array $filtercolumns) {

        $this->add_subcolumns('datafields', $filtercolumns, false);

    }

    /**
     * Define the columns for the fulltext search. This does not have to be rendered, so we don't add it als subcolumn.
     * @return void
     */
    public function define_fulltextsearchcolumns(array $fulltextsearchcolumns) {

        $this->fulltextsearchcolumns = $fulltextsearchcolumns;

    }

    /**
     * Define the columns for the sorting.
     * @return void
     */
    public function define_sortablecolumns(array $sortablecolumns) {

        $this->sortablecolumns = $sortablecolumns;

    }

    /**
     * Add fulltext search.
     *
     * @return void
     */
    private function setup_fulltextsearch() {

        global $DB;

        $searchcolumns = $this->fulltextsearchcolumns;

        if (!empty($searchcolumns) && count($searchcolumns)) {

            foreach ($searchcolumns as $key => $value) {

                $searchcolumns[$key] = "COALESCE(" . $value . ", ' ')";

            }

            $searchcolumns = array_values($searchcolumns);

            $this->sql->fields .= " , " . $DB->sql_concat_join("' '", $searchcolumns) . " as wbfulltextsearch ";
        }

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

        global $CFG;

        // We might run this function twice to generate the filter options.
        // Therefore, we need to store the values we actually want to use temporary.
        $setbackvalues = false;

        // First create hash of all relevant entries.
        $sort = $this->get_sql_sort();
        if ($sort) {
            $sort = "ORDER BY $sort";
        }

        // If we haven't a filter json yet and we have filters defined as usepages is right now true...
        // ... we set it for a moment to false.
        // We do so because we need to get the whole table for once. We'll run the same function again twice...
        // ... and the next time, the pagination will be applied.
        if (isset($this->subcolumns['datafields'])
            && !$this->filterjson
            && (($this->use_pages == true) || $this->infinitescroll > 0) || !empty($this->sql->filter)) {

            // For the caching to work over all pages, we need to set the currpage to null for the filter request.
            // Else the hash value would not match and we would not have filtering of filterjson over the different pages.
            $currpage = $this->currpage;
            $usepages = $this->use_pages;

            $this->use_pages = false;
            $this->currpage = null;

            $setbackvalues = true;
        } else {
            // If we don't cache, we need to set infinite scroll at this point.

            // If we want to use infinite scroll, we need to fetch the current page.
            // We use the same functionality as for just loading the page itself.
            if ($this->infinitescroll > 0) {
                $pagesize = $this->infinitescroll;
                $this->use_pages = true;
            }
        }

        // Create the query string including params.
        $sql = "SELECT
                {$this->sql->fields}
                FROM {$this->sql->from}
                WHERE {$this->sql->where}
                {$sort}"
                // . json_encode($this->sql->params)
                . $pagesize
                . $useinitialsbar
                . $this->download
                . $this->currpage
                . $this->use_pages;

        // We might run a truncated sql which does not use all the params.
        // To use caching in this case, we need to exclude all params not used in this sql.
        $params = $this->sql->params;

        foreach ($params as $key => $value) {
            // If the key is an int, we can't run this.
            if (!is_int($key)) {
                // We only exclude it when we are sure that it's really there.
                if (!strpos($sql, ':'. $key . ' ')
                    && !strpos($sql, ':'. $key . ')')
                    && !strpos($sql, ':'. $key . PHP_EOL)) {
                        unset($params[$key]);
                }
            }
        }

        $sql .= json_encode($params);

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

                if ($CFG->debug > 0) {
                    $this->errormessage .= $e->getMessage();
                } else {
                    $this->errormessage = get_string('somethingwentwrong', 'local_wunderbyte_table');
                }

                $this->rawdata = [];
            }

            // After the query, we set the result to the.
            // But only, if we have a cache by now.
            if ($this->cachecomponent
                && $this->rawcachename
                && $cache) {

                // Only set cachekey when rawdata is bigger than 0.
                if (count($this->rawdata) > 0) {
                    $cache->set($cachekey, $this->rawdata);
                }

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

        // We store the total number of records.
        // In the first tour, totalrecords will aways be 0.
        // If we filter, the second value will be lower or the same.
        if ($this->totalrecords > 0) {
            $this->filteredrecords = $this->totalrows;
        } else {
            $this->totalrecords = $this->totalrows;

            // We have to reset the count sql.
            $this->set_count_sql(null, []);
        }

        // We have stored the columns to filter in the subcolumn "datafields".
        // If we have filters defines, we need to actually create a filter json.
        // It might exist already in our DB. We have to create this from all data, without filter applied.

        if (isset($this->subcolumns['datafields']) && !$this->filterjson) {

            // We need to localize the filter for every user.
            $lang = current_language();

            $this->filterjson = $this->return_filterjson();
            /*
            if (!$this->filterjson = $cache->get($cachekey . '_filterjson')) {
                // Now we create the filter json from the unfiltered json.
                $cache->set($cachekey . '_filterjson', $this->filterjson);
            }
            */
        }

        // If we have chosen this value above, we want to run the code again.
        // The first time, we got rawdata but it's without the filter applied.
        // We still use it.
        if ($setbackvalues) {

            $this->sql->where .= $this->sql->filter ?? '';
            // To avoid another run, we have to set filter to empty now.
            $this->sql->filter = '';

            $this->use_pages = $usepages ?? $this->use_pages; // We set back the old value.
            $this->currpage = $currpage ?? $this->currpage;

            // If we want to use infinite scroll, we need to fetch the current page.
            // We use the same functionality as for just loading the page itself.
            if ($this->infinitescroll > 0) {
                $pagesize = $this->infinitescroll;
                $this->use_pages = true;
            }

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

            if (strtolower($key) == 'id') {
                continue;
            }

            if (isset($value['datepicker'])) {
                $filtercolumns[$key] = 'datepicker';
                continue;
            }

            $filtercolumns[$key] = [];

            foreach ($this->rawdata as $row) {

                $row = (array)$row;

                // Do not use empty(...) here because we want to show 0 values.
                if (!isset($row[$key]) || $row[$key] === null || $row[$key] === '') {
                    continue;
                }

                if (!isset($filtercolumns[$key][$row[$key]])) {

                    $filtercolumns[$key][$row[$key]] = true;
                }
            }
        }

        $filterjson = ['categories' => array()];

        foreach ($filtercolumns as $fckey => $values) {

            // Special treatment for key localizedname.
            if (isset($this->subcolumns['datafields'][$fckey]['localizedname'])) {
                $localizedname = $this->subcolumns['datafields'][$fckey]['localizedname'];
                unset($this->subcolumns['datafields'][$fckey]['localizedname']);
            } else {
                $localizedname = $fckey;
            }

            $categoryobject = [
                'name' => $localizedname, // Localized name.
                'columnname' => $fckey, // The column name.
                'collapsed' => 'collapsed',
            ];

            if (is_string($values) && $values === 'datepicker') {

                $datepickerarray = $this->subcolumns['datafields'][$fckey];

                foreach ($datepickerarray['datepicker'] as $labelkey => $object) {

                    if (!isset($object['columntimestart'])) {
                        $defaulttimestamp = $datepickerarray['datepicker'][$labelkey]['defaultvalue'];

                        $datepickerobject = [
                            'label' => $labelkey,
                            'operator' => $datepickerarray['datepicker'][$labelkey]['operator'],
                            'timestamp' => $defaulttimestamp,
                            'datereadable' => $defaulttimestamp === 'now' ? 'now': date('Y-m-d', $defaulttimestamp),
                            'timereadable' => $defaulttimestamp === 'now' ? 'now': date('H:i', $defaulttimestamp),
                            'checkboxlabel' => $datepickerarray['datepicker'][$labelkey]['checkboxlabel'],
                        ];

                    } else { // Inbetween Filter applied.
                        // Prepare the array for output
                        if (empty($datepickerarray['datepicker'][$labelkey]['possibleoperations'])) {
                            $datepickerarray['datepicker'][$labelkey]['possibleoperations'] = ['within', 'overlapboth', 'overlapstart', 'overlapend', 'before', 'after'];
                        }
                        $operationsarray = array_map(fn($y) => ['operator' => $y, 'label' => get_string($y, 'local_wunderbyte_table')], $datepickerarray['datepicker'][$labelkey]['possibleoperations']);

                        
                        $datepickerobject = [
                            'label' => $labelkey,
                            'startcolumn' => $datepickerarray['datepicker'][$labelkey]['columntimestart'],
                            'starttimestamp' => $datepickerarray['datepicker'][$labelkey]['defaultvaluestart'],
                            'startdatereadable' => $datepickerarray['datepicker'][$labelkey]['defaultvaluestart'] === 'now' ? 'now': date('Y-m-d', $datepickerarray['datepicker'][$labelkey]['defaultvaluestart']),
                            'starttimereadable' => $datepickerarray['datepicker'][$labelkey]['defaultvaluestart'] === 'now' ? 'now': date('H:i', $datepickerarray['datepicker'][$labelkey]['defaultvaluestart']),
                            'endcolumn' => $datepickerarray['datepicker'][$labelkey]['columntimeend'],
                            'endtimestamp' => $datepickerarray['datepicker'][$labelkey]['defaultvalueend'],
                            'enddatereadable' => $datepickerarray['datepicker'][$labelkey]['defaultvalueend'] === 'now' ? 'now': date('Y-m-d', $datepickerarray['datepicker'][$labelkey]['defaultvalueend']),
                            'endtimereadable' => $datepickerarray['datepicker'][$labelkey]['defaultvalueend'] === 'now' ? 'now': date('H:i', $datepickerarray['datepicker'][$labelkey]['defaultvalueend']),
                            'checkboxlabel' => $datepickerarray['datepicker'][$labelkey]['checkboxlabel'],
                            'possibleoperations' => $operationsarray, // Array
                        ];
                    }

                    $categoryobject['datepicker']['datepickers'][] = $datepickerobject;
                }

            } else if (is_array($values)) {
                // We might need to explode values, because of a multi-field.
                if (isset($this->subcolumns['datafields'][$fckey]['explode'])
                    || self::check_if_multi_customfield($fckey)) {

                    // We run through the array of values and explode each item.
                    foreach ($values as $keytoexplode => $valuetoexplode) {

                        $separator = $this->subcolumns['datafields'][$fckey]['explode'] ?? ',';

                        $explodedarray = explode($separator, $keytoexplode);

                        // Only if we have more than one item, we unset key and insert all the new keys we got.
                        if (count($explodedarray) > 1) {
                            // Run through all the keys.
                            foreach ($explodedarray as $explodeditem) {

                                // Make sure we don't have any empty values.
                                $explodeditem = trim($explodeditem);

                                if (empty($explodeditem)) {
                                    continue;
                                }

                                $values[$explodeditem] = true;
                            }
                            // We make sure the strings with more than one values are not treated anymore.
                            unset($values[$keytoexplode]);
                        }
                    }

                    unset($this->subcolumns['datafields'][$fckey]['explode']);
                }

                // If we have JSON, we need special treatment.
                if (!empty($this->subcolumns['datafields'][$fckey]['jsonattribute'])) {
                    $valuescopy = $values;
                    $values = [];

                    // We run through the array of values containing the JSON strings.
                    foreach ($valuescopy as $jsonstring => $boolvalue) {
                        // Convert into an array, so we can handle items with multiple objects.
                        $jsonstring = '[' . $jsonstring . ']';
                        $jsonarray = json_decode($jsonstring);

                        foreach ($jsonarray as $jsonobj) {
                            if (empty($jsonobj)) {
                                continue;
                            }
                            // We only want to show the attribute of the JSON which is relevant for the filter.
                            $searchattribute = $jsonobj->{$this->subcolumns['datafields'][$fckey]['jsonattribute']};
                            $values[$searchattribute] = true;
                        }
                    }

                    unset($this->subcolumns['datafields'][$fckey]['json']);
                }

                // We have to check if we have a sortarray for this filtercolumn.
                if (isset($this->subcolumns['datafields'][$fckey])
                            && count($this->subcolumns['datafields'][$fckey]) > 0) {

                                    $sortarray = $this->subcolumns['datafields'][$fckey];
                } else {
                    $sortarray = null;
                }

                // First we create our sortedarray and add all values in the right order.
                if ($sortarray != null) {
                    $sortedarray = [];
                    foreach ($sortarray as $sortkey => $sortvalue) {
                        if (isset($values[$sortkey])) {
                            $sortedarray[$sortvalue] = $sortkey;

                            unset($values[$sortkey]);
                        }
                    }

                    // Now we make sure we havent forgotten any values.
                    // If so, we sort them and add them at the end.
                    if (count($values) > 0) {
                        // First sort the values first.
                        ksort($values);

                        foreach ($values as $unsortedkey => $unsortedvalue) {
                            $sortedarray[$unsortedkey] = true;
                        }
                    }

                    // Finally, we pass the sorted array to the values back.
                    $values = $sortedarray;
                }

                foreach ($values as $valuekey => $valuevalue) {

                    $itemobject = [
                        'key' => $valuekey,
                        'value' => $valuevalue === true ? $valuekey : $valuevalue,
                        'category' => $fckey
                    ];

                    $categoryobject['default']['values'][$valuekey] = $itemobject;
                }

                if (!isset($categoryobject['default']) || count($categoryobject['default']['values']) == 0) {
                    continue;
                }

                if ($sortarray == null) {
                    // If we didn't sort otherwise, we do it now.
                    ksort($categoryobject['default']['values']);
                }

                // Make the arrays mustache ready, we have to jump through loops.
                $categoryobject['default']['values'] = array_values($categoryobject['default']['values']);
            }
            $filterjson['categories'][] = $categoryobject;
        }

        return json_encode($filterjson);
    }

    /**
     * This function finds out the column after which the current table is sorted at the moment and returns the present sortorder.
     *
     * @return null|int
     */
    public function return_current_sortorder() {

        global $SESSION;

        $sortorder = null;

        // We need the flextable session to get the sortorder.
        if (isset($SESSION->flextable[$this->idstring])) {
            $prefs = $SESSION->flextable[$this->idstring];
        } else {
            return null;
        }

        // Return the currently used sortorder.
        // We only need the first one.
        foreach ($prefs['sortby'] as $key => $value) {
            $currentcolumname = $key;
            $sortorder = $value;
            break;
        }

        return $sortorder;
    }



    /**
     * Save the filter sortoder here.
     *
     * @param array $categories
     * @return void
     */
    public function define_sortorder_filter(array $categories) {
        $this->filtersortorder = $categories;
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
    public function set_filter_sql(string $fields, string $from, string $where, string $filter, array $params = array()) {

        $this->set_sql($fields, $from, $where, $params);
        $this->sql->filter = $filter;
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
        $paramkey = 'param';

        foreach ($filterobject as $categorykey => $categoryvalue) {

            if (!empty($categoryvalue)) {
                // For the first filter in a category we append AND.
                $filter .= " AND ( ";
                $paramcounter = 1;
                $categorycounter =1;

                // We check if we are applying a timestamp comparison which is stored in an object.
                $datecomparison = false;
                if (is_object($categoryvalue)) {
                    $datecomparison = true;}

                foreach ($categoryvalue as $key => $value) {
                    // We use the while function to find a param we can actually use.
                    $paramsvaluekey = $paramkey . $paramcounter;

                    // datecomp == true

                    if ($datecomparison == false) {
                        // If there are more than one filter per category they will be concatenated via OR.
                        $filter .= $categorycounter == 1 ? "" : " OR ";
                    } else {
                        // Except if we filter for time values, in which case they will be concatenated via AND.
                        $filter .= $categorycounter == 1 ? "" : " AND ";
                    }

                    while (isset($this->sql->params[$paramkey . $paramcounter])) {
                        $paramcounter++;
                        $paramsvaluekey = $paramkey . $paramcounter;
                    }

                    if ($datecomparison == true) {
                        $filter .= $categorykey . ' ' . key($value) . ' ' . current($value);
                    } else if (is_numeric($value)) {
                        $filter .= $DB->sql_like($DB->sql_concat($categorykey), ":$paramsvaluekey", false);
                        $this->sql->params[$paramsvaluekey] = "". $value;
                    } else if (isset($this->subcolumns['datafields'][$categorykey]['explode'])) {
                        $filter .= $DB->sql_like("$categorykey", ":$paramsvaluekey", false);
                        $this->sql->params[$paramsvaluekey] = "%$value%";
                    }  else {
                        $filter .= $DB->sql_like("$categorykey", ":$paramsvaluekey", false);
                        $this->sql->params[$paramsvaluekey] = "$value";
                    }
                    $categorycounter++;
                }
                $filter .= " ) ";
            }
        }

        if (empty($this->sql->filter)) {
            $this->sql->filter = $filter;
        } else {
            $this->sql->filter .= $filter;
        }
    }

    /**
     * Applies the searchtext we got via webservice as jsonobject to the sql object.
     * This code actually adds a created fulltext searchcolumn to the sql. we need to encapsulate it to make it searchable.
     *
     * @param string $filter
     * @return void
     */
    public function apply_searchtext(string $searchtext) {

        global $DB;

        if (empty($searchtext)) {
            throw new moodle_exception('invalidsearchtext', 'local_wunderbyte_table');
        }

        $this->setup_fulltextsearch();

        // Add the fields/Select to the FROM part.
        $from = " ( SELECT " . $this->sql->fields . " FROM " . $this->sql->from;

        // Add the new container here.
        $fields = " DISTINCT fulltextsearchcontainer.* ";

        // And close it in from..
        $from .= " ) fulltextsearchcontainer ";

        $filter = " AND ( ";

        $searchtext = trim($searchtext);

        $searcharray = explode(' ', $searchtext);

        // We add the parts of the filter to this array, to be able to implode it afterwards.
        $filterarray = [];

        foreach ($searcharray as $searchword) {

            // Make sure we can use the param.
            $originalparamsvaluekey = 'param';
            $paramsvaluekey = $originalparamsvaluekey;

            $counter = 1;

            while (isset($this->sql->params[$paramsvaluekey])) {
                $paramsvaluekey = $originalparamsvaluekey . $counter;
                $counter++;
            }

            $filterarray[] = $DB->sql_like("wbfulltextsearch", ":$paramsvaluekey", false);
            $this->sql->params[$paramsvaluekey] = "%$searchword%";

        }

        // Now we have the filterarray with all the filters for every word.
        // We implode it with AND, because all the words should be in the column.

        if (count($filterarray) > 1) {
            $filter .= ' ( ';
            $filter .= implode(' ) AND ( ', $filterarray);
            $filter .= ' ) ';
        } else {
            $filter .= reset($filterarray);
        }

        $filter .= " ) ";

        if (!empty($this->sql->filter)) {
            $filter = $this->sql->filter . $filter;
        }

        // We have to use this function to apply the sql at the right place.
        $this->set_filter_sql($fields, $from, $this->sql->where, $filter, $this->sql->params);
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
    }

    /**
     * This function returns custom comonent and template of instance.
     * If there is a _container template present, this templatename_container is returned.
     *
     * @throws moodle_exception
     * @return array
     */
    private function return_component_and_template() {

        global $CFG;

        if (!empty($this->tabletemplate)) {
            list($component, $template) = explode("/", $this->tabletemplate);
        }

        if (empty($component) || empty($template)) {
            throw new moodle_exception('wrongtemplatespecified', 'local_wunderbyte_table', '', $this->tabletemplate);
        }

        $componentarray = explode("_", $component);
        $componenttype = array_shift($componentarray);
        $componentname = implode("_", $componentarray);

        // First, we get all the available conditions from our directory.
        $path = $CFG->dirroot . '/' . $componenttype . '/' . $componentname . '/templates//' . $template . '_container.mustache';
        $filelist = glob($path);

        if (count($filelist) === 1) {
            $template = $template . '_container';
        }

        return [$component, $template];
    }

    /**
     * Encode the wholetable class and output it.
     *
     * @return string
     */
    public function return_encoded_table():string {

        global $USER;

        // We don't want errormessage in the encoded table.
        $this->errormessage = '';

        if (empty($this->tablecachehash)) {
            $cache = cache::make('local_wunderbyte_table', 'encodedtables');
            $this->tablecachehash = md5($USER->id . $this->idstring);

            if ($cashedtable = $cache->get($this->tablecachehash)) {
                $this->pagesize = $cashedtable->pagesize;
            } else {

                // Make sure that we don't use old filter params.
                $filter = $this->sql->filter ?? '';
                $this->sql->filter = '';
                $cache->set($this->tablecachehash, $this);
                $this->sql->filter = $filter;
            }
        }

        // We need to urlencode everything to make it proof.
        return $this->tablecachehash;
    }

    /**
     * Checks if a config shortname exists and if so, checks for configdata to see, if it's set to multi.
     *
     * @param string $columnname
     * @return bool
     */
    private static function check_if_multi_customfield($columnname) {
        global $DB;

        $configmulti = $DB->sql_like('configdata', ":param1");
        $params = ['param1' => '%multiselect\":\"1\"%'];

        $sql = "SELECT id
                FROM {customfield_field}
                WHERE shortname='$columnname'
                AND $configmulti";

        if (!$DB->record_exists_sql($sql, $params)) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Return an arraf of the count of the total records and the filtered records.
     *
     * @return array
     */
    public function return_records_count() {

        $totalrecords = $this->totalrecords;
        $filteredrecords = $this->filteredrecords === -1 ? $totalrecords : $this->filteredrecords;

        return [$totalrecords, $filteredrecords];
    }

    /**
     * This handles the colum checkboxes.
     *
     * @param stdClass $values
     * @return void
     */
    public function col_wbcheckbox($values) {

        global $OUTPUT;

        $data['id'] = $values->id;
        $data['label'] = '';
        $data['name'] = 'row-'.$this->uniqueid.'-'.$values->id;
        $data['checkboxclass'] = '';
        $data['checked'] = !empty($values->checkbox) ? true : false;
        $data['tableid'] = $this->idstring;

        return $OUTPUT->render_from_template('local_wunderbyte_table/col_checkbox', $data);;
    }

    /**
     * Change number of rows. Uses the transmitaction pattern (actionbutton).
     * @param integer $id
     * @param string $data
     * @return array
     */
    public function rownumberperpage(int $id, string $data):array {

        $jsonobject = json_decode($data);
        $this->pagesize = $jsonobject->numberofrowsselect;

        // Overwrite cached table object.
        $cache = cache::make('local_wunderbyte_table', 'encodedtables');
        $cache->set($this->tablecachehash, $this);

        return [
            'success' => 1,
            'message' => 'Did work',
        ];
    }
    /**
     * This returns an instance of wunderbyte table or child class.
     *
     * @param string $tablecachehash
     * @return wunderbyte_table
     */
    public static function instantiate_from_tablecache_hash(string $tablecachehash) {

        $cache = cache::make('local_wunderbyte_table', 'encodedtables');
        $class = $cache->get($tablecachehash);

        return $class;
    }

    /**
     * If we have filter or search params in the URL, they will be applied.
     *
     * @return void
     */
    private function apply_filter_and_search_from_url() {
        $wbtfilter = optional_param('wbtfilter', '', PARAM_RAW);
        $wbtsearch = optional_param('wbtsearch', '', PARAM_RAW);

        if (!empty($wbtfilter)) {
            $this->apply_filter($wbtfilter);
        }

        if (!empty($wbtsearch)) {
            $this->apply_searchtext($wbtsearch);
        }
}


}
