
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
import {callLoadData, queries, infinitescrollEnabled} from 'local_wunderbyte_table/init';
import {getFilterObjects} from 'local_wunderbyte_table/filter';
import {getSearchInput} from 'local_wunderbyte_table/search';
import {getSortSelection} from 'local_wunderbyte_table/sort';

/**
 * Function to initialize the search after rendering the searchbox.
 * @param {*} selector
 * @param {*} idstring
 * @param {*} encodedtable
 * @returns {void}
 */
export function initializeReload(selector, idstring, encodedtable) {

    const button = document.querySelector(selector + " .wb_reload_button");

    if (!button) {
        return;
    }

    const idstringplusa = 'a' + idstring;

    button.addEventListener('click', () => {

        wbTableReload(idstringplusa, encodedtable);
    });
}

/**
 * Function to reload a wunderbyte table from js.
 * Here we trim the idstring before we pass it to the calldatafunction.
 * @param {*} idstringplusa
 * @param {*} encodedtable
 * @param {number} rowid
 */
export function wbTableReload(idstringplusa, encodedtable, rowid = 0) {

    // We need to trim the first character. We use the a to make sure no number is in first place due to random generation.
    const idstring = idstringplusa.substring(1);

    let filterobjects = getFilterObjects(idstring);

    // If we have a rowid, we add the rowid to the filter.
    if (rowid > 0) {

        let filterobject = {};

        if (filterobjects.length !== 0) {
            filterobject = JSON.parse(filterobjects);
        }

        filterobject.id = [rowid];
        filterobjects = JSON.stringify(filterobject);
    }

    const replacerow = rowid > 0 ? true : false;

    const searchstring = getSearchInput(idstring);
    const sort = getSortSelection(idstring);

    callLoadData(idstring,
        encodedtable,
        0, // Pagenumber is always rest to 0.
        null,
        sort,
        null,
        null,
        null,
        filterobjects,
        searchstring,
        replacerow);
}

/**
 * This function can be called from a button. The button identifies the table and the id and calls reload.
 * @param {HTMLElement} element
 */
export function wbTableRowReload(element) {

    let parentelement = element;
    let rowid = null;

    // We run through the parents until we have the table class.
    while (!parentelement.classList.contains('wunderbyteTableClass')) {
        // We only want the first id, so we check if we have found an id already.
        if (!rowid && parentelement.dataset.id) {
            rowid = parentelement.dataset.id;
        }
        parentelement = parentelement.parentElement;

        if (!parentelement) {
            break;
        }
    }
    // Only if we have found a parent element, we call reload.
    if (parentelement) {
        const idstring = parentelement.getAttribute('id');
        const encodedtable = parentelement.dataset.encodedtable;

        wbTableReload(idstring, encodedtable, rowid);
    }

}

/**
 * Reload all other tables on the same page.
 *
 * @param {null|bool} scrollToTabletop
 */
export function reloadAllTables(scrollToTabletop = true) {

    // eslint-disable-next-line no-unused-vars
    for (const [key, value] of Object.entries(queries)) {

        // When we have infinite scroll, we need to all the currently shown pages.
        if (infinitescrollEnabled(value.idstring)) {
            let counter = 0;
            while (counter <= value.page) {
                callLoadData(
                    value.idstring,
                    value.encodedtable,
                    counter,
                    value.tsort,
                    value.thide,
                    value.tshow,
                    value.tdir,
                    value.treset,
                    value.filterobjects,
                    value.searchtext,
                    value.replacerow,
                    null,
                    scrollToTabletop
                );
                counter++;
            }

        } else {
            callLoadData(
                value.idstring,
                value.encodedtable,
                value.page,
                value.tsort,
                value.thide,
                value.tshow,
                value.tdir,
                value.treset,
                value.filterobjects,
                value.searchtext,
                value.replacerow,
                null,
                scrollToTabletop
            );
        }
    }
}