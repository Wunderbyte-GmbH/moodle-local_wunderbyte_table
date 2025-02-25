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

namespace local_wunderbyte_table\filters\type_form_builder;

/**
 * Wunderbyte table class is an extension of table_sql.
 */
class standardfilter_form_builder {
    /** @var string */
    private $key;
    /** @var string */
    private $value;
    /** @var \MoodleQuickForm */
    private $mform;
    /** @var string */
    private $groupid;
    /** @var array */
    private $unsaveddatakey;
    /** @var array */
    private $unsaveddatavalue;

    /**
     * The expected value.
     * @param string $key
     * @param string $value
     * @param \MoodleQuickForm $mform
     */
    public function __construct($key, $value, &$mform) {
        $this->key = $key;
        $this->value = $value;
        $this->mform = $mform;
        $this->groupid = "group_{$key}";
        $this->unsaveddatakey = [];
        $this->unsaveddatavalue = [];
    }

    /**
     * The expected value.
     * @param array $groupedelements
     */
    public function generate_mandatory_standardfilter_fields(&$groupedelements) {
        $keylabelinput = $this->generate_pair_label_input('key');
        $valuelabelinput = $this->generate_pair_label_input('value');
        if (!empty($this->key)) {
            $removebutton = $this->generate_delete_button();
        }
        $singlegroupelement = array_merge($keylabelinput, $valuelabelinput, $removebutton ?? []);
        self::pack_group_elements($groupedelements, $singlegroupelement);
    }

    /**
     * The expected value.
     * @param string $type
     * @return array
     */
    private function generate_pair_label_input($type) {
        $keylabel = $this->key ?? "0";
        $valuelabel = 'keyvaluepairs[' . $keylabel . '][' . $type . ']';
        $typename = get_string("standardfilter{$type}label", 'local_wunderbyte_table');
        $label = $this->mform->createElement('static', "{$this->key}_{$type}_label", '', $typename);
        $input = $this->mform->createElement('text', $valuelabel, '', ['size' => '20']);
        $this->mform->setType($valuelabel, PARAM_TEXT);
        $unsavedkey = "unsaveddata" . $type;
        if (!empty($this->$unsavedkey)) {
            $this->mform->setDefault($valuelabel, $this->$unsavedkey);
        } else {
            $this->mform->setDefault($valuelabel, $this->$type);
        }
        return [
            "{$type}label" => $label,
            "{$type}keyinput" => $input,
        ];
    }

    /**
     * The expected value.
     * @return array
     */
    private function generate_delete_button() {
        $trashicon = '<i class="fa fa-trash"></i>';
        $removebutton = $this->mform->createElement(
            'button',
            "remove[{$this->key}]",
            $trashicon,
            [
                'class' => 'btn remove-key-value',
                'type' => 'button',
                'data-groupid' => $this->groupid,
                'aria-label' => "Remove key-value pair for {$this->key}",
            ]
        );
        return [$removebutton];
    }


    /**
     * The expected value.
     * @param array $groupedelements
     * @param array $singlegroupelement
     */
    private function pack_group_elements(&$groupedelements, $singlegroupelement) {
        $group = $this->mform->createElement('group', "group_{$this->key}", '', $singlegroupelement, ' ', false);
        $groupwrapperstart = '<div id="' . $this->groupid . '" class="key-value-group">';
        $groupwrapperend = '</div>';
        $groupedelements[] = $this->mform->createElement('html', $groupwrapperstart);
        $groupedelements[] = $group;
        $groupedelements[] = $this->mform->createElement('html', $groupwrapperend);
    }

    /**
     * The expected value.
     * @param array $data
     */
    public function set_unsaved_new_fields($data) {
        $this->unsaveddatakey = array_keys($data)[0];
        $this->unsaveddatavalue = array_values($data)[0];
    }
}
