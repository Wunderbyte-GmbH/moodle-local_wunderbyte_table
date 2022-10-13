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
 * Contains class mod_questionnaire\output\indexpage
 *
 * @package    local_wunderbyte_table
 * @copyright  2020 Wunderbyte Gmbh <info@wunderbyte.at>
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
     * Options data format
     *
     * @var array
     */
    private $printoptions = [];

    /**
     * Constructor.
     * @param wunderbyte_table $table
     */
    public function __construct(wunderbyte_table $table) {

        $this->table = [];

        $this->idstring = $table->idstring;

        $this->encodedtable = $table->return_encoded_table();

        $this->baseurl = $table->baseurl->out(false);

        // If we have filtercolumns defined, we add the filter key to the output.
        $this->categories = json_decode($table->filterjson, true);

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

        // Now we see if we have a header class.

        // We have to prepare the row for output.
        foreach ($table->formatedrows as $row) {
            $rowarray = [];

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
                $this->table['header']['headers'][] = [
                    'key' => $column,
                    'localized' => $localized
                ];
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

        $this->printoptions = $this->return_dataformat_selector();

        $this->showdownloadbutton = $table->showdownloadbutton;

        $this->showreloadbutton = $table->showreloadbutton;

        $this->showcountlabel = $table->showcountlabel;
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
     * Prepare data for use in a template
     *
     * @param renderer_base $output
     * @return array
     */
    public function export_for_template(renderer_base $output) {
        $data = [
            'idstring' => $this->idstring,
            'encodedtable' => $this->encodedtable,
            'baseurl' => $this->baseurl,
            'table' => $this->table,
            'totalrecords' => $this->totalrecords,
            'filteredrecords' => $this->filteredrecords,
            'pages' => $this->pagination['pages'] ?? null,
            'disableprevious' => $this->pagination['disableprevious'] ?? null,
            'disablenext' => $this->pagination['disablenext'] ?? null,
            'previouspage' => $this->pagination['previouspage'] ?? null,
            'nextpage' => $this->pagination['nextpage'] ?? null,
            'nopages' => $this->pagination['nopages'] ?? null,
            'infinitescroll' => $this->pagination['infinitescroll'] ?? null,
            'sesskey' => sesskey(),
            'filter' => $this->categories ?? null,
        ];

        // Only if we want to show the searchfield, we actually add the key.
        if ($this->search) {
            $data['search'] = true;
        }

        // Only if we want to show the searchfield, we actually add the key.
        if ($this->sort) {
            $data['sort'] = $this->sort;
        }

        // Only if we want to show the searchfield, we actually add the key.
        if ($this->showreloadbutton) {
            $data['reload'] = true;
        }

        if ($this->showcountlabel) {
            $data['countlabel'] = true;
        }

        // Only if we want to show the print elements, we actually add the key.
        if ($this->showdownloadbutton) {
            $data['print'] = true;
            $data['printoptions'] = $this->printoptions;
        }

        return $data;
    }
}
