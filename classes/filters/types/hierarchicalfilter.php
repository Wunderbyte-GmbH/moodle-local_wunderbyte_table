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
use local_wunderbyte_table\wunderbyte_table;
use local_wunderbyte_table\local\customfield\wbt_field_controller_info;

/**
 * Wunderbyte table class is an extension of table_sql.
 */
class hierarchicalfilter extends base {
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
     * Adds the array for the mustache template to render the categoryobject.
     * If no special treatment is needed, it must be implemented in the filter class, but just return.
     * The standard filter will take care of it.
     * @param array $categoryobject
     * @param array $filtersettings
     * @param string $fckey
     * @param array $values
     * @return array
     */
    public static function add_to_categoryobject(array &$categoryobject, array $filtersettings, string $fckey, array $values) {

        // Don't treat this filter if there are no values here.
        if (!is_array($values)) {
            return;
        }

        $valueswithcount = base::apply_filtercount($values, $fckey, $filtersettings);

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
                    if (isset($sortvalue['localizedname'])) {
                        $localizedname = $sortvalue['localizedname'];
                    } else {
                        $localizedname = $sortkey;
                    }

                    if (isset($sortvalue['parent'])) {
                        $sortedarray[$sortvalue['parent']][$localizedname] = $sortkey;
                    } else {
                        $sortedarray['other'][$localizedname] = $sortkey;
                    }

                    unset($values[$sortkey]);
                }
            }

            // Now we make sure we havent forgotten any values.
            // If so, we sort them and add them at the end.
            if (count($values) > 0) {
                // First sort the values first.
                ksort($values);

                foreach ($values as $unsortedkey => $unsortedvalue) {
                    $sortedarray['other'][$unsortedkey] = true;
                }
            }

            // Finally, we pass the sorted array to the values back.
            $values = $sortedarray;
        } else {
            $values = array_combine(array_keys($values), array_keys($values));
        }
        $index = 1;
        foreach ($values as $subcategorykey => $subcategoryarray) {
            $categorycount = 0;
            foreach ($subcategoryarray as $valuekey => $valuevalue) {

                // For custom fields, we get the actual string value from field controller.
                $fieldcontroller = wbt_field_controller_info::get_instance_by_shortname($fckey);
                if (!empty($fieldcontroller)) {
                    $cfstringvalueforvaluekey = $fieldcontroller->get_option_value_by_key($valuekey);
                    if ($cfstringvalueforvaluekey == wbt_field_controller_info::WBTABLE_CUSTOMFIELD_VALUE_NOTFOUND) {
                        continue;
                    }
                }

                $itemobject = [
                    // We do not want to show HTML tags or HTML entities, so replace &amp; with &.
                    'key' => strip_tags(str_replace("&amp;", "&", $cfstringvalueforvaluekey ?? $valuekey)),
                    'value' => $valuevalue === true ? $valuekey : $valuevalue,
                    'category' => $fckey,
                ];

                // Count may not be used, so we have an extra check.
                if (!empty($valueswithcount[$itemobject['value']])) {
                    $itemobject['count'] = $valueswithcount[$itemobject['value']] ?? false;

                    if ($itemobject['count']) {
                        $categorycount += (int)$itemobject['count'] ?? 0;
                    }
                }

                $categoryobject['hierarchy'][$subcategorykey]['values'][$valuekey] = $itemobject;
            }

            if (
                !isset($categoryobject['hierarchy'][$subcategorykey])
                || count($categoryobject['hierarchy'][$subcategorykey]['values']) == 0
            ) {
                // We don't add the filter if there is nothing in there.
                return [];
            }

            if ($sortarray == null) {
                // If we didn't sort otherwise, we do it now.
                ksort($categoryobject['hierarchy'][$subcategorykey]['values']);
            }

            // Make the arrays mustache ready, we have to jump through loops.
            $categoryobject['hierarchy'][$subcategorykey]['values'] =
            array_values($categoryobject['hierarchy'][$subcategorykey]['values']);
            $categoryobject['hierarchy'][$subcategorykey]['label'] = $subcategorykey;
            $categoryobject['hierarchy'][$subcategorykey]['id'] = 'subcategorykey_' . $subcategorykey;
            $categoryobject['hierarchy'][$subcategorykey]['index'] = $index;
            $categoryobject['hierarchy'][$subcategorykey]['count'] = $categorycount;
            $index++;
        }

        // Make the arrays mustache ready, we have to jump through loops.
        $categoryobject['hierarchy'] = array_values($categoryobject['hierarchy']);
    }

    /**
     * Apply the filter of hierachical class. Logic of Standardfilter can be applied here.
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

        $standardfilter = new standardfilter($columnname);
        $standardfilter->apply_filter($filter, $columnname, $categoryvalue, $table);
    }
}
