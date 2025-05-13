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

namespace local_wunderbyte_table\filters;

use coding_exception;
use local_wunderbyte_table\local\customfield\wbt_field_controller_info;
use local_wunderbyte_table\filter;
use local_wunderbyte_table\wunderbyte_table;
use moodle_exception;
use MoodleQuickForm;
use stdClass;

/**
 * Wunderbyte table class is an extension of table_sql.
 */
abstract class base {
    /**
     * @var array instances
     */
    protected static array $instances = [];
    /**
     * @var string columnidentifier
     */
    protected string $columnidentifier = '';

    /**
     * @var string localizedstring
     */
    protected string $localizedstring = '';

    /**
     * @var string secondcolumnidentifier
     */
    protected string $secondcolumnidentifier = '';

    /**
     * @var string secondcolumnidentifier
     */
    protected string $secondcolumnlocalized = '';

    /**
     * Options are there to sort or localize filter results.
     * @var array
     */
    protected array $options = [];

    /**
     * Property to indicate if class has implemented a callback
     *
     * @var bool
     */
    public $hascallback = false;

    /**
     * Expected value.
     *
     * @var string
     */
    public $expectedvalue;

    /**
     * Set the column which should be filtered and possibly localize it.
     * @param string $columnidentifier
     * @param string $localizedstring
     * @param string $secondcolumnidentifier
     * @param string $secondcolumnlocalized
     * @return void
     */
    public function __construct(
        string $columnidentifier,
        string $localizedstring = '',
        string $secondcolumnidentifier = '',
        string $secondcolumnlocalized = ''
    ) {

        $this->columnidentifier = $columnidentifier;
        $this->localizedstring = empty($localizedstring) ? $columnidentifier : $localizedstring;
        $this->secondcolumnidentifier = $secondcolumnidentifier;
        $this->secondcolumnlocalized = empty($secondcolumnlocalized) ? $secondcolumnidentifier : $secondcolumnlocalized;
    }

    /**
     * Handles form definiton for filter classes.
     * @param MoodleQuickForm $mform
     * @param array $formdata
     * @param stdClass $filter
     * @return void
     * @throws coding_exception
     */
    public static function definition(MoodleQuickForm &$mform, array &$formdata, stdClass $filter) {
        $classname = get_called_class();
        // We only want the last part of the classname.
        $array = explode('\\', $classname);
        $classname = array_pop($array);

        $mform->addElement(
            'advcheckbox',
            $filter->columnidentifier . '_wb_checked',
            get_string('showfilter', 'local_wunderbyte_table'),
            $filter->localizedname
        );
        $mform->addElement(
            'text',
            $filter->columnidentifier . '_wb_localizedname',
            get_string('editfiltername', 'local_wunderbyte_table'),
            $filter->localizedname
        );
    }


    /**
     * Set data for form.
     * @param stdClass $data
     * @param stdClass $filter
     * @return void
     */
    public static function set_data(stdClass $data, stdClass $filter) {

        $data->{$filter->columnidentifier . '_wb_checked'}
            = $filter->{$filter->columnidentifier . '_wb_checked'};
        $data->{$filter->columnidentifier . '_wb_localizedname'}
            = $filter->localizedname;
    }

    /**
     * Add the filter to the array.
     * @param array $filter
     * @param bool $invisible
     * @return void
     * @throws moodle_exception
     */
    public function add_filter(array &$filter, bool $invisible = false) {

        $options = $this->options;

        $options['localizedname'] = $this->localizedstring;
        $options['wbfilterclass'] = get_called_class();
        $options[$this->columnidentifier . '_wb_checked'] = $invisible ? 0 : 1;

        // We always need to make sure that id column is present.
        if (!isset($filter['id'])) {
            $filter['id'] = [
                'localizedname' => get_string('id', 'local_wunderbyte_table'),
                'id_wb_checked' => $this->columnidentifier === 'id' ? 0 : 1,
            ];
        } else {
            // If we don't add the id column, we want the filter to be visible, normally.
            // If not, we have to use the hide_filter() method after definining the filters.
            $filter['id']['id_wb_checked'] = 1;
        }

        if (!isset($filter[$this->columnidentifier])) {
            $filter[$this->columnidentifier] = $options;
        } else if ($this->columnidentifier !== 'id') {
            throw new moodle_exception(
                'filteridentifierconflict',
                'local_wunderbyte_table',
                '',
                $this->columnidentifier,
                'Every column can have only one filter applied'
            );
        }
    }

