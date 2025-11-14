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

namespace local_wunderbyte_table\local\helper;

/**
 * actforuser class.
 *
 * @package    local_wunderbyte_table
 * @copyright  2024 Wunderbyte GmbH <info@wunderbyte.at>
 * @author     Mahdi Poustini
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class actforuser {
    /**
     * Returns the value of 'urlparamforuserid' if it is set.
     *
     * The 'urlparamforuserid' argument can be used to specify which optional parameter
     * in the URL provides the user ID. This prevents relying on a fixed parameter name
     * for the property that holds the user ID.
     *
     * Example usage:
     *  [allbookingoptions urlparamforuserid=userid] → expects a URL containing a query parameter like: ?userid=123456
     *  [allbookingoptions urlparamforuserid=foruserid] → expects a URL containing a query parameter like: ?foruserid=123456
     *  [allbookingoptions urlparamforuserid=id] → expects a URL containing a query parameter like: ?id=123456
     *
     * @param array $args Arguments passed to the shortcode.
     * @return string The name of the URL parameter for the user ID.
     */
    public static function get_urlparamforuserid(array $args): string {
        if (isset($args['urlparamforuserid']) && is_string($args['urlparamforuserid'])) {
            return $args['urlparamforuserid'];
        }
        return '';
    }
}
