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

 namespace local_wunderbyte_table\filters\types\datepickers;

/**
 * Class unit
 * @author Jacob Viertel
 * @copyright 2025 Wunderbyte GmbH
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class timestamp {
    /** @var \MoodleQuickForm  instances */
    protected \MoodleQuickForm $mform;
    /** @var string columnidentifier */
    protected string $valuelabel;
    /** @var array localizedstring */
    protected array $filtertype;

    /**
     * Set the column which should be filtered and possibly localize it.
     * @param \MoodleQuickForm $mform
     * @param string $valuelabel
     * @param array $filtertype
     * @return void
     */
    public function __construct(&$mform, $valuelabel, $filtertype) {
        $this->mform = $mform;
        $this->valuelabel = $valuelabel;
        $this->filtertype = $filtertype;
    }

    /**
     * The expected value.
     * @param bool $hidden
     * @return array
     */
    public function get_inputs($hidden = false) {
        $nameinput = $this->mform->createElement(
            'text',
            $this->valuelabel  . '[name]',
            '',
            ['placeholder' => get_string('datepickerplaceholdername', 'local_wunderbyte_table')]
        );

        $checkboxlabelinput = $this->mform->createElement(
            'text',
            $this->valuelabel  . '[checkboxlabel]',
            '',
            ['placeholder' => get_string('datepickerplaceholdercheckboxlabel', 'local_wunderbyte_table')]
        );

        $operatorinput = $this->mform->createElement(
            'select',
            $this->valuelabel . '[operator]',
            '',
            self::get_operators()
        );

        $defaultvalueinput = $this->mform->createElement(
            'date_selector',
            $this->valuelabel  . '[defaultvalue]',
            '',
        );

        $form = [
            $this->mform->createElement(
                'static',
                '',
                '',
                '<br><label>' . get_string('datepickerheadingname', 'local_wunderbyte_table') . '</label>'
            ),
            $nameinput,
            $this->mform->createElement(
                'static',
                '',
                '',
                '<br><label>' . get_string('datepickerheadingcheckboxlabel', 'local_wunderbyte_table') . '</label>'
            ),
            $checkboxlabelinput,
            $this->mform->createElement(
                'static',
                '',
                '',
                '<br><label>' . get_string('datepickerheadingoperation', 'local_wunderbyte_table') . '</label>'
            ),
            $operatorinput,
            $this->mform->createElement(
                'static',
                '',
                '',
                '<br><label>' . get_string('datepickerheadingdefaultvalue', 'local_wunderbyte_table') . '</label>'
            ),
            $defaultvalueinput,
        ];
        return $form;
    }

    /**
     * Setting the default values.
     */
    public function set_inputs() {
        $this->mform->setDefault(
            $this->valuelabel . '[name]',
            $this->filtertype['name'] ?? ''
        );
        $this->mform->setDefault(
            $this->valuelabel . '[checkboxlabel]',
            $this->filtertype['checkboxlabel'] ?? ''
        );
        $this->mform->setDefault(
            $this->valuelabel . '[operator]',
            $this->filtertype['operator'] ?? ''
        );
        $timestamp = $this->filtertype['defaultvalue'] ?? time();
        if (is_string($timestamp)) {
            $timestamp = time();
        }
        $this->mform->setDefault(
            $this->valuelabel . '[defaultvalue]',
            $timestamp
        );
    }

    /**
     * The expected value.
     * @return array
     */
    private function get_operators() {
        return [
            '=' => '=',
            '<' => '<',
            '>' => '>',
            '<=' => '<=',
            '>=' => '>=',
        ];
    }
}
