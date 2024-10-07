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
 * The attempt_completed event.
 *
 * @package local_wunderbyte_table
 * @copyright 2024 Wunderbyte GmbH
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_wunderbyte_table\event;

use stdClass;

/**
 * Extends the event base with utility methods.
 *
 * @copyright 2024 Wunderbyte GmbH
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class wbtable_event_base extends \core\event\base {
    /**
     * Returns the 'other' data as object
     * @return ?stdClass
     */
    protected function get_other_data(): ?stdClass {
        if (!$this->data['other']) {
            return null;
        }
        if (is_array($this->data['other'])) {
            return (object) $this->data['other'];
        }
        if (is_string($this->data['other'])) {
            return json_decode($this->data['other']);
        }
        return $this->data['other'];
    }

    /**
     * Get url.
     *
     * @return ?stdClass
     */
    public function get_url() {
        return null;
    }
}
