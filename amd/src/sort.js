
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

import {callLoadData} from 'local_wunderbyte_table/init';
import {getFilterOjects} from 'local_wunderbyte_table/filter';
import {getSearchInput} from 'local_wunderbyte_table/search';

/**
 * Function to initialize the search after rendering the searchbox.
 * @param {*} listContainer
 * @param {*} idstring
 * @param {*} encodedtable
 * @returns {void}
 */
 export function initializeSort(listContainer, idstring, encodedtable) {

  // eslint-disable-next-line no-console
    console.log("soinitializeSortrt called ", listContainer);

    const container = document.querySelector(listContainer);

    const sortColumnElement = container.querySelector('select.sortcolumn');
    const sortOrderElement = container.querySelector('a.changesortorder');

    if (!sortColumnElement || !sortOrderElement) {

        // eslint-disable-next-line no-console
        console.log('abort ', sortColumnElement, sortOrderElement.className);

        return;
    }

    if (!sortColumnElement.dataset.initialized || !sortOrderElement.dataset.initialized) {

      sortColumnElement.dataset.initialized = true;
      sortOrderElement.dataset.initialized = true;

      // We add to listener, on on the select, one on the sortorder button.

      sortColumnElement.addEventListener('change', (e) => {
        callSortAjax(e, idstring, encodedtable);
      });
      sortOrderElement.addEventListener('click', () => {
        callSortAjax(null, idstring, encodedtable);
      });
    }
}

/**
 * Execture the two possible sort Ajax calls as reaction on the triggered event.
 * @param {*} event
 * @param {*} idstring
 * @param {*} encodedtable
 */
function callSortAjax(event, idstring, encodedtable) {

  // eslint-disable-next-line no-console
  console.log('callSortAjax was called');

  let sortcolumn = null;
  let sortorder = null;
  let reset = null;

  // If we get an event, we are in the sortcolum mode.
  if (event !== null) {
    // eslint-disable-next-line no-console
    console.log("sort called ", event.target.value);

    sortcolumn = event.target.value;
    // We reset only on changed sortcolumn, not on order.
    reset = 1;

    if (!sortcolumn) {
      return;
    }
  } else {
    // Else, we are in the sortorder mode.
    // 3 is ASC, 4 is DESC. We have to find out which is the current mode.

    const container = document.querySelector(".wunderbyte_table_container_" + idstring);

    const sortColumnElement = container.querySelector('select.sortcolumn');

    // eslint-disable-next-line no-console
    console.log("classname ", container.querySelector("a.changesortorder").className);

    let className = container.querySelector("a.changesortorder i").className;

    // Get the sortorder by the icon and change it.
    if (className.includes('asc')) {

      // eslint-disable-next-line no-console
      console.log("change order from A to z", className);

      sortorder = 3;
      container.querySelector("a.changesortorder i").className = className.replace('asc', 'desc');
    } else {
      // eslint-disable-next-line no-console
      console.log("change order from Z to A", className);

      sortorder = 4;
      container.querySelector("a.changesortorder i").className = className.replace('desc', 'asc');
    }

    // We also need the sortcolumn name to effectuate the change.
    sortcolumn = sortColumnElement.selectedOptions[0].value;
  }

   // eslint-disable-next-line no-console
   console.log("we found the sortcolum ", sortcolumn, sortorder);

   const filterobjects = getFilterOjects(idstring);
   const searchstring = getSearchInput(idstring);


   callLoadData(idstring,
     encodedtable,
     0, // We set page to 0 because we need to start the container anew.
     sortcolumn,
     null,
     null,
     sortorder,
     reset,
     filterobjects,
     searchstring);
}

/**
 * Function to read the searchstring from the input leement.
 * @param {*} idstring
 * @returns {null|string}
 */
export function getSortSelection(idstring) {

  const inputElement = document.querySelector(".wunderbyte_table_container_" + idstring + ' select.sort');

  if (!inputElement) {
    return null;
  }

  return '';
}
