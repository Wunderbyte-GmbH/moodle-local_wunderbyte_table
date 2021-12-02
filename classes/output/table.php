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


    private $table = [];
    /**
     * Constructor.
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
    }

    /**
     * Prepare data for use in a template
     *
     * @param renderer_base $output
     * @return array
     */
    public function export_for_template(renderer_base $output) {
        $data = [
            'table' => $this->table
        ];

        return $data;
    }
}