    /**
     * Get standard filter options.
     * @param wunderbyte_table $table
     * @param string $key
     * @return array
     */
    public static function get_data_for_filter_options(wunderbyte_table $table, string $key) {
        $returnarray = filter::get_db_filter_column($table, $key);
        return $returnarray ?? [];
    }

    /**
     * Adds the array for the mustache template to render the categoryobject.
     * If no special treatment is needed, it must be implemented in the filter class, but just return.
     * The standard filter will take care of it.
     * @param array $categoryobject
     * @param array $filtersettings
     * @param string $fckey
     * @param array $values
     * @return void
     */
    public static function add_to_categoryobject(array &$categoryobject, array $filtersettings, string $fckey, array $values) {
        // Don't treat this filter if there are no values here.
        if (!is_array($values)) {
            return;
        }

        $valueswithcount = self::apply_filtercount($values, $fckey, $filtersettings);
        // If we have JSON, we need special treatment.
        if (!empty($filtersettings[$fckey]['jsonattribute'])) {
            $valuescopy = $values;
            $values = [];

            // We run through the array of values containing the JSON strings.
            foreach ($valuescopy as $jsonstring => $boolvalue) {
                // Convert into an array, so we can handle items with multiple objects.
                $jsonstring = '[' . $jsonstring . ']';
                $jsonarray = json_decode($jsonstring);

                foreach ($jsonarray as $jsonobj) {
                    if (empty($jsonobj)) {
                        continue;
                    }
                    // We only want to show the attribute of the JSON which is relevant for the filter.
                    $searchattribute = $jsonobj->{$filtersettings[$fckey]['jsonattribute']};
                    $values[$searchattribute] = true;
                }
            }

            unset($filtersettings[$fckey]['json']);
        }

        // We have to check if we have a sortarray for this filtercolumn.
        if (
            isset($filtersettings[$fckey])
            && count($filtersettings[$fckey]) > 0
        ) {
            $sortarray = $filtersettings[$fckey];
        } else {
            $sortarray = null;
        }

        // First we create our sortedarray and add all values in the right order.
        if ($sortarray != null) {
            $sortedarray = [];
            foreach ($sortarray as $sortkey => $sortvalue) {
                if (isset($values[$sortkey])) {
                    // Exchange the value in the valueswithcount array.
                    $valueswithcount[$sortvalue] = $valueswithcount[$sortkey];
                    unset($valueswithcount[$sortkey]);

                    // And in the sortedarray.
                    $sortedarray[$sortvalue] = $sortkey;
                    unset($values[$sortkey]);
                }
            }

            // Now we make sure we havent forgotten any values.
            // If so, we sort them and add them at the end.
            if (count($values) > 0) {
                // First sort the values first.
                ksort($values);

                foreach ($values as $unsortedkey => $unsortedvalue) {
                    $sortedarray[$unsortedkey] = true;
                }
            }

            // Finally, we pass the sorted array to the values back.
            $values = $sortedarray;
        } else {
            $values = array_combine(array_keys($values), array_keys($values));
        }

        $identifierarray = [];
        foreach ($values as $valuekey => $valuevalue) {
            if (
                isset($filtersettings[$fckey])
                && isset($filtersettings[$fckey][$valuevalue])
                && $filtersettings[$fckey][$valuevalue] === 'wbt_suppress'
            ) {
                continue;
            }

            $baseidentifier = preg_replace('/[^a-zA-Z0-9\-_\.]/', '-', 'id' . $valuekey);
            $i = 1;
            $identifier = $baseidentifier;
            while (in_array($identifier, $identifierarray)) {
                $identifier = $baseidentifier . $i;
                $i++;
            }
            $identifierarray[] = $identifier;

            if (isset($sortedarray[$valuekey]) && $sortedarray[$valuekey] === true) {
                // For custom fields, we get the actual string value from field controller.
                $fieldcontroller = wbt_field_controller_info::get_instance_by_shortname($fckey);
                if (!empty($fieldcontroller)) {
                    $cfstringvalueforvaluekey = $fieldcontroller->get_option_value_by_key($valuekey);
                    if ($cfstringvalueforvaluekey == wbt_field_controller_info::WBTABLE_CUSTOMFIELD_VALUE_NOTFOUND) {
                        continue;
                    }
                }
            }

            $itemobject = [
                // We do not want to show HTML tags or HTML entities, so replace &amp; with &.
                'key' => strip_tags(str_replace("&amp;", "&", $cfstringvalueforvaluekey ?? $valuekey)),
                'value' => $valuevalue === true ? $valuekey : $valuevalue,
                'identifier' => $identifier,
                'category' => $fckey,
            ];

            // Count may not be used, so we have an extra check.
            if (!empty($valueswithcount[$valuekey])) {
                $itemobject['count'] = $valueswithcount[$valuekey];
            }

            $categoryobject['default']['values'][$valuekey] = $itemobject;
        }

        if (!isset($categoryobject['default']) || count($categoryobject['default']['values']) == 0) {
            // We don't add the filter if there is nothing in there.
            return;
        }

        if ($sortarray == null) {
            // If we didn't sort otherwise, we do it now.
            ksort($categoryobject['default']['values']);
        }

        // Make the arrays mustache ready, we have to jump through loops.
        $categoryobject['default']['values'] = array_values($categoryobject['default']['values']);
        return;
    }

