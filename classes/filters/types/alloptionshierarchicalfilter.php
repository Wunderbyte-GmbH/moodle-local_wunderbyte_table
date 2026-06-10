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

namespace local_wunderbyte_table\filters\types;

use local_wunderbyte_table\wunderbyte_table;
use local_wunderbyte_table\local\customfield\wbt_field_controller_info;
use local_wunderbyte_table\filters\base;

/**
 * Hierarchical filter that displays ALL statically-defined options,
 * including those with zero entries in the database.
 *
 * This is useful for cases where you want to show all available categories
 * (e.g., competencies from a custom field definition) even if no records
 * currently match some categories.
 */
class alloptionshierarchicalfilter extends hierarchicalfilter {
    /**
     * Get data for filter options. Override to handle the case where no DB records exist.
     *
     * If the parent method returns ['continue' => true] (no DB records found),
     * we return an empty array instead. This signals to the filter creation logic
     * that we want to build the filter from static options rather than skip it entirely.
     *
     * @param wunderbyte_table $table
     * @param string $key
     * @return array
     */
    public static function get_data_for_filter_options(wunderbyte_table $table, string $key) {
        $result = parent::get_data_for_filter_options($table, $key);

        // If parent returns ['continue' => true], it means no DB records found.
        // We want to show the filter anyway with static options, so return empty array.
        if (isset($result['continue']) && $result['continue'] === true) {
            return [];
        }

        return $result;
    }

    /**
     * Adds the array for the mustache template to render the categoryobject.
     *
     * This override ensures that ALL options defined in $filtersettings are displayed,
     * even if they have zero matching records in the database.
     *
     * @param array $categoryobject
     * @param array $filtersettings
     * @param string $fckey
     * @param array $values
     * @return void
     */
    public static function add_to_categoryobject(array &$categoryobject, array $filtersettings, string $fckey, array $values) {
        // Don't treat this filter if there are no values here.
        if (!is_array($values)) {
            $values = [];
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
        $sortarray = isset($filtersettings[$fckey]) && (count($filtersettings[$fckey]) > 0) ? $filtersettings[$fckey] : null;

        // First we create our sortedarray and add all values in the right order.
        if ($sortarray != null) {
            $sortedarray = [];
            // MODIFICATION: Iterate through ALL options in sortarray, not just those with DB results.
            foreach ($sortarray as $sortkey => $sortvalue) {
                // Skip non-array entries — these are filter metadata (wbfilterclass, explode, etc.).
                if (!is_array($sortvalue)) {
                    continue;
                }

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

                // Remove from values if it was present (we've now handled it).
                if (isset($values[$sortkey])) {
                    unset($values[$sortkey]);
                }
            }

            // Now we make sure we haven't forgotten any values.
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
                $fieldcontroller = wbt_field_controller_info::get_instance_by_shortname(
                    $fckey,
                    $filtersettings['_customfieldcomponent'] ?? '',
                    $filtersettings['_customfieldarea'] ?? ''
                );
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

                // Only add count if this item was actually in the original DB results.
                if (isset($valueswithcount[$itemobject['value']]) && $valueswithcount[$itemobject['value']]) {
                    $itemobject['count'] = $valueswithcount[$itemobject['value']];

                    if ($itemobject['count']) {
                        $categorycount += (int)$itemobject['count'] ?? 0;
                    }
                } else {
                    // Explicitly set count to 0 for items with no DB records.
                    // Without this key, Mustache walks up the context stack and inherits
                    // the parent subcategory count, showing a wrong "N Veranstaltungen" label.
                    $itemobject['count'] = 0;
                }

                $categoryobject['hierarchy'][$subcategorykey]['values'][$valuekey] = $itemobject;
            }

            // MODIFICATION: Don't skip subcategories with 0 values - still add them.
            if (
                !isset($categoryobject['hierarchy'][$subcategorykey])
                || count($categoryobject['hierarchy'][$subcategorykey]['values']) == 0
            ) {
                // Still continue to next subcategory instead of bailing out.
                continue;
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
}
