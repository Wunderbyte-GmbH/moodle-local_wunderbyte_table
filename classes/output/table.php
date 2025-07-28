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
 * Class \local_wunderbyte_table\output\table
 *
 * @package    local_wunderbyte_table
 * @copyright  2023 Wunderbyte Gmbh <info@wunderbyte.at>
 * @author     Georg Maißer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 **/

namespace local_wunderbyte_table\output;

use core_plugin_manager;
use local_wunderbyte_table\editfilter;
use local_wunderbyte_table\local\settings\tablesettings;
use local_wunderbyte_table\wunderbyte_table;
use renderable;
use renderer_base;
use templatable;

/**
 * viewtable class to display view.php
 * @package local_wunderbyte_table
 *
 */
class table implements renderable, templatable {
    /**
     * idstring of the table, needed for output
     *
     * @var string
     */
    private $idstring = '';


    /**
     * uniqueid of the table, needed for output
     *
     * @var string
     */
    private $uniqueid = '';

    /**
     * encodedtable
     *
     * @var string
     */
    private $encodedtable = '';

    /**
     * baseurl
     *
     * @var string
     */
    private $baseurl = '';

    /**
     * Table is the array used for output.
     *
     * @var array
     */
    private $table = [];

    /**
     * Table is the array used for output.
     *
     * @var wunderbyte_table
     */
    private $wbtable;

    /**
     * Pagination is the array used for output.
     *
     * @var array
     */
    private $pagination = [];

    /**
     * categories are used for filter
     *
     * @var array
     */
    private $categories = [];

    /**
     * Search is to display search field
     *
     * @var bool
     */
    private $search = false;

    /**
     * Sort is to display the available sortcolumns.
     *
     * @var array
     */
    private $sort = [];

    /**
     * Reload is to display a button to reload the table
     *
     * @var bool
     */
    private $showreloadbutton = true;

    /**
     * Show filter button.
     *
     * @var bool
     */
    private $showfilterbutton = true;

    /**
     * Allow editing of filter & table
     *
     * @var bool
     */
    private $edittable = true;

    /**
     * Button to print table.
     *
     * @var bool
     */
    private $showdownloadbutton = true;

    /**
     * Button to add custom filters to table.
     *
     * @var bool
     */
    private $showaddfilterbutton = true;

    /**
     * Applyfilterondownload.
     *
     * @var bool
     */
    private $applyfilterondownload = false;

    /**
     * Countlabel.
     *
     * @var bool
     */
    private $showcountlabel = true;

    /**
     * Go to page.
     *
     * @var bool
     */
    private $gotopage = false;

    /**
     * Stickyheader.
     *
     * @var bool
     */
    private $stickyheader = true;

    /**
     * Tableheight.
     *
     * @var int
     */
    private $tableheight = '';

    /**
     * Filtered records.
     *
     * @var int
     */
    private $filteredrecords = '';

    /**
     * Total records.
     *
     * @var int
     */
    private $totalrecords = '';

    /**
     * Total records.
     *
     * @var int
     */
    private $totalrows = '';

    /**
     * Options data format
     *
     * @var array
     */
    private $printoptions = [];

    /**
     *
     * @var bool Show a label where number of totalrows and filtered rows are displayed.
     */
    public $showfilterontop = false;

    /**
     *
     * @var bool Scroll to wb_table container on page load.
     */
    public $scrolltocontainer = true;

    /**
     *
     * @var bool Show download button at bottom instead of top (default).
     */
    public $showdownloadbuttonatbottom = false;

    /**
     * Action buttons
     *
     * @var array
     */
    private $actionbuttons = [];

    /**
     * Display Card sort element
     * (Depends on the template, if we want this or not)
     *
     * @var bool
     */
    private $cardsort = false;

    /**
     * Errormessage
     *
     * @var string
     */
    private $errormessage = '';

    /**
     * Number of rows diplayed per page in table.
     *
     * @var bool
     */
    public $showrowcountselect = false;

    /**
     * Display Actionbuttons, Pagination and Rowcount on top of table.
     *
     * @var bool
     */
    public $placebuttonandpageelementsontop = false;

    /**
     * Pagesize
     *
     * @var int
     */
    private $pagesize = 10;

    /**
     * Info about filter applied.
     *
     * @var string
     */
    private $filtercountstring = '';