    /**
     * Check if values need to be exploded and count correctly.
     *
     * @param array $values
     * @param string $fckey
     * @param array $filtersettings
     *
     * @return array
     *
     */
    public static function apply_filtercount(array &$values, string $fckey, array &$filtersettings): array {
        $valueswithcount = $values;

        // We might need to explode values, because of a multi-field.
        if (
            isset($filtersettings[$fckey]['explode'])
            || filter::check_if_multi_customfield($fckey)
        ) {
            // We run through the array of values and explode each item.
            foreach ($values as $keytoexplode => $recordscount) {
                $separator = $filtersettings[$fckey]['explode'] ?? ',';

                $explodedarray = explode($separator, $keytoexplode);

                // Only if we have more than one item, we unset key and insert all the new keys we got.
                if (count($explodedarray) > 1) {
                    // Run through all the keys.
                    foreach ($explodedarray as $explodeditem) {
                        // Make sure we don't have any empty values.
                        $explodeditem = trim($explodeditem);

                        if (empty($explodeditem)) {
                            continue;
                        }

                        $values[$explodeditem] = true;
                        // Recordscount can be more than 1, since we use a group by query.
                        $valueswithcount[$explodeditem] =
                            isset($valueswithcount[$explodeditem])
                                ? $valueswithcount[$explodeditem] + (int)$recordscount : (int)$recordscount;
                    }
                    // We make sure the strings with more than one values are not treated anymore.
                    unset($values[$keytoexplode]);
                }
            }
            unset($filtersettings[$fckey]['explode']);
        }
        return $valueswithcount;
    }

    /**
     * Definition after data callback
     * @return string
     * @throws coding_exception
     */
    public static function return_localized_name() {

        $classname = get_called_class();
        // We only want the last part of the classname.
        $array = explode('\\', $classname);
        $classname = array_pop($array);

        return get_string($classname, 'local_wunderbyte_table');
    }

