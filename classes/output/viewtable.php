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
 * viewpage class to display view.php
 * @package local_wunderbyte_table
 *
 */
class viewpage implements renderable, templatable {

    /**
     * An idstring for the table & spinner.
     *
     * @var string
     */
    public $idstring;

    /**
     * The encoded settings for the sql table.
     *
     * @var string
     */
    public $encodedtable;

    /**
     * Constructor.
     * @param array $data
     */
    public function __construct(string $idstring, string $encodedtable) {
        $this->idstring = $idstring;
        $this->encodedtable = $encodedtable;
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
            'encodedtable' => $this->encodedtable
        ];

        return $data;
    }
}
