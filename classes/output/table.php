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
     * Pagination is the array used for output.
     *
     * @var array
     */
    private $pagination = [];

    /**
     * Categories are used for filter
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
     * Button to print table.
     *
     * @var bool
     */
    private $showdownloadbutton = true;

    /**
     * Countlabel.
     *
     * @var bool
     */
    private $showcountlabel = true;

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
     * Options data format
     *
     * @var array
     */
    private $printoptions = [];

    /**
     * Action buttons
     *
     * @var array
     */
    private $actionbuttons = [];

    /**
     * Display Card sort element
     * (Depends on the template, if we want this or not)
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
     * @var boolean
     */
    public $showrowcountselect = false;

    /**
     * Pagesize
     *
     * @var int
     */
    private $pagesize = 10;


    /**
     * Constructor.
     * @param wunderbyte_table $table
     */
    public function __construct(wunderbyte_table $table, string $encodedtable = '') {

        $this->table = [];

        $this->idstring = $table->idstring;

        $this->uniqueid = $table->uniqueid;

        $this->errormessage = $table->errormessage;

        // We don't want the encoded table stable, regardless of previous actions.
        $this->encodedtable = empty($encodedtable) ? $table->return_encoded_table() : $encodedtable;

        $this->baseurl = $table->baseurl->out(false);

        // If we have filtercolumns defined, we add the filter key to the output.
        $this->categories = json_decode($table->filterjson, true);

        $this->printoptions = $this->return_dataformat_selector();

        $this->showdownloadbutton = $table->showdownloadbutton;

        $this->showreloadbutton = $table->showreloadbutton;

        $this->showcountlabel = $table->showcountlabel;

        $this->tableheight = $table->tableheight;

        $this->stickyheader = $table->stickyheader;

        $this->cardsort = $table->cardsort;

        self::transform_actionbuttons_array($table->actionbuttons);

        $this->actionbuttons = $table->actionbuttons;

        $this->showrowcountselect = $table->showrowcountselect;

        $this->pagesize = $table->pagesize;

        list($this->totalrecords, $this->filteredrecords) = $table->return_records_count();

        // If we want to use fulltextsearch, we add the search key to the output.
        if (!empty($table->fulltextsearchcolumns)) {
            $this->search = true;
        }

        $this->sort = $table->return_sort_columns();

        // Now we create the Table with all necessary columns.
        foreach ($table->tableclasses as $key => $value) {
            $this->table[$key] = $value;
        }

        // We need a dedicated rowid. It will work like this:
        // #tableidentifier_rx
        $rcounter = 1;

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

                $foundid = array_filter($rowarray['datafields'], function($x) {
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


                if (in_array($column, $table->sortablecolumns, true)
                    || in_array($column, array_keys($table->sortablecolumns), true)) {

                    $item['sortable'] = true;
                };

                if($column == $table->sort_default_column){
                    switch ($table->sort_default_order) {
                        case (3):
                            $item['sortclass'] = 'asc';
                        case (4):
                            $item['sortclass'] = 'desc';
                    }

                };

                $this->table['header']['headers'][] = $item;
            }

        }

        // Create pagination data.
        // We show ellipsis if there are more than the specified number of pages.
        if ($table->use_pages && $table->infinitescroll == 0) {
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
                } else if ($pagenumber <= $shownumberofpages
                    || $pagenumber > ($numberofpages - $shownumberofpages)
                    || ($pagenumber > ($currpage - $shownumberofpages)
                        && ($pagenumber < ($currpage + $shownumberofpages))
                    )) {
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

        } else if ($table->infinitescroll > 0) {
            $this->pagination['nopages'] = 'nopages';
            $this->pagination['infinitescroll'] = true;
        } else {
            $this->pagination['nopages'] = 'nopages';
        }
    }


    private function return_dataformat_selector() {
        $formats = core_plugin_manager::instance()->get_plugins_of_type('dataformat');
        $printoptions = array();
        foreach ($formats as $format) {
            if ($format->is_enabled()) {
                $printoptions[] = array(
                    'value' => $format->name,
                    'label' => get_string('dataformat', $format->component),
                );
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

        if(!$this->showrowcountselect) {
            return [];
        }

        $options = [];
        $counter = 1;
        while ($counter <= 19) {
            if ($counter < 11) {
                $pagenumber = $counter * 10;
            }
            else {
                $pagenumber = ($counter -9) * 100;
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
     * Prepare data for use in a template
     *
     * @param renderer_base $output
     * @return array
     */
    public function export_for_template(renderer_base $output) {
        $data = [
            'idstring' => $this->idstring,
            'uniqueid' => $this->uniqueid,
            'encodedtable' => $this->encodedtable,
            'baseurl' => $this->baseurl,
            'table' => $this->table,
            'totalrecords' => $this->totalrecords,
            'filteredrecords' => $this->filteredrecords,
            'countlabelstring' => get_string('countlabel',
                'local_wunderbyte_table',
                (object)[
                    'totalrecords' => $this->totalrecords,
                    'filteredrecords' => $this->filteredrecords,
                ]),
            'pages' => $this->pagination['pages'] ?? null,
            'disableprevious' => $this->pagination['disableprevious'] ?? null,
            'disablenext' => $this->pagination['disablenext'] ?? null,
            'previouspage' => $this->pagination['previouspage'] ?? null,
            'nextpage' => $this->pagination['nextpage'] ?? null,
            'nopages' => $this->pagination['nopages'] ?? null,
            'infinitescroll' => $this->pagination['infinitescroll'] ?? null,
            'sesskey' => sesskey(),
            'filter' => $this->categories ?? null,
            'errormessage' => !empty($this->errormessage) ? $this->errormessage : false,
            'showrowcountselect' => $this->showcountselect(),
            ];

        // Only if we want to show the searchfield, we actually add the key.
        if ($this->search) {
            $data['search'] = true;
        }

        // Only if we want to show the sortelements, we actually add the key.
        if ($this->sort) {
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

        if ($this->showcountlabel) {
            $data['countlabel'] = true;
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
        }

        if ($this->categories) {
            $data['showcomponentstoggle'] = true;
        }

        if (!empty($this->actionbuttons)) {
            $data['showactionbuttons'] = $this->actionbuttons;
        }

        if (class_exists('local_shopping_cart\shopping_cart')) {
            $data['shoppingcartisavailable'] = true;
        }

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
}
