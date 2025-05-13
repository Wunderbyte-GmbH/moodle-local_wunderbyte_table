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
     * @param array $data
     * @param string $filterspecificvalue
     */
    public static function render_mandatory_fields(&$mform, $data = [[]], $filterspecificvalue = '') {
        foreach ($data as $key => $keyvaluepair) {
            if (count($data) > 1 && empty($key)) {
                continue;
            }
            $elements = [];
            $elements[] = $mform->createElement('text', 'keyvaluepairs[' . $key . '][key]', '', ['placeholder' => 'Key']);
            if (!empty($keyvaluepair['key'])) {
                $mform->setDefault('keyvaluepairs[' . $key . '][key]', $keyvaluepair['key']);
            }
            $elements[] = $mform->createElement('text', 'keyvaluepairs[' . $key . '][value]', '', ['placeholder' => 'Value']);
            if (!empty($keyvaluepair['value'])) {
                $mform->setDefault('keyvaluepairs[' . $key . '][value]', $keyvaluepair['value']);
            }
            if (!empty($key)) {
                $elements[] = self::generate_delete_button($mform, $key);
            }
            $grouplabelname = empty($key) ? 'New' : $key;
            $mform->addGroup($elements, $key . '_group', $grouplabelname . ' values', '<br>', false);
        }
    }

    /**
     * The expected value.
     * @param \MoodleQuickForm $mform
     * @param string $key
     */
    private static function generate_delete_button($mform, $key) {
        $trashicon = '<i class="fa fa-trash"></i>';
        return $mform->createElement(
            'button',
            "remove[{$key}_group]",
            $trashicon,
            [
                'class' => 'btn remove-key-value',
                'type' => 'button',
                'data-groupid' => $key . '_group',
                'aria-label' => "Remove key-value pair for {$key}",
            ]
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

    /**
     * Handles form definition of filter classes.
     * @param string $filtercolumn
     * @return array
     */
    public static function non_kestringy_value_pair_properties($filtercolumn) {
        return [
            'localizedname',
            'wbfilterclass',
            $filtercolumn . '_wb_checked',
        ];
    }

    /**
     * The expected value.
     * @param array $data
     * @return array
     */
    public static function validate_input($data) {
        $errors = [];
        foreach ($data['keyvaluepairs'] as $key => $keyvaluepair) {
            if (self::only_partial_submitted($keyvaluepair)) {
                $errors[$key . '_group'] = get_string('standardfiltervaluekeyerror', 'local_wunderbyte_table');
            }
        }
        return $errors;
    }

    /**
     * The expected value.
     * @param array $keyvaluepair
     * @return bool
     */
    private static function only_partial_submitted($keyvaluepair) {
        if (
            empty($keyvaluepair['key']) !== empty($keyvaluepair['value'])
        ) {
            return true;
        }
        return false;
    }

    /**
     * The expected value.
     * @param array $data
     * @param string $filtercolumn
     * @return array
     */
    public static function get_filterspecific_values($data, $filtercolumn) {
        $filterenablelabel = $filtercolumn . '_wb_checked';
        $filterunspecificvalues = [
            'localizedname',
            'wbfilterclass',
            $filterenablelabel,
        ];
        $filterspecificvalues = [];
        foreach ($data as $key => $value) {
            if (!in_array($key, $filterunspecificvalues)) {
                $filterspecificvalues[$key] = [
                    'key' => $key,
                    'value' => $value,
                ];
            }
        }
        return [$filterspecificvalues, ''];
    }

    /**
     * The expected value.
     * @param object $data
     * @param string $filtercolumn
     * @return array
     */
    public static function get_new_filter_values($data, $filtercolumn) {
        $filterenablelabel = $filtercolumn . '_wb_checked';
        $filterspecificvalues = [
            'localizedname' => $data->localizedname ?? '',
            'wbfilterclass' => $data->wbfilterclass ?? '',
            $filterenablelabel => $data->$filterenablelabel ?? '0',
        ];
        foreach ($data->keyvaluepairs as $key => $keyvaluepair) {
            if (!empty($keyvaluepair['key']) && !empty($keyvaluepair['value'])) {
                $filterspecificvalues[$keyvaluepair['key']] = $keyvaluepair['value'];
            }
        }
        return $filterspecificvalues;
    }
}