    /**
     * Searchtext.
     *
     * @var string
     */
    public $searchtext = '';

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
     * Constructor.
     *
     * @param wunderbyte_table $table
     * @param string $encodedtable
     *
     */
    public function __construct(wunderbyte_table $table, string $encodedtable = '') {

        global $SESSION;

        $this->table = [];

        $this->wbtable = $table;

        $this->idstring = $table->idstring;

        $this->uniqueid = $table->uniqueid;

        $this->errormessage = $table->errormessage;

        // Make sure the chosen template is marked as selected.
        if (!empty($this->wbtable->switchtemplates['templates'])) {
            foreach ($this->wbtable->switchtemplates['templates'] as &$t) {
                if (
                    ($t['template'] == get_user_preferences('wbtable_chosen_template_' . $this->wbtable->uniqueid))
                    && ($t['viewparam'] == get_user_preferences(
                        'wbtable_chosen_template_viewparam_' . $this->wbtable->uniqueid
                    ))
                ) {
                    $t['selected'] = true;
                } else {
                    unset($t['selected']);
                }
            }
        }
        $this->switchtemplates = $this->wbtable->switchtemplates ?? null;

        // We don't want the encoded table stable, regardless of previous actions.
        $this->encodedtable = empty($encodedtable) ? $table->return_encoded_table() : $encodedtable;

        $this->baseurl = $table->baseurl->out(false);

        // If we have filtercolumns defined, we add the filter key to the output.
        $this->categories = $this->applyfilterselection($table);
        $this->printoptions = $this->return_dataformat_selector();

        $this->showdownloadbutton = $table->showdownloadbutton;
        $this->showfilterbutton = $table->showfilterbutton;
        $this->applyfilterondownload = $table->applyfilterondownload;
        $this->showreloadbutton = $table->showreloadbutton;
        $this->showaddfilterbutton = $table->showaddfilterbutton;

        if (
            get_config('local_wunderbyte_table', 'allowedittable')
            && has_capability('local/wunderbyte_table:canedittable', $table->context)
        ) {
            $this->edittable = true;
        } else {
            $this->edittable = false;
        }

        $this->showcountlabel = $table->showcountlabel;

        $this->gotopage = $table->gotopage;

        $this->showfilterontop = $table->showfilterontop;

        $this->scrolltocontainer = $table->scrolltocontainer;

        $this->showdownloadbuttonatbottom = $table->showdownloadbuttonatbottom;

        $this->tableheight = $table->tableheight;

        $this->stickyheader = $table->stickyheader;

        $this->cardsort = $table->cardsort;

        self::transform_actionbuttons_array($table->actionbuttons);

        $this->actionbuttons = $table->actionbuttons;

        $this->showrowcountselect = $table->showrowcountselect;

        $this->placebuttonandpageelementsontop = $table->placebuttonandpageelementsontop;

        $this->pagesize = $table->pagesize;

        $this->totalrows = $table->totalrows;

        $this->templatedata = $table->templatedata;

        $this->switchtemplates = $table->switchtemplates;

        [$this->totalrecords, $this->filteredrecords] = $table->return_records_count();

        // If we want to use fulltextsearch, we add the search key to the output.
        if (!empty($table->fulltextsearchcolumns)) {
            $this->search = true;
        }
        $this->searchtext = $table->searchtext;
        // To get the current sortcolum, we need to get the user prefs.
        $prefs = $SESSION->flextable[$table->uniqueid] ?? [];
        $sortcolumns = isset($prefs['sortby']) ? array_slice($prefs['sortby'], 0, 1) : [];

        // Will be null if no sort columns found/defined.
        $this->sort = $this->return_sort_columns($sortcolumns);

        // Now we create the Table with all necessary columns.
        foreach ($table->tableclasses as $key => $value) {
            $this->table[$key] = $value;
        }

        // We need a dedicated rowid. It will work like this: #tableidentifier_rx .
        if ($table->infinitescroll) {
            // The rowid has to be continuous in case of infinitescroll.
            $rcounter = $table->currpage * $table->infinitescroll + 1;
        } else {
            // The rowid has to be restarted in case of pagination.
            $rcounter = 1;
        }

        // Now we see if we have a header class.
        // We have to prepare the row for output.
        foreach ($table->formatedrows as $rowid => $row) {
            $rowarray = [];

            $rowarray['rowid'] = "$table->uniqueid" . "_r$rcounter";
            $rcounter++;

            // The tableheaderclasses need to be available also within the rows.
            foreach ($table->tableclasses as $key => $value) {
                $rowarray[$key] = $value;
            }

            $counter = 0;
            foreach ($row as $key => $value) {
                // We run through all our set subcolumnsidentifiers.

                foreach ($table->subcolumns as $subcolumnskey => $subcolumnsvalue) {
                    if (isset($subcolumnsvalue[$key])) {
                        $subcolumnsvalue[$key]['key'] = $key;
                        $subcolumnsvalue[$key]['value'] = $value;
                        if (!empty($table->headers[$counter])) {
                            $subcolumnsvalue[$key]['localized'] = $table->headers[$counter];
                            $counter++;
                        } else {
                            $subcolumnsvalue[$key]['localized'] = $key;
                        }
                        $rowarray[$subcolumnskey][] = $subcolumnsvalue[$key];
                    }
                }

                // If at this point, we have not dataset id, we need to add it now.

                if (!isset($rowarray['datafields'])) {
                    $rowarray['datafields'] = [];
                }

                $foundid = array_filter($rowarray['datafields'], function ($x) {
                    return $x['key'] === 'id';
                });

                if (empty($foundid)) {
                    $rowarray['datafields'][] = [
                        'key' => 'id',
                        'value' => $rowid,
                    ];
                };
            }

            $this->table['rows'][] = $rowarray;
            // Only if it's not yet set, we set the header.
            if (!isset($this->table['header'])) {
                $this->table['header'] = $rowarray;
            }
        }

        if (!empty($table->headers)) {
            foreach ($table->columns as $column => $key) {
                $localized = $table->headers[$key] ?? $column;
                $item = [
                    'key' => $column,
                    'localized' => $localized,
                ];

                // Whether there should be up down arrows in the header.
                if (
                    in_array($column, $table->sortablecolumns, true)
                    || in_array($column, array_keys($table->sortablecolumns), true)
                ) {
                    $item['sortable'] = true;
                };

                // Make the up down arrow fat/black when it's actually sorted.
                if (in_array($column, array_keys($sortcolumns)) && !empty($this->sort)) {
                    switch ($sortcolumns[$column]) {
                        case (SORT_ASC):
                            $item['sortclass'] = 'asc';
                            $this->sort['sortup'] = true;
                            $this->sort['sortdown'] = false;
                            break;
                        case (SORT_DESC):
                            $item['sortclass'] = 'desc';
                            $this->sort['sortup'] = false;
                            $this->sort['sortdown'] = true;
                            break;
                    }
                };

                $this->table['header']['headers'][] = $item;
            }
        } else { // We also need this in case there are no headers to apply sorting correctly.
            foreach ($table->columns as $column => $key) {
                if (in_array($column, array_keys($sortcolumns)) && !empty($this->sort)) {
                    switch ($sortcolumns[$column]) {
                        case (SORT_ASC):
                            $item['sortclass'] = 'asc';
                            $this->sort['sortup'] = true;
                            $this->sort['sortdown'] = false;
                            break;
                        case (SORT_DESC):
                            $item['sortclass'] = 'desc';
                            $this->sort['sortup'] = false;
                            $this->sort['sortdown'] = true;
                            break;
                    }
                };
            }
        }

        // Create pagination data.
        // We show ellipsis if there are more than the specified number of pages.
        if (!$table->showpagination) {
            $this->pagination['nopages'] = 'nopages';
        } else if ($table->use_pages && $table->infinitescroll == 0) {
            $pages = [];
            $numberofpages = ceil($table->totalrows / $table->pagesize);
            if ($numberofpages < 2) {
                $this->pagination['nopages'] = 'nopages';
                return;
            }
            $pagenumber = 0;
            $currpage = $table->currpage + 1;
            if ($currpage > 4 && $numberofpages > 8) {
                $shownumberofpages = 2;
            } else {
                $shownumberofpages = 3;
            }

            while (++$pagenumber <= $numberofpages) {
                $page = [];
                if ($pagenumber == ($currpage + $shownumberofpages + 1)) {
                    // Check if previous page is not already ellipsis.
                    $lasteleemnt = end($pages);
                    if (!isset($lasteleemnt['ellipsis'])) {
                        $page['ellipsis'] = 'ellipsis';
                        $pages[] = $page;
                    }
                } else if (
                    $pagenumber <= $shownumberofpages
                    || $pagenumber > ($numberofpages - $shownumberofpages)
                    || ($pagenumber > ($currpage - $shownumberofpages)
                        && ($pagenumber < ($currpage + $shownumberofpages))
                    )
                ) {
                    $page['pagenumber'] = $pagenumber;
                    if ($pagenumber === $currpage) {
                        $page['active'] = 'active';
                    }
                    $pages[] = $page;
                } else if ($pagenumber == ($shownumberofpages + 1)) {
                    // Check if previous page is not already ellipsis.
                    $lasteleemnt = end($pages);
                    if (!isset($lasteleemnt['ellipsis'])) {
                        $page['ellipsis'] = 'ellipsis';
                        $pages[] = $page;
                    }
                }
            }

            // If currentpage is the last one, next is disabled.
            if ($currpage == $numberofpages) {
                $this->pagination['disablenext'] = 'disabled';
            } else {
                $this->pagination['nextpage'] = $currpage + 1;
            }
            // If currentpage is the first one previous is disabled.
            if ($currpage == 1) {
                $this->pagination['disableprevious'] = 'disabled';
            } else {
                $this->pagination['previouspage'] = $currpage - 1;
            }
            $this->pagination['pages'] = $pages;
            $totalpagesarray = range(1, $numberofpages);
            $totalpagesarray = array_map(function ($pagenum) use ($currpage) {
                return [
                    'value' => $pagenum,
                    'selected' => ($pagenum == $currpage),
                ];
            }, $totalpagesarray);
            $this->pagination['totalpages'] = $totalpagesarray;
            $this->pagination['currentpage'] = $currpage;
        } else if ($table->infinitescroll > 0) {
            $this->pagination['nopages'] = 'nopages';
            $this->pagination['infinitescroll'] = true;
        } else {
            $this->pagination['nopages'] = 'nopages';
        }
    }

