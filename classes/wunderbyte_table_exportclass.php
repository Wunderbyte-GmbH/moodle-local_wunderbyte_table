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
 * The wunderbyte_table_exportclass is an extension of the tablelib table_dataformat_export_format.
 *
 * @package local_wunderbyte_table
 * @copyright 2021 onwards Wunderbyte Gmbh <info@wunderbyte.at>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_wunderbyte_table;

use phpDocumentor\Reflection\Types\Boolean;
use table_dataformat_export_format;

defined('MOODLE_INTERNAL') || die();

class wunderbyte_table_exportclass extends table_dataformat_export_format {


/**
     * This function is not part of the public api.
     */
    function finish_html() {
        global $OUTPUT, $PAGE;

        if (!$this->started_output) {
            //no data has been added to the table.
            $this->print_nothing_to_display();

        } else {
            // Print empty rows to fill the table to the current pagesize.
            // This is done so the header aria-controls attributes do not point to
            // non existant elements.
            $emptyrow = array_fill(0, count($this->columns), '');
            while ($this->currentrow < $this->pagesize) {
                $this->print_row($emptyrow, 'emptyrow');
            }

            echo html_writer::end_tag('tbody');
            echo html_writer::end_tag('table');
            echo html_writer::end_tag('div');
            $this->wrap_html_finish();

            // Paging bar
            if(in_array(TABLE_P_BOTTOM, $this->showdownloadbuttonsat)) {
                echo $this->download_buttons();
            }

            if($this->use_pages) {
                $pagingbar = new paging_bar($this->totalrows, $this->currpage, $this->pagesize, $this->baseurl);
                $pagingbar->pagevar = $this->request[TABLE_VAR_PAGE];
                echo $OUTPUT->render($pagingbar);
            }

            // Render the dynamic table footer.
            echo $this->get_dynamic_table_html_end();
        }
    }

    /**
     * Supports html.
     *
     * @return Boolean
     */
    public function supports_html() {
        return _parent::supports_html();
    }

}