    /**
     * Adds the specific part of SQL to the filterstring.
     *
     * @param string $filter
     * @param string $columnname
     * @param mixed $categoryvalue
     * @param wunderbyte_table $table
     *
     * @return void
     *
     */
    public function apply_filter(
        string &$filter,
        string $columnname,
        $categoryvalue,
        wunderbyte_table &$table
    ): void {
        global $DB;
        $filtercounter = 1;
        $filter .= " ( ";
        foreach ($categoryvalue as $key => $value) {
            $filter .= $filtercounter == 1 ? "" : " OR ";
            // Apply special filter here.
            if (
                isset($table->subcolumns['datafields'][$columnname]['jsonattribute'])
            ) {
                    $paramsvaluekey = $table->set_params("%" . $value . "%");
                    $filter .= $DB->sql_like("$columnname", ":$paramsvaluekey", false);
            } else {
                // We want to find the value in an array of values.
                // Therefore, we have to use or as well.
                // First, make sure we have enough params we can use..
                $separator = $table->subcolumns['datafields'][$columnname]['explode'] ?? ",";
                $paramsvaluekey = $table->set_params('%' . $separator . $value . $separator . '%', true);
                $escapecharacter = wunderbyte_table::return_escape_character($value);
                $concatvalue = $DB->sql_concat("'$separator'", $columnname, "'$separator'");
                $filter .= $DB->sql_like("$concatvalue", ":$paramsvaluekey", false, false, false, $escapecharacter);
            }
            $filtercounter++;
        }
        $filter .= " ) ";
    }

    /**
     * Getter for column identifier string.
     *
     * @return string
     *
     */
    public function return_columnidentifier() {
        return $this->columnidentifier;
    }

    /**
     * Add keys and values for applied filters. This will only be applied if filter is active.
     *
     * @param mixed $tableobject
     * @param array $filterarray
     * @param int $key
     *
     * @return void
     *
     */
    public static function prepare_filter_for_rendering(&$tableobject, array $filterarray, int $key) {
        return;
    }

    /**
     * Function to filter std Class records by the set callback.
     * We have positiv & negativ filter.
     *
     * @param array $records
     * @param bool $not
     *
     * @return array
     *
     */
    public function filter_by_callback(array $records, $not = false) {
        // In the base version, we do no filtering either way.
        return $records;
    }

    /**
     * The expected value.
     *
     * @param string $expectedvalue
     *
     * @return void
     *
     */
    public function set_expected_value(string $expectedvalue) {
        $this->expectedvalue = $expectedvalue;
    }

    /**
     * The expected value.
     * @param \MoodleQuickForm $mform
     * @param array $data
     * @param string $filterspecificvalue
     */
    public static function render_mandatory_fields(&$mform, $data = [], $filterspecificvalue = '') {
        $mform = new MoodleQuickForm('dynamicform', 'post', '');
        $mform->addElement(
            'static',
            'user_information',
            '',
            'No additional fields needed'
        );
    }

    /**
     * The expected value.
     * @param array $data
     * @return array
     */
    public static function validate_filter_data($data) {
        return ['name' => 'error'];
    }

    /**
     * The expected value.
     * @param array $fieldsandsubmitteddata
     */
    public static function get_dynamic_values($fieldsandsubmitteddata) {
        return [];
    }

    /**
     * The expected value.
     * @param object $data
     * @param string $filtercolumn
     * @return array
     */
    public static function get_filterspecific_values($data, $filtercolumn) {
        $filterenablelabel = $filtercolumn . '_wb_checked';
        $filterspecificvalues = [
            'localizedname' => $data->localizedname ?? '',
            'wbfilterclass' => $data->wbfilterclass ?? '',
            $filterenablelabel => $data->$filterenablelabel ?? '0',
        ];
        return [$filterspecificvalues, ''];
    }
}
