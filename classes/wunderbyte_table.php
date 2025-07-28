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
use local_wunderbyte_table\local\sortables\sortable_info;
use mod_booking\singleton_service;

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/tablelib.php");

use cache;
use context_system;
use Exception;
use local_wunderbyte_table\event\table_viewed;
use local_wunderbyte_table\output\lazytable;
use local_wunderbyte_table\output\table;
use moodle_exception;
use table_sql;
use moodle_url;
use stdClass;
use coding_exception;
use local_wunderbyte_table\event\template_switched;
use local_wunderbyte_table\filters\base;
use local_wunderbyte_table\local\sortables\base as basesort;
use local_wunderbyte_table\filters\types\standardfilter;
use local_wunderbyte_table\local\settings\tablesettings;

/**
 * Wunderbyte table class is an extension of table_sql.
 */
class wunderbyte_table extends table_sql {
    /**
     * Provide const for sortorder ASC.
     */
    public const SORTORDER_ASC = 4;

    /**
     * Provide const for sortorder DESC.
     */
    public const SORTORDER_DESC = 3;

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
    public $totalrecords = 0;

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
     * @var bool Show the download button at the bottom of the table (on top is the default).
     */
    public $showdownloadbuttonatbottom = false;

    /**
     *
     * @var bool Show a label where number of totalrows and filtered rows are displayed.
     */
    public $showfilterontop = false;

    /**
     *
     * @var bool Scroll to top of container.
     */
    public $scrolltocontainer = true;

    /**
     *
     * @var bool Show the Components toggle.
     */
    public $showfilterbutton = true;

    /**
     *
     * @var bool Show elements to download the table.
     */
    public $showdownloadbutton = false;

    /**
     *
     * @var bool Show button to add individual custom filters.
     */
    public $showaddfilterbutton = false;

    /**
     *
     * @var bool Apply filter on download.
     */
    public $applyfilterondownload = false;

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
     * @var bool Rows can be sorted.
     */
    public $sortablerows = false;

    /**
     * Sortables.
     *
     * @var array
     */
    public $sortables = [];

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
    public $tabletemplate = 'local_wunderbyte_table/twtable_list'; // Default template.

    /**
     *
     * @var moodle_url fallback url for downloading.
     */
    public $baseurl = null;

    /**
     * Card sort is a special sort element
     * Used when there are now table headers displayed.
     * This can only be determined manually.
     * @var bool special sort element.
     */
    public $cardsort = false;

    /**
     * Require Login is a security feature which normally is turned on.
     * @var bool requirelogin
     */
    public $requirelogin = true;

    /**
     * Require capability is a security feature which defaults to the standard capability.
     * @var string requirecapability
     */
    public $requirecapability = 'local/wunderbyte_table:canaccess';

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
     * Filters.
     *
     * @var array
     */
    public $filters = [];

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
     * @var bool
     */
    public $showrowcountselect = false;

    /**
     * Inactive filter display on load.
     *
     * @var bool
     */
    public $filteronloadinactive = false;

    /**
     * Filter to be applied from URL
     *
     * @var string
     */
    public $urlfilter = '';

    /**
     * Search to be applied from URL
     *
     * @var string
     */
    public $urlsearch = '';

    /**
     * Display Actionbuttons, Pagination and Rowcount on top of table.
     *
     * @var bool
     */
    public $placebuttonandpageelementsontop = false;

    /**
     * We need to store the context in the class.
     * @var \context
     */
    public $context;

    /**
     * We need to store the context in the class.
     * @var int
     */
    public $paramcounter = 1;

    /**
     * Searchtext to be applied.
     * @var string
     */
    public $searchtext = '';

    /**
     * Show pagination
     * @var bool
     */
    public $showpagination = true;

    /**
     * Show gotopage selectbox
     *
     * This will be displayed only if pagination is enabled, the "goto page" setting is true,
     * and there is more than one page.
     * @var bool
     */
    public $gotopage = false;

    /**
     * Additional template data.
     * @var array
     */
    public $templatedata = [];

    /**
     * Array of templates for template switcher.
     * @var array
     */
    public $switchtemplates = [];

    /**
     * Constructor. Does store uniqueid as hashed value and the actual classname.
     * The $uniqueid should be composed by ASCII alphanumeric characters, underlines and spaces only!
     * It is recommended to avoid of usage of simple single words like "table" to reduce chance of affecting by Moodle`s core CSS
     *
     * @param string $uniqueid Has to be really unique eg. by adding the cmid, so it's unique over all instances of one plugin!
     */
    public function __construct($uniqueid) {

        global $PAGE;

        // We will not break working code but have to inform developers about potentially severe issue.
        if (debugging() && preg_match('#[^a-zA-Z0-9_\s]#', $uniqueid)) {
            throw new coding_exception(
                "Variable uniqueid should be composed by ASCII alphanumeric characters, underlines and spaces only!",
                $uniqueid
            );
        }

        // We always add the contextid to the table.
        $this->context = $PAGE->context;

        parent::__construct($uniqueid);

        $this->idstring = md5($uniqueid . $this->context->id ?? 1);
        $this->classname = get_class($this);

        // This unsets the eventual memory of sorting in session to apply the default sorting on load as defined.
        $this->unset_sorting_settings();

        // This is a fallback for the downloading function. A different baseurl can be defined later in the process.
        $this->define_baseurl(new moodle_url('/local/wunderbyte_table/download.php'));

        $standardfilter = new standardfilter('id');
        $this->add_filter($standardfilter);

        // If a user preference for the table template is set, we use it.
        $chosentemplate = get_user_preferences('wbtable_chosen_template_' . $this->uniqueid);
        if (
            !empty($this->switchtemplates['templates'])
            && !empty($chosentemplate)
            && self::template_exists($chosentemplate)
        ) {
            $this->tabletemplate = $chosentemplate;
        }
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

        [$idnumber, $encodedtable, $html] = $this->lazyouthtml($pagesize, $useinitialsbar, $downloadhelpbutton);

        echo $html;
    }

