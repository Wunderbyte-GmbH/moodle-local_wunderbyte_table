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
class span {
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
    public function __construct($mform, $valuelabel, $filtertype) {
        $this->mform = $mform;
        $this->valuelabel = $valuelabel;
        $this->filtertype = $filtertype;
    }

    /**
     * The expected value.
     * @return array
     */
    public function get_inputs() {
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

        $possibleoperationsinput = $this->mform->createElement(
            'select',
            $this->valuelabel . '[possibleoperations]',
            '',
            self::get_operatoroptions(),
            ['multiple' => 'multiple']
        );

        $defaultvaluestartinput = $this->mform->createElement(
            'date_selector',
            $this->valuelabel  . '[defaultvaluestart]',
            'from'
        );

        $defaultvalueendinput = $this->mform->createElement(
            'date_selector',
            $this->valuelabel  . '[defaultvalueend]',
            0
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
                '<br><label>' . get_string('datepickerheadingpossibleoperations', 'local_wunderbyte_table') . '</label>'
            ),
            $possibleoperationsinput,
            $this->mform->createElement(
                'static',
                '',
                '',
                '<br><label>' . get_string('datepickerheadingdefaultvaluestart', 'local_wunderbyte_table') . '</label>'
            ),
            $defaultvaluestartinput,
            $this->mform->createElement(
                'static',
                '',
                '',
                '<br><label>' . get_string('datepickerheadingdefaultvalueend', 'local_wunderbyte_table') . '</label>'
            ),
            $defaultvalueendinput,
        ];
        return $form;
    }

    /**
     * The expected value.
     * @return array
     */
    private function get_operatoroptions() {
        return [
            0 => "within",
            1 => "overlapboth",
            2 => "overlapstart",
            3 => "overlapend",
            4 => "before",
            5 => "after",
            6 => "flexoverlap",
        ];
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
            $this->valuelabel . '[possibleoperations]',
            self::get_operatoroptions_index($this->filtertype['possibleoperations'] ?? [])
        );
        $this->mform->setDefault(
            $this->valuelabel . '[defaultvaluestart]',
            $this->filtertype['defaultvaluestart'] ?? time()
        );
        $this->mform->setDefault(
            $this->valuelabel . '[defaultvalueend]',
            $this->filtertype['defaultvalueend'] ?? time()
        );
    }

    /**
     * The expected value.
     * @param array $selectedoptions
     * @return array
     */
    public function get_operatoroptions_index($selectedoptions) {
        if (is_null($selectedoptions)) {
            $selectedoptions = [];
        }
        $possibleoperations = self::get_operatoroptions();
        $possibleoperationsindex = [];

        foreach ($possibleoperations as $index => $value) {
            if (in_array($value, $selectedoptions)) {
                $possibleoperationsindex[] = $index;
            }
        }
        return $possibleoperationsindex;
    }
}
