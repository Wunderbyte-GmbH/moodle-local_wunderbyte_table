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
 * @package    local_wunderbyte_table
 * @copyright  Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

const SELECTOR = {
    ROWSELECT: 'select.rowsperpage',
};

// eslint-disable-next-line no-unused-vars
const NUMBEROFROWS = 10;
/**
 * Function getting value of select to choose how many rows will be displayed in table.
 * @param {string} selector
 * @returns {void};
 */
export function initializeRowsSelect(selector) {

        const container = document.querySelector(selector);
        const numberOfRowsSelect = container.querySelector(SELECTOR.ROWSELECT);

        numberOfRowsSelect.addEventListener('change', () => {
            const selectedValue = numberOfRowsSelect.value;
            // eslint-disable-next-line no-console
            console.log(selectedValue);
            this.NUMBEROFROWS = selectedValue;
        });
}