    /**
     * Returns dataformat selector.
     *
     * @return array
     *
     */
    private function return_dataformat_selector() {
        $formats = core_plugin_manager::instance()->get_plugins_of_type('dataformat');
        $printoptions = [];
        foreach ($formats as $format) {
            if ($format->is_enabled()) {
                $printoptions[] = [
                    'value' => $format->name,
                    'label' => get_string('dataformat', $format->component),
                ];
            }
        }
        return $printoptions;
    }
    /**
     * Function for select to choose number of rows displayed in table.
     *
     * @return array
     */
    private function showcountselect() {

        if (!$this->showrowcountselect) {
            return [];
        }
        $options = [];
        $counter = 0;
        while ($counter <= 19) {
            if ($counter == 0) {
                $pagenumber = 5;
            } else if ($counter < 11) {
                $pagenumber = $counter * 10;
            } else {
                $pagenumber = ($counter - 9) * 100;
            }

            array_push($options, [
                'label' => get_string('pagelabel', 'local_wunderbyte_table', $pagenumber),
                'value' => $pagenumber,
                'selected' => $pagenumber == $this->pagesize ? 'selected' : '',
            ]);
            $counter++;
        }

        return ['options' => $options];
    }

    /**
     * Returns prepared data
     *
     * @param renderer_base $output
     * @return array
     */
    public function export_for_template(renderer_base $output) {
        return $this->return_as_list();
    }

