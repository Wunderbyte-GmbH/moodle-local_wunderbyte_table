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
 * The checkout completed event.
 *
 * @package    local_wunderbyte_table
 * @copyright  2023 Wunderbyte GmbH <info@wunderbyte.at>
 * @author     Georg Maißer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_wunderbyte_table\event;
use local_wunderbyte_table\event\wbtable_event_base;

/**
 * An action button in a wunderbyte table was pressed.
 *
 * @package    local_wunderbyte_table
 * @copyright  2023 Wunderbyte GmbH <info@wunderbyte.at>
 * @author     Georg Maißer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class action_executed extends wbtable_event_base {

    /**
     * Init method.
     */
    protected function init() {
        $this->data['crud'] = 'r';
        $this->data['edulevel'] = self::LEVEL_OTHER;
    }

    /**
     * Returns localized general event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('action_executed', 'local_wunderbyte_table');
    }

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        $otherdata = $this->get_other_data();
        $params = (object)[
            'tablename' => $otherdata->tablename ?? '',
            'methodname' => $otherdata->methodname ?? '',
        ];
        return get_string('action_executed_desc', 'local_wunderbyte_table', $params);
    }
}
