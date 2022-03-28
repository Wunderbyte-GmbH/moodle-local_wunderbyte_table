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

use renderable;
use renderer_base;
use templatable;

defined('MOODLE_INTERNAL') || die();

/**
 * viewtable class to display view.php
 * @package local_wunderbyte_table
 *
 */
class table implements renderable, templatable {

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
     * Constructor.
     * @param [type] $table
     */
    public function __construct($table) {

        $this->table = [];

        foreach ($table->tableclasses as $key => $value) {
            $this->table[$key] = $value;
        }

        // We have to prepare the row for output.
        foreach ($table->formatedrows as $row) {
            $rowarray = [];
            foreach ($row as $key => $value) {
                // We run through all our set subcolumnsidentifiers.
                foreach ($table->subcolumns as $subcolumnskey => $subcolumnsvalue) {

                    if (isset($subcolumnsvalue[$key])) {
                        $subcolumnsvalue[$key]['key'] = $key;
                        $subcolumnsvalue[$key]['value'] = $value;
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

        // Create pagination data.
        // We show ellipsis if there are more than the specified number of pages.
        if ($table->use_pages) {
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

        } else {
            $this->pagination['nopages'] = 'nopages';
        }

    }

    /**
     * Prepare data for use in a template
     *
     * @param renderer_base $output
     * @return array
     */
    public function export_for_template(renderer_base $output) {
        $data = [
            'table' => $this->table,
            'pages' => $this->pagination['pages'] ?? null,
            'disableprevious' => $this->pagination['disableprevious'] ?? null,
            'disablenext' => $this->pagination['disablenext'] ?? null,
            'previouspage' => $this->pagination['previouspage'] ?? null,
            'nextpage' => $this->pagination['nextpage'] ?? null,
            'nopages' => $this->pagination['nopages'] ?? null
        ];

        return $data;
    }
}
