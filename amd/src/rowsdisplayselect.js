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
 * @module    local_wunderbyte_table
 * @copyright  Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import {transmitAction} from "local_wunderbyte_table/actionbutton";

const SELECTOR = {
    ROWSELECT: 'select.rowsperpage',
};

/**
 * Function getting value of select to choose how many rows will be displayed in table.
 * @param {string} selector
 * @param {string} idstring
 * @param {string} encodedtable
 * @returns {void};
 */
export function initializeRowsSelect(selector, idstring, encodedtable) {
    const container = document.querySelector(selector);
    if (!container) {
        return;
    }
    const selectElements = container.querySelectorAll(SELECTOR.ROWSELECT);

    selectElements.forEach(selectElement => {
        if (!selectElement.dataset.initialized) {
            selectElement.dataset.initialized = true;
            selectElement.addEventListener('change', () => {
                const selectedvalue = selectElement.value;
                const data = {
                    "numberofrowsselect": selectedvalue,
                };
                transmitAction(0, 'rownumberperpage', JSON.stringify(data), idstring, encodedtable);
            });
        }
    });
}