    /**
     * Prepare data and return it as a list.
     *
     * @return array
     *
     */
    public function return_as_list() {
        global $CFG;

        $data = [
            'idstring' => $this->idstring,
            'uniqueid' => $this->uniqueid,
            'encodedtable' => $this->encodedtable,
            'baseurl' => $this->baseurl,
            'table' => $this->table,
            'totalrecords' => $this->totalrecords,
            'norecords' => $this->totalrecords == 0 ? true : false,
            'filteredrecords' => $this->filteredrecords,
            'countlabelstring' => get_string(
                'countlabel',
                'local_wunderbyte_table',
                (object)[
                    'totalrecords' => $this->totalrecords,
                    'filteredrecords' => $this->totalrows,
                ]
            ),
            'filtercount' => $this->filtercountstring,
            'searchtext' => $this->searchtext,
            'searchtextapplied' => $this->search,
            'pages' => $this->pagination['pages'] ?? null,
            'totalpages' => $this->pagination['totalpages'] ?? null,
            'disableprevious' => $this->pagination['disableprevious'] ?? null,
            'disablenext' => $this->pagination['disablenext'] ?? null,
            'previouspage' => $this->pagination['previouspage'] ?? null,
            'nextpage' => $this->pagination['nextpage'] ?? null,
            'nopages' => ($this->pagination['nopages'] ?? '') === 'nopages' ? true : false,
            'infinitescroll' => $this->pagination['infinitescroll'] ?? null,
            'sesskey' => sesskey(),
            'filter' => $this->categories ?? null,
            'errormessage' => !empty($this->errormessage) ? $this->errormessage : false,
            'showrowcountselect' => $this->showcountselect(),
            'showfilterontop' => $this->showfilterontop,
            'scrolltocontainer' => $this->scrolltocontainer,
            'showdownloadbuttonatbottom' => $this->showdownloadbuttonatbottom,
            'displayelementsontop' => $this->placebuttonandpageelementsontop ?? null,
            'showspinner' => true,
            'switchtemplates' => $this->switchtemplates ?? null,
        ];

        // Only if we want to show the searchfield, we actually add the key.
        if ($this->search) {
            $data['search'] = true;
            if ($CFG->version >= 2023042400) {
                // Moodle 4.2 uses Fontawesome 6.
                $data['searchiconclasses'] = 'fa-solid fa-magnifying-glass wunderbyteTableSearchIcon';
            } else {
                // For older versions, use Fontawesome 4.
                $data['searchiconclasses'] = 'fa fa-search fa-xl mt-2';
            }
        }

        // We need to to the same for switchtemplates (template switcher).
        if (!empty($this->switchtemplates)) {
            if ($CFG->version >= 2023042400) {
                // Moodle 4.2 uses Fontawesome 6.
                $data['switchtemplatesiconclasses'] = 'fa-solid fa-table-list wunderbyteTableSwitchTemplatesIcon';
            } else {
                // For older versions, use Fontawesome 4.
                $data['switchtemplatesiconclasses'] = 'fa fa-th-list fa-xl mt-2';
            }
        }

        // Only if we want to show the sortelements, we actually add the key.
        if (!empty($this->sort)) {
            if (!$this->cardsort) {
                $data['sort'] = $this->sort;
            } else {
                $data['cardsort'] = $this->sort;
            }
        }

        // Only if we want to show the searchfield, we actually add the key.
        if ($this->showreloadbutton) {
            $data['reload'] = true;
        }

        // Only if we want to show the searchfield, we actually add the key.
        if ($this->edittable) {
            $data['edit'] = true;
        }

        if ($this->showcountlabel) {
            $data['countlabel'] = true;
        }

        // Check if pagination and go to page are both enabled are there are more than one page.
        if (!$data['nopages'] && $this->gotopage && count($this->pagination['totalpages']) >= 2) {
            $data['gotopage'] = true;
        }

        if (!empty($this->stickyheader)) {
            $data['stickyheader'] = $this->stickyheader;
        }

        if (!empty($this->tableheight)) {
            $data['tableheight'] = $this->tableheight;
        }

        // Only if we want to show the print elements, we actually add the key.
        if ($this->showdownloadbutton) {
            $data['print'] = true;
            $data['printoptions'] = $this->printoptions;
            if (!empty($this->applyfilterondownload)) {
                $data['applyfilterondownload'] = "1";
            }
        }

        if ($this->showaddfilterbutton) {
            $data['showaddfilterbutton'] = true;
        }

        if (!empty($this->categories)) {
            // If there there is a filterobject, we check if on load filters should be hidden or displayed (default).
            if ($this->categories['filterinactive'] == true) {
                $data['showcomponentstoggle'] = false;
                $data['showfilterbutton'] = $this->showfilterbutton;
                $data['filterdeactivated'] = true;
            } else {
                $data['showcomponentstoggle'] = true;
                $data['showfilterbutton'] = $this->showfilterbutton;
            }
        }

        if (!empty($this->actionbuttons)) {
            $data['showactionbuttons'] = $this->actionbuttons;
        }

        if (class_exists('local_shopping_cart\shopping_cart')) {
            $data['shoppingcartisavailable'] = true;
        }

        // We need a param to check in the css if the version is minimum 4.2.
        if ($CFG->version >= 2023042400) {
            $data['moodleversionminfourtwo'] = 'moodleversionminfourtwo';
        }

        // Make sure switchtemplates array is not passed, if it's empty.
        if (empty($this->switchtemplates)) {
            $data['switchtemplates'] = null;
        }

        $this->apply_template_data($data);

        return $data;
    }

