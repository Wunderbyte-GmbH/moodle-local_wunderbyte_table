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

use local_wunderbyte_table\filter;
use local_wunderbyte_table\filters\base;

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

        // We might need to explode values, because of a multi-field.
        if (isset($filtersettings[$fckey]['explode'])
            || filter::check_if_multi_customfield($fckey)) {

            // We run through the array of values and explode each item.
            foreach ($values as $keytoexplode => $valuetoexplode) {

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
                    }
                    // We make sure the strings with more than one values are not treated anymore.
                    unset($values[$keytoexplode]);
                }
            }

            unset($filtersettings[$fckey]['explode']);
        }

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
        if (isset($filtersettings[$fckey])
                    && count($filtersettings[$fckey]) > 0) {

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

        foreach ($values as $subcategorykey => $subcategoryarray) {

            foreach ($subcategoryarray as $valuekey => $valuevalue) {

                $itemobject = [
                    // We do not want to show html entities, so replace &amp; with &.
                    'key' => str_replace("&amp;", "&", $valuekey),
                    'value' => $valuevalue === true ? $valuekey : $valuevalue,
                    'category' => $fckey,
                ];

                // Count may not be used, so we have an extra check.
                if (!empty($filtercolumns[$fckey][$valuevalue])) {
                    $itemobject['count'] = $filtercolumns[$fckey][$valuevalue];
                }

                $categoryobject['hierarchy'][$subcategorykey]['values'][$valuekey] = $itemobject;
            }

            if (!isset($categoryobject['hierarchy'][$subcategorykey])
             || count($categoryobject['hierarchy'][$subcategorykey]['values']) == 0) {
                // We don't add the filter if there is nothing in there.
                return;
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

        }

        // Make the arrays mustache ready, we have to jump through loops.
        $categoryobject['hierarchy'] = array_values($categoryobject['hierarchy']);
    }

}
