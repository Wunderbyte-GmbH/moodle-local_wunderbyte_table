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

/**
 * Render the checkboxes for the filer.
 * @param {string} idstring
 */
export const renderSearchbox = (idstring) => {

    const selector = ".wunderbyte_table_container_" + idstring;
    const tablecontainer = document.querySelector(selector);
    if (!tablecontainer) {
        return;
    }
    const filtercontainer = tablecontainer.querySelector(".wunderbyteTableFilter");
    if (!filtercontainer) {
        return;
    }
    let searchfields = filtercontainer.querySelectorAll("input.search");

    // eslint-disable-next-line no-console
    console.log("filtersearch init", searchfields);

    if (!searchfields) {
        return;
    }
};

/**
 * Function to initialize the search after rendering each searchfield.
 * @param {*} containerselector
 */
export function initializeFilterSearch(containerselector) {

    const tablecontainer = document.querySelector(containerselector);
    if (!tablecontainer) {
        return;
    }
    const filtercontainer = tablecontainer.querySelector(".wunderbyteTableFilter");

    if (!filtercontainer) {
        return;
    }
    var inputElements = filtercontainer.querySelectorAll('input.search');

    if (!inputElements) {
        return;
    }

    inputElements.forEach(function(inputElement) {
        if (!inputElement.dataset.initialized) {
            inputElement.dataset.initialized = true;

            // Get all records of filter.
            const parentElement = inputElement.parentNode;
            let records = parentElement.querySelectorAll('input.filterelement.form-check-input[type="checkbox"]');

            // Display searchfield with minimum of 13 records.
            if (records.length > 0) {
                inputElement.removeAttribute('hidden');
            }

            inputElement.addEventListener('keyup', () => {

                let searchstring = null;
                let match = false;
                if (inputElement.value.length > 1
                    || inputElement.value.length === 0) {
                    searchstring = inputElement.value;
                }

                if (inputElement.value.length === 0 && inputElement.nextElementSibling.dataset.moodletype == 'hierarchylist') {
                    match = false;
                }

                // Check if value of records contains searchstring.
                // If contained, display it, else hide.
                records.forEach(function(record) {
                    let value = record.dataset.key.toLowerCase();
                    const listelement = record.parentNode;
                    if (value.includes(searchstring.toLowerCase())) {
                        match = true;
                        listelement.removeAttribute('hidden');
                    } else {
                        listelement.setAttribute('hidden', '');
                        // For hierarchy filter. Only do once for current parent.
                    }
                    if (match === true) {
                        listelement.parentNode.parentNode.classList.add('show');
                    } else {
                        listelement.parentNode.parentNode.classList.remove('show');
                    }
                });
                return;
            });
        }
    }
    );
}