    /**
     * With this function, the table can be printed without lazy loading.
     *
     * @param int $pagesize
     * @param bool $useinitialsbar
     * @param string $downloadhelpbutton
     * @return void
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
        [$component, $template] = $this->return_component_and_template();

        $tableobject = $this->printtable($pagesize, $useinitialsbar);
        $output = $PAGE->get_renderer('local_wunderbyte_table');
        return $output->render_table($tableobject, $component . "/" . $template);
    }


    /**
     * With this function, the table can be returned as html without lazy loading.
     * Can be overridden in child class with own renderer.
     *
     * @param int $pagesize
     * @param bool $useinitialsbar
     * @param string $downloadhelpbutton
     * @param array $onlyfilters
     * @return string
     */
    public function filterouthtml($pagesize, $useinitialsbar, $downloadhelpbutton = '', $onlyfilters = []) {

        global $PAGE, $CFG;
        $this->pagesize = $pagesize;
        $this->useinitialsbar = $useinitialsbar;
        $this->downloadhelpbutton = $downloadhelpbutton;

        // In the following function we return the template we want to use.
        // This function also checks, if there is a special container template present. If so, we use it instead.
        [$component, $template] = $this->return_component_and_template();

        $tableobject = $this->printtable($pagesize, $useinitialsbar);

        $tableobject->filter_filter($onlyfilters);

        $output = $PAGE->get_renderer('local_wunderbyte_table');
        return $output->render_table($tableobject, $component . "/" . $template);
    }

    /**
     * With this function, the table can be returned as html without lazy loading.
     * Can be overridden in child class with own renderer.
     *
     * @param int $pagesize
     * @param bool $useinitialsbar
     * @param string $downloadhelpbutton
     *
     * @return string
     */
    public function calendarouthtml(int $pagesize, bool $useinitialsbar, string $downloadhelpbutton = '') {

        global $PAGE, $OUTPUT;
        $this->pagesize = 30;
        $this->useinitialsbar = $useinitialsbar;
        $this->downloadhelpbutton = $downloadhelpbutton;

        // In the following function we return the template we want to use.
        // This function also checks, if there is a special container template present. If so, we use it instead.
        [$component, $template] = $this->return_component_and_template();

        $tableobject = $this->printtable($pagesize, $useinitialsbar);
        $data = $tableobject->return_as_list();

        $rawdata = $this->rawdata;
        $rowswithdates = [];
        foreach ($rawdata as $rowraw) {
            $rowdata = singleton_service::get_instance_of_booking_option_settings($rowraw->id);
            if (count($rowdata->sessions) > 0) {
                foreach ($rowdata->sessions as $session) {
                    $url = new moodle_url('/mod/booking/optionview.php', ['optionid' => $rowdata->id,
                                                                              'cmid' => $rowdata->cmid]);
                    $session->url = $url->out(false);
                    array_push($rowswithdates, $session);
                }
            }
        }
        $data['rowswithdates'] = json_encode($rowswithdates);
        if (isset($data['table']['rows'])) {
            $allrows = $data['table']['rows'];
            if ($allrows && count($allrows) > 0) {
                $data['table']['rows'] = array_slice($allrows, 0, 4);
            }
        }

        return $OUTPUT->render_from_template($component . "/" . $template, $data);
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

        // Check if we have optional params from URL.
        $this->urlfilter = optional_param('wbtfilter', '', PARAM_TEXT);
        $this->urlsearch = optional_param('wbtsearch', '', PARAM_TEXT);

        if (
            ($this->urlfilter !== '' && !empty($this->urlfilter))
            || ($this->urlsearch !== '' && !empty($this->urlsearch))
        ) {
            $tablecachehash = $this->return_encoded_table(true);
        } else {
            $tablecachehash = $this->return_encoded_table();
        }

        // Retrieve the encoded table.
        $this->tablecachehash = $tablecachehash;
        $output = $PAGE->get_renderer('local_wunderbyte_table');
        $data = new lazytable($this->idstring, $tablecachehash, $this->infinitescroll);
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

        $encodedtable = $this->return_encoded_table();

        tablesettings::apply_setting($this);

        if (!$this->columns) {
            $onerow = $DB->get_record_sql(
                "SELECT {$this->sql->fields} FROM {$this->sql->from} WHERE {$this->sql->where}",
                $this->sql->params,
                IGNORE_MULTIPLE
            );
            // If columns is not set then define columns as the keys of the rows returned.
            // From the db.
            $this->define_columns(array_keys((array)$onerow));
        }

        sortable_info::apply_sortables($this);

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

        // At this point, we check if we need to add the drag areas.
        if ($this->sortablerows && !$this->is_downloading()) {
            $columns = array_keys($this->columns);
            $headers = $this->headers;
            array_unshift($columns, 'wbsortableitem');
            array_unshift($headers, get_string('tableheadersortableitem', 'local_wunderbyte_table'));
            $this->columns = [];
            $this->define_columns($columns);
            $this->headers = $headers;
        }

        $this->pagesize = $pagesize;
        $this->setup();

        // First we query without the filter.
        $this->query_db_cached($this->pagesize, $useinitialsbar);

        $this->build_table();
        $this->close_recordset();

        return $this->finish_output(true, $encodedtable);
    }