    /**
     * Store the actionbuttons in the right form for output.
     *
     * @param array $actionbuttons
     * @return void
     */
    public static function transform_actionbuttons_array(array &$actionbuttons) {
        $actionbuttonsarray = [];
        foreach ($actionbuttons as $actionbutton) {
            $datas = $actionbutton['data'];
            $newdatas = [];
            foreach ($datas as $key => $value) {
                $newdatas[] = [
                    'key' => $key,
                    'value' => $value,
                ];
            }
            $actionbutton['data'] = $newdatas;
            $actionbutton['id'] = $actionbutton['id'] ?? 0;
            $actionbuttonsarray[] = $actionbutton;
        }
        $actionbuttons = $actionbuttonsarray;
    }

    /**
     * Return the array for rendering the mustache template.
     * @param array $sortcolumns
     *
     * @return ?array
     */
    public function return_sort_columns(array $sortcolumns) {

        global $SESSION;

        if (empty($this->wbtable->sortablecolumns)) {
            return null;
        }

        $isassociative = array_keys($this->wbtable->sortablecolumns) !== range(0, count($this->wbtable->sortablecolumns) - 1);

        $sortarray['options'] = [];
        foreach ($this->wbtable->sortablecolumns as $key => $value) {
            // If we have an assoziative array, we have localized values.
            // Else, we need to use the same value twice.
            if (!$isassociative) {
                $key = $value;
            }

            $item['sortid'] = $key;
            $item['key'] = $value;
            $item['selected'] = '';

            if (in_array($key, array_keys($sortcolumns))) {
                $item['selected'] = 'selected';
            }

            $sortarray['options'][] = $item;
        }
        if (
            $this->wbtable->return_current_sortorder() == SORT_ASC ||
            empty($this->wbtable->return_current_sortorder())
        ) {
            // Sort up is the default.
            $sortarray['sortup'] = true;
            $sortarray['sortdown'] = null; // For mustache, we neeed null, not false.
        } else {
            $sortarray['sortup'] = null; // For mustache, we neeed null, not false.
            $sortarray['sortdown'] = true;
        }

        return $sortarray;
    }

