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
 * Plugin event observers are registered here.
 *
 * @package local_wunderbyte_table
 * @copyright 2023 Wunderbyte Gmbh <info@wunderbyte.at>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_wunderbyte_table\output;

use plugin_renderer_base;
use templatable;

/**
 * Renderer class.
 * @package local_wunderbyte_table
 */
class renderer extends plugin_renderer_base {

    /**
     * Render a wunderbyte_table.
     * @param templatable $viewtable
     * @return string|bool
     */
    public function render_lazytable(templatable $viewtable) {
        $data = $viewtable->export_for_template($this);
        return $this->render_from_template('local_wunderbyte_table/table_lazy', $data);
    }

    /**
     * Function to render the cards table
     * @param mixed $data
     * @param string $templatename
     * @return string
     */
    public function render_table($data, string $templatename) {
        $o = '';
        $data = $data->export_for_template($this);
        $o .= $this->render_from_template($templatename, $data);
        return $o;
    }
}