    /**
     * You should call this to finish outputting the table data after adding
     * data to the table with add_data or add_data_keyed.
     * @param bool $closeexportclassdoc
     * @param string $encodedtable
     * @return table|void
     */
    public function finish_output($closeexportclassdoc = true, $encodedtable = '') {

        global $USER;

        // At this point, we trigger the table_viewed event.
        $context = $this->get_context();
        $event = table_viewed::create([
            'context' => $context,
            'userid' => $USER->id,
            'other' => [
                'tablename' => $this->uniqueid,
            ],
        ]);
        $event->trigger();

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
     * Get the context for the table.
     *
     * Note: This function _must_ be overridden by dynamic tables to ensure that the context is correctly determined
     * from the filterset parameters.
     *
     * @return \context
     */
    public function get_context(): \context {
        global $PAGE;

        return $this->context;
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
            throw new moodle_exception(
                'nosubcolumidentifier',
                'local_wunderbyte_table',
                null,
                null,
                "You need to specify a columnidentifer like cardheader or cardfooter"
            );
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
     * @param bool $usestandardclasses
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
     * @param bool $usestandardclasses
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
     * @param ?array $subcolumns
     * @param bool $replace
     * @return void
     */
    public function add_classes_to_subcolumns(
        string $subcolumnsidentifier,
        array $classes,
        ?array $subcolumns = null,
        $replace = false
    ) {
        if (strlen($subcolumnsidentifier) == 0) {
            throw new moodle_exception(
                'nosubcolumidentifier',
                'local_wunderbyte_table',
                null,
                null,
                "You need to specify a columnidentifer like cardheader or cardfooter"
            );
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
                    throw new moodle_exception(
                        'nokeyvaluepairinclassarray',
                        'local_wunderbyte_table',
                        null,
                        null,
                        "The classarray has to have the form classidentifier => classname, where {{classidentifier}}
                        needs to be present in your mustache template."
                    );
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
                $this->add_data_keyed(
                    $formattedrow,
                    $this->get_row_class($rawrow)
                );
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
     * @param ?string $rawcachename
     * @param ?string $renderedcachename
     * @return void
     */
    public function define_cache(string $componentname, ?string $rawcachename = null, ?string $renderedcachename = null) {

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
     * @param base $filter
     * @param bool $invisible
     * @return void
     * @throws moodle_exception
     */
    public function add_filter(base $filter, $invisible = false) {

        $filtercolumns = $this->subcolumns['datafields'] ?? [];

        $filter->add_filter($filtercolumns, $invisible);

        $this->add_subcolumns('datafields', $filtercolumns, false);

        if ($filter->hascallback) {
            $this->filters[$filter->return_columnidentifier()] = $filter;
        }
    }

    /**
     * Define the columns for which an automatic filter should be generated.
     * We just store them as subcolumns of type datafields. In the mustache template these fields must be added to every...
     * ... row or card element, so it can be hidden or shown via the integrated filter mechanism..
     * @param basesort $sortable
     *
     * @return void
     *
     */
    public function add_sortable(basesort $sortable) {

        $sortablecolumns = $this->sortablecolumns ?? [];

        $sortable->add_sortable($sortablecolumns);

        $this->sortablecolumns = $sortablecolumns;

        $this->sortables[$sortable->return_columnidentifier()] = $sortable;
    }

    /**
     * Hides the entire filter.
     * This is not like toggling on and off on start, but there will be just no filter at all.
     * @return void
     * @throws moodle_exception
     */
    public function hide_filter() {

        $this->subcolumns['datafields']['id']['id_wb_checked'] = 0;
    }

    /**
     * Define the columns for the fulltext search. This does not have to be rendered, so we don't add it als subcolumn.
     * @param array $fulltextsearchcolumns
     *
     * @return void
     */
    public function define_fulltextsearchcolumns(array $fulltextsearchcolumns) {

        $this->fulltextsearchcolumns = $fulltextsearchcolumns;
    }

    /**
     * Define the columns for the sorting.
     * @param array $sortablecolumns
     *
     * @return void
     */
    public function define_sortablecolumns(array $sortablecolumns) {

        foreach ($sortablecolumns as $key => $value) {
            $this->sortablecolumns[$key] = $value;
        }
    }

    /**
     * Add fulltext search.
     *
     * @return string
     */
    private function setup_fulltextsearch() {

        global $DB, $CFG;

        $searchcolumns = $this->fulltextsearchcolumns;

        if (!empty($searchcolumns) && count($searchcolumns)) {
            foreach ($searchcolumns as $key => $value) {
                // Check Moodle version to determine compatibility.
                if ($CFG->version > 2022112800) {
                    // Use sql_cast_to_char, available since Moodle 4.1.
                    $valuestring = $DB->sql_cast_to_char($value);
                } else {
                    // Handle databases differently based on DB type.
                    if ($DB->get_dbfamily() === 'mysql') {
                        // For MySQL, use CAST as CHAR.
                        $valuestring = "CAST(" . $value . " AS CHAR)";
                    } else {
                        // For other DB types, use CAST as VARCHAR.
                        $valuestring = "CAST(" . $value . " AS VARCHAR)";
                    }
                }

                // Prepare the column string with COALESCE.
                $searchcolumns[$key] = "COALESCE(" . $valuestring . ", ' ')";
            }

            $searchcolumns = array_values($searchcolumns);

            return  $DB->sql_concat_join("' '", $searchcolumns) . " as wbfulltextsearch ";
        }
        return '';
    }

    /**
     * This calls the parent query_db function, but only after checking for cached queries.
     * This function can and should be overriden if your plugin needs different cache treatment.
     *
     * @param int $pagesize
     * @param bool $useinitialsbar
     * @return void
     */
    public function query_db_cached($pagesize, $useinitialsbar = true) {

        global $CFG, $DB, $PAGE, $USER;

        // At this point, we need seperate the unfiltered sql and the filtered sql and create respective cachekeys.
        // The sepearation of the sql is important because it allows us to distinguish ...
        // ... between filtered and unfiltered calls.
        // They will both use the same data for the filter, though.
        filter::create_filter($this);

        // We store our totalcountsql here, we might need it only later.
        $totalcountsql = "SELECT COUNT(*)
                         FROM {$this->sql->from}
                         WHERE {$this->sql->where}";

        // Apply filter and search text.
        $this->apply_filter_and_search_from_url();

        // When we have a callback sort in place, we need to fetch all records.
        // In order to avoid overloading, it would be best to still have a limit (eg. 10000).
        // So we set the pagesize to the right value.

        $usepages = $this->use_pages || $this->infinitescroll > 0;

        // The Callback filter is applied on the existing records.
        // The callback filter updates $this->rawdata.
        $callbacksorting = false;

        $repeat = true;
        $initialcurrpage = $this->currpage;
        $unfilteredrawdata = [];

        // Check if we'll use a callback filter.

        $callbackfilter = false;
        foreach ($this->filters as $filter) {
            if ($filter->expectedvalue !== null) {
                $callbackfilter = true;
                // On a callbackfilter, we always need to start with a 0 page.
                // We need to iterate through all pages.
                if ($usepages) {
                    $this->currpage = 0;
                }
                break;
            }
        }

        while (
            $repeat
            || $callbacksorting
            || (
                $callbackfilter
                // Rawdata must be bigger than 0 on the second run, else we simply ran out of records.
                && count($unfilteredrawdata) == $this->pagesize
                // If we don't use pages, we don't need to repeat.
                && $usepages
                // If we don't have a pagesize, we don't need to repeat.
                && $this->pagesize > 0
                // If we have less records than the pagesize, we don't need to repeat.
                // && (count($this->rawdata) < $this->pagesize)
                   // If we have less total records than the pagesize times curr page, we don't need to repeat.
                && ($this->totalrows > ($this->currpage * $this->pagesize))
            )
        ) {
            if (
                !$repeat
                // This is to protect against repeating the call when there are just not enough records.
            ) {
                $this->currpage++;
            }
            // This previousrawdata is the one we got from the last iteration.
            // It's already filtered.
            $previousdata ??= [];
            $this->query_db_cached_filtered($this->pagesize, $useinitialsbar, $totalcountsql);
            $unfilteredrawdata = $this->rawdata;
            foreach ($this->filters as $filter) {
                $this->rawdata = $filter->filter_by_callback($this->rawdata);
            }

            // We need to retrieve the id of the records. normally, it's 'id', but for sure it's the first column.
            $probableid ??= array_key_first((array)reset($this->rawdata));
            // Here we combine the data we got from the previous run and the current one.
            foreach ($this->rawdata as $record) {
                if (!isset($previousdata[$record->{$probableid}])) {
                    $previousdata[$record->{$probableid}] = $record;
                }
            }

            // On the first run we don't need to act.
            if (!$repeat) {
                // We only add the number of elements we need to reach the pagesize.
                $this->rawdata = array_slice($previousdata, $initialcurrpage * $this->pagesize, $this->pagesize);
            } else {
                // Repeat should be false on the second run.
                $repeat = false;
            }
        }

        // After the callback filter, we might have reduced the number of records.
        // But we still want to return the correct number of records, we need to look at hte next page.
        if ($this->currpage !== $initialcurrpage) {
            $this->totalrows = count($previousdata);
            $this->currpage = $initialcurrpage;
        }

        $this->filteredrecords = empty($filter) ? $this->totalrows : count($this->rawdata);
    }

    /**
     * More precise function to query the database and cache the results.
     *
     * @param int $pagesize
     * @param bool $useinitialsbar
     * @param string $totalcountsql
     *
     * @return void
     *
     */
    private function query_db_cached_filtered(int $pagesize, bool $useinitialsbar, string $totalcountsql) {
        global $DB, $USER, $CFG, $PAGE;
        // Now we proceed to the actual sql query.
        $filter = $this->sql->filter ?? '';
        $this->sql->where .= " $filter ";
        $this->pagesize = $pagesize;
        $cachekey = $this->create_cachekey();
        $pagesize = $this->pagesize;

        // And then we query our cache to see if we have it already.
        if (
            !get_config('local_wunderbyte_table', 'turnoffcaching')
            && $this->cachecomponent
            && $this->rawcachename
        ) {
            $cache = cache::make($this->cachecomponent, $this->rawcachename);
            $cachedrawdata = $cache->get($cachekey);
        } else {
            $cachedrawdata = false;
            $cache = false;
        }

        // Pagination might have been set independend from cachedrawdata.
        // Because if there is no result, we don't save rawdata.
        // But we still want to save pagination.
        $paginationset = $this->get_pagination_from_cache($cachekey);

        if ($cachedrawdata !== false) {
            // If so, just return it.
            $this->rawdata = (array)$cachedrawdata;

            // If we hit the cache, we may increase the count for debugging reasons.
            if (
                get_config('local_wunderbyte_table', 'logfiltercaches')
                && (count($this->rawdata) > 0)
            ) {
                if (
                    $record = $DB->get_record(
                        'local_wunderbyte_table',
                        ['hash' => $cachekey],
                        'id, count'
                    )
                ) {
                    $count = $record->count + 1;
                    unset($record->count);
                    $record->count = $count; // COUNT is a reserved keyword in MariaDB, so use quotes.
                    $record->timemodified = time();
                    $DB->update_record('local_wunderbyte_table', $record);
                }
            }
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
            if (
                $this->cachecomponent
                && $this->rawcachename
                && $cache
            ) {
                // Only set cachekey when rawdata is bigger than 0.

                if (count($this->rawdata) > 0) {
                    $cache->set($cachekey, $this->rawdata);
                    if (get_config('local_wunderbyte_table', 'logfiltercaches')) {
                        $sql = $this->get_sql_for_cachekey();

                        // For testing, we save the filter settings at this point.
                        $url = $PAGE->url->out();
                        $now = time();
                        $data = (object)[
                            'hash' => $cachekey,
                            'tablehash' => $this->tablecachehash,
                            'idstring' => $this->idstring,
                            'userid' => 0,
                            'page' => (string) $this->context->id,
                            'jsonstring' => json_encode($this->sql),
                            '\'sql\'' => $sql, // SQL is a reserved keyword in MariaDB, so use quotes.
                            'usermodified' => (int) $USER->id,
                            'timecreated' => $now,
                            'timemodified' => $now,
                            'count' => 1, // COUNT is a reserved keyword in MariaDB, so use quotes.
                        ];
                        if (
                            $record = $DB->get_record(
                                'local_wunderbyte_table',
                                [
                                    'hash' => $cachekey,
                                    'page' => $this->context->id,
                                ],
                                'id, count'
                            )
                        ) { // COUNT is a reserved keyword in MariaDB, so use quotes.
                            $count = $record->count + 1;
                            unset($record->count);
                            $record->count = $count; // COUNT is a reserved keyword in MariaDB, so use quotes.
                            $record->timemodified = time();
                            $DB->update_record('local_wunderbyte_table', $record);
                            $dontinsert = true;
                        } else {
                            $DB->insert_record('local_wunderbyte_table', $data);
                        }
                    }
                }
            }

            if (!$paginationset) {
                $this->totalrecords = $DB->count_records_sql($totalcountsql, $this->sql->params);
                $this->set_pagination_to_cache($cachekey);
            }
        }
    }

    /**
     * Sets the pagination values of the class from the cache.
     * Returns false if no cache was found.
     * @param string $cachekey
     * @return bool
     * @throws coding_exception
     */
    private function get_pagination_from_cache(string $cachekey) {

        $cache = \cache::make($this->cachecomponent, $this->rawcachename);
        if ($pagination = $cache->get($cachekey . '_pagination')) {
            $this->pagesize = $pagination['pagesize'];
            $this->totalrows = $pagination['totalrows'];
            $this->currpage = $pagination['currpage'];
            $this->use_pages = $pagination['use_pages'];
            $this->totalrecords = $pagination['totalrecords'];
            return true;
        } else {
            return false;
        }
    }

    /**
     * Set pagination to cache.
     * @param string $cachekey
     * @return void
     * @throws coding_exception
     */
    private function set_pagination_to_cache(string $cachekey) {

        $cache = \cache::make($this->cachecomponent, $this->rawcachename);

        $pagination['pagesize'] = $this->pagesize;
        $pagination['totalrecords'] = $this->totalrecords;
        $pagination['totalrows'] = $this->totalrows;
        $pagination['currpage'] = $this->currpage;
        $pagination['use_pages'] = $this->use_pages;
        $cache->set($cachekey . '_pagination', $pagination);
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
     * This function finds out the column after which the current table is sorted at the moment and returns the present sortorder.
     *
     * @return null|int
     */
    public function return_current_sortorder() {

        global $SESSION;

        $sortorder = null;

        // We need the flextable session to get the sortorder.
        if (isset($SESSION->flextable[$this->uniqueid])) {
            $prefs = $SESSION->flextable[$this->uniqueid];
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
     *
     * @param string $fields
     * @param string $from
     * @param string $where
     * @param string $filter
     * @param array $params
     *
     * @return void
     */
    public function set_filter_sql(string $fields, string $from, string $where, string $filter, array $params = []) {

        $this->set_sql($fields, $from, $where, $params);
        $this->sql->filter = $filter;
    }

    /**
     * Applies the filter we got via webservice as jsonobject to the sql object.
     *
     * @param string $filter
     * @param string $searchtext
     * @return void
     */
    public function apply_filter(string $filter, string &$searchtext = '') {

        global $DB;
        $lang = filter::current_language();
        $key = $this->tablecachehash . $lang . "_filterjson";
        $filtersettings = editfilter::return_filtersettings($this, $key);

        if ($filter !== "" && !$filterobject = json_decode($filter)) {
            throw new moodle_exception('invalidfilterjson', 'local_wunderbyte_table');
        }
        if (!isset($filterobject)) {
            $filterobject = new stdClass();
        }

        $_POST['wbtfilter'] = $filter;

        if (get_config('local_wunderbyte_table', 'allowsearchincolumns')) {
            if (!$searchtext == '') {
                // Separator defines which character seperates key (columnname) from value (searchterm).
                $separator = ":";
                $remainingstring = $searchtext;
                // If the separator is in the searchstring, we check if we get params to apply as filter.
                if (strpos($searchtext, $separator) !== false) {
                    $characterstoreplace = ["'", '„', '“'];
                    $replacements = ['"', '"', '"'];
                    $searchtext = str_replace($characterstoreplace, $replacements, $searchtext);

                    $regex = '/(?|"([^"]+)"|(\w+))' . $separator . '(?:"([^"]+)"|([^,\s]+))/';
                    $initialsearchtext = $searchtext;
                    $columnname = '';
                    $value = '';
                    preg_match_all($regex, $searchtext, $matches, PREG_SET_ORDER);

                    // Combining defined columns and their localized names.
                    // If you get an error here you have a problem with the definition of your headers and columns,
                    // they must be exactly the same.
                    $columns = array_combine(array_keys($this->columns), array_values($this->headers));

                    foreach ($matches as $match) {
                        // Assigning the values the columnname and value.
                        $columnname = $match[1];
                        $value = $match[2];
                        if ($match[2] == "") {
                            $value = $match[3];
                        }

                        // Checking if we find a doublequote after the semicolon.
                        $quotedvalue = false;
                        $separatorposition = strpos($match[0], $separator);
                        if ($separatorposition !== false) {
                            $separatorposition++;
                            if ($separatorposition < strlen($match[0])) {
                                $characterafter = $match[0][$separatorposition];
                                if ($characterafter == '"') {
                                    $quotedvalue = true;
                                }
                            }
                        }

                        if (
                            !$quotedvalue && // Value is unquoted.
                            !filter_var($value, FILTER_VALIDATE_INT) && // And not a number.
                            !filter_var($value, FILTER_VALIDATE_FLOAT)
                        ) {
                            $value = "%" . $value . "%"; // Add wildcards.
                        }

                        // Check if searchstring column corresponds to localized name. If so set columnname.
                        if (in_array($columnname, $columns)) {
                            $columnname = array_search($columnname, $columns);
                        } else if (
                            !array_key_exists($columnname, $columns)
                            || !array_key_exists(strtolower($columnname), $columns)
                        ) {
                            // Or columnname.
                            continue;
                        }

                        if (property_exists($filterobject, $columnname)) {
                            if (!in_array($value, $filterobject->$columnname)) {
                                $filterobject->{$columnname}[] = $value;
                            }
                        } else {
                            $filterobject->{$columnname}[] = $value;
                        }

                        // Check if there is a string remaining after getting key and value.
                        if (isset($match[0]) && is_string($match[0])) {
                            $remainingstring = str_replace($match[0], "", $remainingstring);
                        }
                    }
                }
            }
            $searchtext = trim($remainingstring);
            $this->searchtext = $searchtext;
        }
        // If we don't get filter values to apply from searchtext or filter, end of function.
        if (isset($initialsearchtext)) {
            if ($initialsearchtext == $searchtext && $filter == "") {
                return;
            }
        }

        $filter = '';
        $paramkey = 'param';

        // This handles the case of flexoverlap filter. Datepicker.
        $foobject = [];
        foreach ($filterobject as $categorykey => $categoryvalue) {
            if (!is_object($categoryvalue)) {
                continue;
            }
            foreach ($categoryvalue as $filtername => $filterarray) {
                foreach ($filterarray as $key => $value) {
                    if ($key == "fo") {
                        $foobject[$categorykey] = $value;
                        if (count((array)$filterobject->$categorykey) > 1) {
                            unset($filterobject->$categorykey->$filtername);
                        } else {
                            unset($filterobject->$categorykey);
                        }
                    }
                }
            }
        }
        // Define the filter string. Datepicker.
        if (count($foobject) > 1) {
            $sc = array_keys($foobject)[0]; // Startcolumn.
            $ec = array_keys($foobject)[1]; // Endcolumn.
            $sf = array_values($foobject)[0]; // Startfilter.
            $ef = array_values($foobject)[1]; // Endfilter.

            // In order to make sure we are dealing with real column names and no sql injection...
            // ... we check against column names.
            if (
                in_array($sc, array_keys($this->columns))
                && in_array($ec, array_keys($this->columns))
            ) {
                $fparam = 'fparam';
                $fcounter = 1;
                while (isset($this->sql->params[$fparam . 'sf1' . $fcounter])) {
                    $fcounter++;
                }

                $sfkey1 = $fparam . 'sf1' . $fcounter;
                $sfkey2 = $fparam . 'sf2' . $fcounter;
                $sfkey3 = $fparam . 'sf3' . $fcounter;

                $this->sql->params[$sfkey1] = $sf;
                $this->sql->params[$sfkey2] = $sf;
                $this->sql->params[$sfkey3] = $sf;

                $efkey1 = $fparam . 'ef1' . $fcounter;
                $efkey2 = $fparam . 'ef2' . $fcounter;
                $efkey3 = $fparam . 'ef3' . $fcounter;

                $this->sql->params[$efkey1] = $ef;
                $this->sql->params[$efkey2] = $ef;
                $this->sql->params[$efkey3] = $ef;

                $filter .= " AND (
                    (:$sfkey1 <= $sc AND :$efkey1 >= $sc) OR
                    (:$sfkey2 <= $ec AND :$efkey2 >= $ec) OR
                    (:$sfkey3 >= $sc AND :$efkey3 <= $ec)
                ) ";
            }
        }

        foreach ($filterobject as $categorykey => $categoryvalue) {
            if (!empty($categoryvalue)) {
                // For the first filter in a category we append AND.
                $filter .= " AND ( ";
                $categorycounter = 1;

                $filtersetting = $filtersettings[$categorykey] ?? [];
                // For filters treating two columns (i.e. datepickers), this will return empty.
                $classname = $filtersetting['wbfilterclass'] ?? "";

                if (!empty($classname)) {
                    if (isset($this->filters[$categorykey])) {
                        $class = $this->filters[$categorykey];
                    } else {
                        $class = new $classname($categorykey, $filtersetting['localizedname']);
                    }
                    $class->apply_filter($filter, $categorykey, $categoryvalue, $this);

                    // phpcs:ignore moodle.Commenting.TodoComment.MissingInfoInline
                    // TODO: Use apply_filter method for the remaining filter type datepicker.
                    // Eventually we will get rid of the following section.
                    // ... for the moment, make sure to escape it for classes already implementing the new way.
                    if (!strpos($classname, "datepicker")) {
                        $filter .= " ) ";
                        continue;
                    }
                }

                foreach ($categoryvalue as $key => $value) {
                    $filter .= ($categorycounter == 1) ? "" : " AND ";
                    $valuecounter = 1;
                    if (is_object($value) || is_array($value)) {
                        foreach ($value as $operator => $timestamp) {
                            // Time values will be concatenated via AND.
                            $filter .= ($valuecounter == 1) ? "" : " AND ";

                            $filter .= $categorykey . ' ' . $operator . ' ' . $timestamp;
                            $valuecounter++;
                        }
                    } else {
                        $filter .= $categorycounter == 1 ? "" : " AND ";

                        $filter .= $categorykey . ' ' . key((array) $value) . ' ' . current((array) $value);
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
     * @param string $searchtext
     * @return void
     */
    public function apply_searchtext(string $searchtext) {

        global $DB;

        if (empty($searchtext)) {
            throw new moodle_exception('invalidsearchtext', 'local_wunderbyte_table');
        }
        $this->searchtext = $searchtext;
        $newselect = $this->setup_fulltextsearch();

        // Add the fields/Select to the FROM part.
        $from = " ( SELECT " . $this->sql->fields . " , $newselect FROM " . $this->sql->from;

        // Add the new container here.
        $fields = " DISTINCT fulltextsearchcontainer.* ";
        $this->sql->fields = $fields;

        // And close it in from..
        $from .= " ) fulltextsearchcontainer ";

        $filter = " AND ( ";

        $searchtext = trim($searchtext);

        $searcharray = explode(' ', $searchtext);

        // We add the parts of the filter to this array, to be able to implode it afterwards.
        $filterarray = [];

        foreach ($searcharray as $searchword) {
            $paramsvaluekey = $this->set_params("%" . $searchword . "%", true);
            $filterarray[] = $DB->sql_like("wbfulltextsearch", ":$paramsvaluekey", false);
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

        // If a user preference for the table template is set, we use it.
        $chosentemplate = get_user_preferences('wbtable_chosen_template_' . $this->uniqueid);
        if (
            !empty($this->switchtemplates['templates'])
            && !empty($chosentemplate)
            && self::template_exists($chosentemplate)
        ) {
            $chosentemplate = get_user_preferences('wbtable_chosen_template_' . $this->uniqueid);
            $this->tabletemplate = $chosentemplate;
        }

        if (!empty($this->tabletemplate)) {
            [$component, $template] = explode("/", $this->tabletemplate);
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
     * @param bool $newcache
     * @return string
     */
    public function return_encoded_table($newcache = false) {

        // We don't want errormessage in the encoded table.
        $this->errormessage = '';

        $this->recreateidstring();

        if (empty($this->tablecachehash) || $newcache) {
            $cache = cache::make('local_wunderbyte_table', 'encodedtables');

            // We need to make sure that the correct instance with the correct capabilities are cached.
            // Therefore, we add the capability to the hash.
            $this->tablecachehash = md5($this->idstring . $this->requirecapability ?? '' . $this->requirelogin ?? '');

            // We just fetch the pagesize, no need to get all the table here.
            if (($pagesize = $cache->get($this->tablecachehash . '_pagesize')) && !$newcache) {
                $this->pagesize = $pagesize;
            } else {
                // Make sure that we don't use old filter params.
                $filter = $this->sql->filter ?? '';
                $this->sql->filter = '';

                $cache->set($this->tablecachehash, $this);
                $cache->set($this->tablecachehash . '_pagesize', $this->pagesize);

                // Reassign those properties we didn't want to cache.
                $this->sql->filter = $filter;
            }
        }

        // We need to urlencode everything to make it proof.
        return $this->tablecachehash;
    }

    /**
     * Return an array of the count of the total records and the filtered records.
     *
     * @return array
     */
    public function return_records_count() {

        $totalrecords = $this->totalrecords;
        $filteredrecords = $this->filteredrecords === -1 ? $totalrecords : $this->filteredrecords;

        return [$totalrecords, $filteredrecords];
    }

    /**
     * Return an array of the count of the total records and the filtered records.
     *
     * @return int
     */
    public function return_number_of_records() {

        if (isset($this->totalrows)) {
            return $this->totalrows;
        } else {
            return 0;
        }
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
        $data['name'] = 'row-' . $this->uniqueid . '-' . $values->id;
        $data['checkboxclass'] = '';
        $data['checked'] = !empty($values->checkbox) ? true : false;
        $data['tableid'] = $this->idstring;

        return $OUTPUT->render_from_template('local_wunderbyte_table/col_checkbox', $data);
        ;
    }

    /**
     * This handles the colum checkboxes.
     *
     * @param stdClass $values
     * @return void
     */
    public function col_wbsortableitem($values) {

        global $OUTPUT;

        $data['id'] = $values->id;
        $data['label'] = '';
        $data['name'] = 'row-' . $this->uniqueid . '-' . $values->id;
        $data['checkboxclass'] = '';
        $data['checked'] = !empty($values->checkbox) ? true : false;
        $data['tableid'] = $this->idstring;

        return $OUTPUT->render_from_template('local_wunderbyte_table/col_sortableitem', $data);
        ;
    }

    /**
     * Change number of rows. Uses the transmitaction pattern (actionbutton).
     * @param int $id
     * @param string $data
     * @return array
     */
    public function action_rownumberperpage(int $id, string $data): array {

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
     * Change number of rows. Uses the transmitaction pattern (actionbutton).
     * @param int $id
     * @param string $data
     * @return array
     */
    public function action_reorderrows(int $id, string $data): array {

        $jsonobject = json_decode($data);
        $ids = $jsonobject->ids;

        return [
            'success' => 1,
            'message' => 'This is just a demo, reordering has to be implemented for each table',
        ];
    }

    /**
     * Switch between templates.
     * @param int $id
     * @param string $data
     * @return array
     */
    public function action_switchtemplates(int $id, string $data): array {
        global $CFG, $USER;
        $jsonobject = json_decode($data);
        [$template, $viewparam] = explode(" ", $jsonobject->selectedValue);
        if (empty($template) || !self::template_exists($template)) {
            return [
                'success' => 0,
                'message' => 'Template could not be found!',
            ];
        }
        set_user_preference('wbtable_chosen_template_' . $this->uniqueid, $template);
        set_user_preference('wbtable_chosen_template_viewparam_' . $this->uniqueid, (int) $viewparam);

        $this->tabletemplate = $template;

        // When template is changed, we needd to re-cache the table.
        $cache = cache::make('local_wunderbyte_table', 'encodedtables');
        $cache->delete($this->tablecachehash);
        $tablecachehash = $this->return_encoded_table(true);

        // Trigger event, so we can react to it from other plugins.
        $event = template_switched::create([
            'context' => context_system::instance(),
            'userid' => $USER->id,
            'other' => [
                'tablecachehash' => $tablecachehash ?? '',
                'template' => $template ?? '',
                'viewparam' => $viewparam ?? 0,
            ],
        ]);
        $event->trigger();

        $returnarray['success'] = 1;
        // Can be added if needed.
        // phpcs:ignore Squiz.PHP.CommentedOutCode.Found
        /* if ($CFG->debug == DEBUG_DEVELOPER) {
            $returnarray['message'] = "template: " . get_user_preferences('wbtable_chosen_template_' . $this->uniqueid) .
                " viewparam: " . get_user_preferences('wbtable_chosen_template_viewparam_' . $this->uniqueid);
        } */
        return $returnarray;
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

        if (!empty($wbtfilter) || !empty($wbtsearch)) {
            $this->apply_filter($wbtfilter, $wbtsearch);
        }

        if (!empty($wbtsearch)) {
            $this->apply_searchtext($wbtsearch);
        }
    }
    /**
     * Unsetting all sorting settings from session.
     * Important for application of default sort params.
     *
     * @return void
     */
    public function unset_sorting_settings() {
        global $SESSION;
        if (isset($SESSION->flextable[$this->uniqueid])) {
            $SESSION->flextable[$this->uniqueid]['sortby'] = [];
        }
    }

    /**
     * We probe for an unused excape character.
     * @param string $paramvalue
     * @return string
     */
    public static function return_escape_character($paramvalue) {

        $values = ['\\', '@', '~', '[', ']'];

        foreach ($values as $value) {
            if (strpos($paramvalue, $value) === false) {
                return $value;
            }
        }
        return '\\';
    }

    /**
     * Function to create cachekey.
     * Every time we create a cachekey for an sql request...
     * ... we will also check if the filter for this request is created.
     * @param bool $forfilter
     * @param bool $useinitialsbar
     * @return string
     * @throws coding_exception
     */
    public function create_cachekey(bool $forfilter = false, bool $useinitialsbar = true) {

        $sql = $this->get_sql_for_cachekey($forfilter, $useinitialsbar);

        // Now that we have the string, we hash it with a very fast method.
        $cachekey = crc32($sql) . '_sqlquery';

        return $cachekey;
    }

    /**
     * Returns the sql to create the cachekey.
     * @param bool $forfilter
     * @param bool $useinitialsbar
     * @return string
     * @throws coding_exception
     */
    public function get_sql_for_cachekey(bool $forfilter = false, bool $useinitialsbar = true) {
        // If we run this for filter, we need have a reduced set of values.
        if ($forfilter) {
            $usepages = false;
            $sort = '';
            $currpage = '';
            $download = '';
            $pagesize = '';
        } else {
            // First create hash of all relevant entries.
            $sort = $this->get_sql_sort();
            if ($sort) {
                $sort = "ORDER BY $sort";
            }

            // If we want to use infinite scroll, we need to fetch the current page.
            // We use the same functionality as for just loading the page itself.
            if ($this->infinitescroll > 0) {
                $pagesize = $this->infinitescroll;
                $this->pagesize = $this->infinitescroll;
                $this->use_pages = true;
                $usepages = true;
            } else {
                $pagesize = $this->pagesize;
                $usepages = $this->use_pages;
            }

            $currpage = $this->currpage;
            $download = $this->download;
        }

        // Create the query string including params.
        $sql = "SELECT
                {$this->sql->fields}
                FROM {$this->sql->from}
                WHERE {$this->sql->where}
                {$sort}"
                . ($usepages ? $pagesize : '')
                . $useinitialsbar
                . $download
                . $currpage
                . $usepages;

        // We might run a truncated sql which does not use all the params.
        // To use caching in this case, we need to exclude all params not used in this sql.
        $params = $this->sql->params;

        self::unset_unused_params_in_sql($sql, $params);

        $sql .= json_encode($params);
        // We add the capability to the key to make sure no user with lesser capability can access data meant for higher access.
        $sql .= $this->requirecapability ?? '';

        return $sql;
    }

    /**
     * Get column names of all SQL columns of this table.
     * @return array Array of column names.
     */
    public function get_sql_column_names(): array {
        global $DB;

        $sql = "SELECT {$this->sql->fields} FROM {$this->sql->from}";
        $params = $this->sql->params ?? null;

        // Limit result to 1 row to reduce load.
        $limitedsql = $sql . ' LIMIT 1';
        // Get one row from the result.
        $row = $DB->get_record_sql($limitedsql, $params);
        if (!$row) {
            return [];
        }

        // Extract and return column names.
        return array_keys((array) $row);
    }

    /**
     * This function replaces the given idstring with anotherone which is recreateable from the settings of the table class.
     * This is useful when we have e.g. a table created via shortcodes. We don't know how many of them there will be.
     * Random idstrings will not allow configurability, but hardcoding is not possible either.
     * So we create the idstrings anew once we know the params and where they are created.
     * @return void
     */
    public function recreateidstring() {

        // This creates a hash from the sql settings.
        $cachekey = $this->create_cachekey(true);

        // We add the contextid.
        $idstring = md5($cachekey . $this->context->id ?? 1);

        $this->idstring = $idstring;
    }

    /**
     * Make sure only params which are actually needed are present in the array.
     * @param string $sql
     * @param array $params
     * @return void
     */
    public static function unset_unused_params_in_sql(string $sql, array &$params) {

        foreach ($params as $key => $value) {
            // If the key is an int, we can't run this.
            if (!is_int($key)) {
                // We only exclude it when we are sure that it's really there.
                if (
                    !strpos($sql, ':' . $key . ' ')
                    && !strpos($sql, ':' . $key . ')')
                    && !strpos($sql, ':' . $key . PHP_EOL)
                ) {
                        unset($params[$key]);
                }
            }
        }
    }

    /**
     * Set params with key for table.
     * You can use extra quotes added to the string or set the param without additional quotes.
     *
     * @param string $value
     * @param bool $useextraquotes
     *
     * @return string
     *
     */
    public function set_params(string $value, bool $useextraquotes = true): string {

        $paramsvaluekey = 'param1';
        while (isset($this->sql->params['param' . $this->paramcounter])) {
            $this->paramcounter++;
            $paramsvaluekey = 'param' . $this->paramcounter;
        }
        if ($useextraquotes) {
            $this->sql->params[$paramsvaluekey] = "$value";
        } else {
            $this->sql->params[$paramsvaluekey] = $value;
        }

        return $paramsvaluekey;
    }

    /**
     * Sets template data by key and value.
     *
     * @param string $key   The key under which the data will be stored.
     * @param mixed  $value The value to be stored under the specified key.
     */
    public function set_template_data($key, $value) {
        $this->templatedata[$key] = $value;
    }

    /**
     * Unsets template data.
     */
    public function unset_template_data() {
        unset($this->templatedata);
    }

    /**
     * Checks if a Mustache template exists for a given template.
     *
     * @param string $template The full template string, e.g. 'local_wunderbyte_table/twtable_list'.
     * @return bool True if the template exists, false otherwise.
     */
    public static function template_exists($template) {
        global $CFG;
        $templatearr = explode('/', $template);
        $component = $templatearr[0];
        $templatepath = $templatearr[1];
        $typearr = explode('_', $component);
        $type = array_shift($typearr);
        $pluginnamewithouttype = implode('_', $typearr);
        $templatefullpath = $CFG->dirroot . '/' . $type . '/' . $pluginnamewithouttype . '/templates/' .
            $templatepath . '.mustache';
        // Check if the file path is valid (non-empty) and if the file exists.
        return !empty($templatefullpath) && file_exists($templatefullpath);
    }

    /**
     * Add a template to the template switcher.
     *
     * @param string $template full template name, e.g. 'local_wunderbyte_table/twtable_list'
     * @param string $label    label for the template, e.g. 'List'
     * @param bool $selected   whether the template is selected by default
     * @param int $viewparam   an optional viewparam if you want to use the same template for different views
     */
    public function add_template_to_switcher(string $template, string $label, bool $selected = false, int $viewparam = 0) {
        $template = [
            'template' => $template,
            'label' => $label,
            'viewparam' => $viewparam,
        ];
        if ($selected) {
            $template['selected'] = true;
            // Make sure only one template is selected.
            if (!empty($this->switchtemplates['templates'])) {
                foreach ($this->switchtemplates['templates'] as &$existingtemplate) {
                    unset($existingtemplate['selected']);
                }
            }
        }
        // Now we can add the template to the switcher.
        $this->switchtemplates['templates'][] = $template;
    }
}