    /**
     * Make actual filter params checked in table filter display.
     *
     * @param wunderbyte_table $table
     * @return object|null
     */
    private function applyfilterselection(wunderbyte_table $table) {

        // To be compatible with php 8.1.
        if (empty($table->filterjson)) {
            return null;
        }

        $filtercountarray = [];

        $categories = json_decode($table->filterjson, true);

        if (!isset($categories['categories'])) {
            return null;
        }

        $tableobject = $categories['categories'];

        $now = usertime(time());

        // Check if the value for date and time picker is defined as "now". If so apply current date and time from user.
        foreach ($tableobject as $tokey => $column) {
            if (isset($column['datepicker'])) {
                foreach ($column['datepicker']['datepickers'] as $vkey => $value) {
                    if (isset($value['timestamp'])) {
                        if ((is_string($value['timestamp']) && !is_numeric($value['timestamp']))) {
                            $time = strtotime($value['timestamp']);
                        } else {
                            $time = (int)$value['timestamp'];
                        }
                        $tableobject[$tokey]['datepicker']['datepickers'][$vkey]['datereadable'] = date('Y-m-d', $time);
                        $tableobject[$tokey]['datepicker']['datepickers'][$vkey]['timereadable'] = date('h:i', $time);
                    }
                    if (isset($value['starttimestamp'])) {
                        if (is_string($value['starttimestamp']) && !is_numeric($value['starttimestamp'])) {
                            $time = strtotime($value['starttimestamp']);
                        } else {
                            $time = (int)$value['starttimestamp'];
                        }
                        $tableobject[$tokey]['datepicker']['datepickers'][$vkey]['startdatereadable'] = date('Y-m-d', $time);
                        $tableobject[$tokey]['datepicker']['datepickers'][$vkey]['starttimereadable'] = date('h:i', $time);
                    }
                    if (isset($value['endtimestamp'])) {
                        if (is_string($value['endtimestamp']) && !is_numeric($value['endtimestamp'])) {
                            $time = strtotime($value['endtimestamp']);
                        } else {
                            $time = (int)$value['endtimestamp'];
                        }
                        $tableobject[$tokey]['datepicker']['datepickers'][$vkey]['enddatereadable'] = date('Y-m-d', $time);
                        $tableobject[$tokey]['datepicker']['datepickers'][$vkey]['endtimereadable'] = date('h:i', $time);
                    }
                }
            }
        }

        // Only if we have filterobjects defined, we try to apply them.
        $filterparam = optional_param('wbtfilter', "", PARAM_RAW);
        if ($filterparam) {
            $filterarray = (array)json_decode($filterparam);
            // For all the potential filtercolumns...
            foreach ($tableobject as $tokey => $potentialfiltercolumn) {
                $tempfiltercolumn = $potentialfiltercolumn['columnname'];

                if (isset($filterarray[$tempfiltercolumn])) {
                    // We create an array to fetch human readable data.
                    $filtercounts = count((array)$filterarray[$tempfiltercolumn]);
                    // This is the localized string used as a key... not sure if thats intended.
                    $filtercountarray[$potentialfiltercolumn['name']] = $filtercounts;
                    $tableobject[$tokey]['filtercounter']
                        = $filtercounts > 0 ? $filtercounts : false;

                    $filterclass = $potentialfiltercolumn['wbfilterclass'];
                    $filter = new $filterclass($tempfiltercolumn, $potentialfiltercolumn['name']);
                    $filter::prepare_filter_for_rendering($tableobject, $filterarray, $tokey);
                    // phpcs:ignore moodle.Commenting.TodoComment.MissingInfoInline
                    // TODO: Migrate these functions to the concerned filter classes and get rid of the following condition.
                    if (strpos($potentialfiltercolumn['wbfilterclass'], 'intrange') !== false) {
                        continue;
                    }

                    foreach ($filterarray[$tempfiltercolumn] as $sfkey => $filter) {
                        $counter = 1;
                        // Apply filter for date and time value.
                        if (is_object($filter)) {
                            foreach ($filter as $filterdata) {
                                $unixcode = $filterdata;
                                $date = date('Y-m-d', $unixcode);
                                $time = date('H:i', $unixcode);
                                // We check which filter of the column is checked and apply the values.
                                // Todo: Handle cases where we have a start- & enddate.
                                foreach ($tableobject[$tokey]['datepicker']['datepickers'] as $dkey => $dvalues) {
                                    if ($dvalues['label'] == $sfkey) {
                                        $tableobject[$tokey]['datepicker']['datepickers'][$dkey]['datereadable'] = $date;
                                        $tableobject[$tokey]['datepicker']['datepickers'][$dkey]['timereadable'] = $time;
                                        $tableobject[$tokey]['datepicker']['datepickers'][$dkey]['checked'] = 'checked';
                                        if ($counter == 1) {
                                            $tableobject[$tokey]['datepicker']['datepickers'][$dkey]['startdatereadable'] = $date;
                                            $tableobject[$tokey]['datepicker']['datepickers'][$dkey]['starttimereadable'] = $time;
                                        } else if ($counter == 2) {
                                            $tableobject[$tokey]['datepicker']['datepickers'][$dkey]['enddatereadable'] = $date;
                                            $tableobject[$tokey]['datepicker']['datepickers'][$dkey]['endtimereadable'] = $time;
                                        }
                                        $counter++;
                                        continue;
                                    }
                                }
                            }

                            // If a filter is selected, filter buttons will be expanded. Right checkbox will be checked.
                            $tableobject[$tokey]['show'] = 'show';
                            $tableobject[$tokey]['collapsed'] = '';
                            $tableobject[$tokey]['expanded'] = 'true';
                            continue;
                            // Then we check for the next filterparam.
                        } else if (isset($potentialfiltercolumn["hierarchy"])) {
                            foreach ($potentialfiltercolumn["hierarchy"] as $hikey => $hivalue) {
                                foreach ($hivalue['values'] as $vkey => $value) {
                                    if (
                                        $value['key'] == $filter
                                        || $value['value'] == $filter
                                    ) {
                                        // If we find the filter, we add the checked value...
                                        // ...and key to the initial tableobject array at the right place.
                                        $tableobject[$tokey]['hierarchy'][$hikey]['values'][$vkey]['checked'] = 'checked';
                                        // Expand the filter area.
                                        $tableobject[$tokey]['hierarchy'][$hikey]['show'] = 'show';
                                        $tableobject[$tokey]['hierarchy'][$hikey]['collapsed'] = '';
                                        $tableobject[$tokey]['hierarchy'][$hikey]['expanded'] = 'true';

                                        continue;
                                        // Then we check for the next filterparam.
                                    }
                                }
                            }
                        } else {
                            // So we can now check all the entries in the filterobject...
                            // ...to see if we find the concrete filter at the right place (values) in the tableobject.
                            foreach ($potentialfiltercolumn['default']['values'] as $vkey => $value) {
                                if (
                                    $value['key'] == $filter
                                    || $value['value'] == $filter
                                ) {
                                    // If we find the filter, we add the checked value...
                                    // ...and key to the initial tableobject array at the right place.
                                    $tableobject[$tokey]['default']['values'][$vkey]['checked'] = 'checked';
                                    // Expand the filter area.
                                    $tableobject[$tokey]['show'] = 'show';
                                    $tableobject[$tokey]['collapsed'] = '';
                                    $tableobject[$tokey]['expanded'] = 'true';

                                    continue;
                                    // Then we check for the next filterparam.
                                }
                            }
                        }
                    }
                }
            }
        }
        $this->add_readable_info_about_filter_and_search($filtercountarray, $table);
        $categories['categories'] = $tableobject;
        return $categories;
    }

