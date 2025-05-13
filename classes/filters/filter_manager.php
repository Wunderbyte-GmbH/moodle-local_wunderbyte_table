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
 * The Wunderbyte table class is an extension of the tablelib table_sql class.
 *
 * @package local_wunderbyte_table
 * @copyright 2024 Wunderbyte Gmbh <info@wunderbyte.at>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_wunderbyte_table\filters;

defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir . '/formslib.php');

use ReflectionClass;
/**
 * Handles the filter classes.
 * @package local_wunderbyte_table
 */
class filter_manager extends filtersettings {
    /**
     * Handles form definition of filter classes.
     */
    public function __construct() {
    }

    /**
     * Handles form definition of filter classes.
     * @param string $classname
     * @param array $data
     * @param string $filterspecifictype
     * @return \MoodleQuickForm
     */
    public function get_mandatory_filter_fields($classname, $data = [], $filterspecifictype = '') {
        $mform = new \MoodleQuickForm('dynamicform', 'post', '');

        $mform->addElement('html', '<div id="filter-add-field">');
        $mform->addElement('header', 'add_pair', 'Add new key value pair');
        $staticfunctionname = 'render_mandatory_fields';
        if (self::is_static_public_function($classname, $staticfunctionname)) {
            $classname::$staticfunctionname($mform, [[]], $filterspecifictype);
        }
        $mform->addElement('html', '</div>');

        return $mform;
    }
}
