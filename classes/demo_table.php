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
 * @copyright 2023 Wunderbyte Gmbh <info@wunderbyte.at>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
// phpcs:ignoreFile

namespace local_wunderbyte_table;

use local_wunderbyte_table\dynamicactionelements\dynamiccheckbox;
use local_wunderbyte_table\dynamicactionelements\dynamicselect;
use local_wunderbyte_table\dynamicactionelements\dynamictextinput;

defined('MOODLE_INTERNAL') || die();

use local_wunderbyte_table\output\table;
use stdClass;

/**
 * Wunderbyte table demo class.
 */
class demo_table extends wunderbyte_table {

    /**
     * Decodes the Unix Timestamp
     *
     * @param stdClass $values
     * @return void
     */
    public function col_added($values) {
        return userdate($values->added, get_string('strftimedatetimeshort'));
    }

    /**
     * Decodes the Unix Timestamp
     *
     * @param stdClass $values
     * @return void
     */
    public function col_timecreated($values) {
        return userdate($values->timecreated);
    }

    /**
     * Decodes the Unix Timestamp
     *
     * @param stdClass $values
     * @return void
     */
    public function col_timemodified($values) {
        return userdate($values->timemodified, get_string('strftimedatetimeshort'));
    }

    /**
     * Decodes the Unix Timestamp
     *
     * @param stdClass $values
     * @return void
     */
    public function col_startdate($values) {
        return userdate($values->startdate);
    }

    /**
     * Decodes the Unix Timestamp
     *
     * @param stdClass $values
     * @return void
     */
    public function col_enddate($values) {
        return userdate($values->enddate);
    }

    /**
     * This handles the action column with buttons, icons, checkboxes.
     *
     * @param stdClass $values
     * @return void
     */
    public function col_action($values) {

        global $OUTPUT;

        $data[] = [
            //'label' => get_string('delete', 'core'), // Name of your action button.
            'label' => 'TriggersNoModal', // Name of your action button.
            'class' => 'btn btn-danger',
            'href' => '#', // You can either use the link, or JS, or both.
            'iclass' => 'fa fa-cog', // Add an icon before the label.
            'arialabel' => 'cogwheel', // Add an aria-label string to your icon.
            'title' => 'Edit', // We be displayed when hovered over icon.
            'id' => $values->id.'-'.$this->uniqueid,
            'name' => $this->uniqueid.'-'.$values->id,
            'methodname' => 'deleteitem', // The method needs to be added to your child of wunderbyte_table class.
            'nomodal' => true,
            'data' => [ // Will be added eg as data-id = $values->id, so values can be transmitted to the method above.
                'id' => $values->id,
            ]
        ];

        $data[] = [
            //'label' => get_string('add', 'core'), // Name of your action button.
            'label' => 'TriggersModal', // Name of your action button.
            'class' => 'btn btn-success',
            'href' => '#', // You can either use the link, or JS, or both.
            'iclass' => 'fa fa-edit', // Add an icon before the label.
            'id' => $values->id.'-'.$this->uniqueid,
            'name' => $this->uniqueid.'-'.$values->id,
            'methodname' => 'additem', // The method needs to be added to your child of wunderbyte_table class.
            'data' => [ // Will be added eg as data-id = $values->id, so values can be transmitted to the method above.
                'id' => $values->id,
                'labelcolumn' => 'username',
            ]
        ];

        $data[] = dynamiccheckbox::generate_data($values->id, $this->uniqueid);;
        $data[] = dynamicselect::generate_data($values->id, $this->uniqueid);
        $data[] = dynamictextinput::generate_data($values->id, $this->uniqueid);

        // This transforms the array to make it easier to use in mustache template.
        table::transform_actionbuttons_array($data);

        return $OUTPUT->render_from_template('local_wunderbyte_table/component_actionbutton', ['showactionbuttons' => $data]);
    }

    /**
     * Delete item.
     *
     * @param int $id
     * @param string $data
     * @return array
     */
    public function action_deleteitem(int $id, string $data):array {

        return [
            'success' => 1,
            'message' => 'Did work',
        ];
    }

    /**
     * Add item.
     *
     * @param int $id
     * @param string $data
     * @return array
     */
    public function action_additem(int $id, string $data):array {

        return [
            'success' => 1,
            'message' => 'Did work',
        ];
    }

    /**
     * Toggle Checkbox
     *
     * @param int $id
     * @param string $data
     * @return array
     */
    public function action_togglecheckbox(int $id, string $data):array {

        $dataobject = json_decode($data);
        return [
           'success' => 1,
           'message' => $dataobject->state == 'true' ? 'checked' : 'unchecked',
        ];
    }
    /**
     * Toggle Checkbox
     *
     * @param int $id
     * @param string $data
     * @return array
     */
    public function action_textinputchange(int $id, string $data):array {
        $dataobject = json_decode($data);
        return [
           'success' => 1,
           'message' => 'Entered string is: ' . $dataobject->value,
        ];
    }
    /**
     * Toggle Checkbox
     *
     * @param int $id
     * @param string $data
     * @return array
     */
    public function action_selectoption(int $id, string $data):array {
        $dataobject = json_decode($data);
        return [
           'success' => 1,
           'message' => 'Selected option: ' . $dataobject->selectedValue,
        ];
    }

}