    /**
     * Create string with human readable informations and add it to $this->filtercountstring.
     *
     * @param mixed $filtercountarray
     * @param mixed $table
     *
     * @return void
     *
     */
    private function add_readable_info_about_filter_and_search($filtercountarray, $table) {
        // We collect human readable informations about applied filters.
        $filtercolumns = implode(', ', array_keys($filtercountarray));
        $filtersum = array_sum($filtercountarray);
        $string = "";
        if ($filtersum > 0) {
            $string .= " | " .
                get_string(
                    'filtercountmessage',
                    'local_wunderbyte_table',
                    (object)[
                        'filtercolumns' => $filtercolumns,
                        'filtersum' => $filtersum,
                    ]
                );
        }
        if (!empty($table->searchtext)) {
            if (!empty($string)) {
                $string .= " & ";
            } else {
                $string .= " | ";
            }
            $string .= get_string('searchcountmessage', 'local_wunderbyte_table', $table->searchtext);
        }
        $this->filtercountstring = $string;
        if (!empty($string)) {
            $this->filtercountstring .= " | ";
        }
    }

    /**
     * Reduce filter columns.
     *
     * @param array $onlyfilterforcolumns
     *
     * @return void
     *
     */
    public function filter_filter($onlyfilterforcolumns = []) {
        if (!empty($onlyfilterforcolumns)) {
            $lefthierarchy = [];
            $righthierarchy = [];
            foreach ($this->categories['categories'] as $key => $value) {
                if (
                    $value["columnname"] !== 'id'
                    && !in_array($value["columnname"], $onlyfilterforcolumns)
                ) {
                    unset($this->categories['categories'][$key]);
                }
            }
            $this->categories['categories'] = array_values($this->categories['categories']);
            foreach ($this->categories['categories'] as $catkey => $category) {
                $left = true;
                foreach ($category['hierarchy'] as $value) {
                    if ($left) {
                        $lefthierarchy[] = $value;
                    } else {
                        $righthierarchy[] = $value;
                    }
                    $left = !$left;
                }
                $this->categories['categories'][$catkey]['lefthierarchy'] = array_values($lefthierarchy);
                $this->categories['categories'][$catkey]['righthierarchy'] = array_values($righthierarchy);
            }
        }
    }

    /**
     * Applies the template data.
     * @param array $data template data
     */
    public function apply_template_data(array &$data) {
        foreach ($this->templatedata as $key => $value) {
            $data[$key] = $value;
        }
    }
}
