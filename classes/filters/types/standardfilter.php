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

namespace local_wunderbyte_table\filters\types;
use local_wunderbyte_table\filters\base;
use local_wunderbyte_table\filters\type_form_builder\standardfilter_form_builder;

/**
 * Wunderbyte table class is an extension of table_sql.
 */
class standardfilter extends base {
    /**
     *
     * @var string
     */
    public static $groupname = 'standardfiltergroup';

    /**
     *
     * @var array
     */
    public static $grouplabels = [
        'key',
        'value',
    ];

    /**
     * Property to indicate if class has implemented a callback
     *
     * @var bool
     */
    public $hascallback = false;

    /**
     * Callable function
     *
     * @var callable|null
     */
    public $callback = null;

    /**
     * SQL (including properties field, where, from) to append to table sql.
     *
     * @var \stdClass
     */
    private $sql;

    /**
     * This function adds sql to the table sql.
     *
     * @param wunderbyte_table $table
     *
     * @return void
     *
     */
    public function add_sql(wunderbyte_table $table) {
    }

    /**
     * [Description for define_sql]
     *
     * @param string $field
     * @param string $from
     * @param string $where
     *
     * @return void
     *
     */
    public function define_sql(string $field, string $from, string $where) {
        $sql = [
            'field' => $field,
            'from' => $from,
            'where' => $where,
        ];
        $this->sql = $sql;
    }

    /**
     * This function takes a key value pair of options.
     * Only if there are actual results in the table, these options will be displayed.
     * The keys are the results, the values are the localized strings.
     * For the standard filter, it's not necessary to provide these options...
     * They will be gathered automatically.
     *
     * @param array $options
     * @return void
     */
    public function add_options(array $options = []) {
        foreach ($options as $key => $value) {
            $this->options[$key] = $value;
        }
    }

    /**
     * The expected value.
     * @param \MoodleQuickForm $mform
     */
    public static function render_mandatory_fields(&$mform, $data = []) {
        $groupelements = [];
        $label = '';

        if ($mform->elementExists('add_pair')) {
            $formbuilder = new standardfilter_form_builder(null, null, $mform);
            if ($data) {
                $formbuilder->set_unsaved_new_fields($data);
            }
            $formbuilder->generate_mandatory_standardfilter_fields($groupelements);
            $label = get_string('standardfiltergrouplabel', 'local_wunderbyte_table');
        } else if ($mform->elementExists('existing_pairs')) {
            foreach ($data as $key => $value) {
                $formbuilder = new standardfilter_form_builder($key, $value, $mform);
                $formbuilder->generate_mandatory_standardfilter_fields($groupelements);
            }
        }

        $mform->addGroup(
            $groupelements,
            self::$groupname,
            $label,
            [' '],
            false
        );
    }

    /**
     * The expected value.
     * @param array $fieldsandsubmitteddata
     * @return mixed
     */
    public static function get_dynamic_values($fieldsandsubmitteddata) {
        $mandatoryfields = $fieldsandsubmitteddata['form'];
        $filtergroup = $mandatoryfields->getElement(self::$groupname);
        if ($filtergroup) {
            foreach ($filtergroup->_elements as $groupelement) {
                $label = $groupelement->_attributes['name'];
                if (in_array($label, self::$grouplabels)) {
                    $groupelement->_attributes['value'] = $fieldsandsubmitteddata['data'][$label] ?? null;
                    $mandatoryfields->setElementError(self::$groupname, $fieldsandsubmitteddata['errors'][$label]);
                }
            }
        }
        return $mandatoryfields;
    }
}
