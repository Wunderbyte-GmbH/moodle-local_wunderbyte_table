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

/*
 * @package    local_shopping_cart
 * @copyright  Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Ajax from 'core/ajax';

/** @param {string} encodedtable */
export const init = (encodedtable) => {
    const observeDOMChanges = (callback) => {
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                if (mutation.addedNodes) {
                    mutation.addedNodes.forEach((node) => {
                        if (node instanceof HTMLElement && node.querySelector('[name="filter_columns"]')) {
                            callback(node.querySelector('[name="filter_columns"]'));
                        }
                    });
                }
            });
        });
        observer.observe(document.body, {childList: true, subtree: true});
    };
    observeDOMChanges((dropdown) => {
        dropdown.addEventListener('change', (event) => {
            const selectedValue = event.target.value;
            // eslint-disable-next-line no-console
            console.log('inside', selectedValue);
            Ajax.call([{
                methodname: 'local_wunderbyte_table_get_filter_column_data',
                args: {
                    filtercolumn: selectedValue,
                    encodedtable: encodedtable
                },
                done: (response) => {
                    const placeholder = document.getElementById('filter-edit-fields');
                    if (placeholder) {
                        placeholder.innerHTML = response.html;
                    }
                },
                fail: (error) => {
                    // eslint-disable-next-line no-console
                    console.error('Web service error:', error);
                }
            }]);
        });
    });
};