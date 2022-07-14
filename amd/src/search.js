
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

import Templates from 'core/templates';
import {callLoadData} from 'local_wunderbyte_table/init';

var checked = {};
var categories = [];


var listContainerSelector = '';

var idstring = '';
var encodedtable = '';

var lastsearchinput = 0;

var loading = false;

/**
 * Store some params globally.
 * @param {string} listContainer
 */
export const init = (listContainer) => {

    // eslint-disable-next-line no-console
    console.log("listContainer", listContainer);

    listContainerSelector = listContainer;

    initializeSearch();

};

export const searchInput = (inputElement, elementToHide, elementToSearch) => {
    let filter, li, a, i, txtValue;

    filter = inputElement.value.toUpperCase();

    li = document.querySelectorAll(elementToHide);

    for (i = 0; i < li.length; i++) {
        if (elementToSearch) {
            a = li[i].querySelector(elementToSearch);
        } else {
            a = li[i];
        }

        txtValue = a.textContent || a.innerText;

        if (txtValue.toUpperCase().indexOf(filter) > -1) {
            li[i].style.display = "";
        } else {
            li[i].style.display = "none";
        }
    }
};


/**
 * Eventhandler
 *
 * @param {*} e - event
 */
 export const toggleCheckbox = (e) => {

    // eslint-disable-next-line no-console
    console.log('checked', e.target.name);
    getChecked(e.target.name);

    // Reload the filtered elements via ajax.

    const filterobjects = getFilterOjects();
    const searchstring = getSearchInput();

    // eslint-disable-next-line no-console
    console.log('reload from filter ', filterobjects);

    callLoadData(idstring,
    encodedtable,
    0,
    null,
    null,
    null,
    null,
    null,
    filterobjects,
    searchstring);

  };

  /**
   * Check which Checkboxes are selected inside a group.
   *
   * @param {*} name of Element group
   */
  export const getChecked = (name) => {
    checked[name] = Array.from(
      document.querySelectorAll("input[name=" + name + "]:checked")
    ).map(function(el) {
      return el.value;
    });
  };

  /**
   * Render the checkboxes for the filer.
   * @param {string} filterjson
   * @param {string} idstringvar
   * @param {string} encodedtable
   */
  export const renderFilter = (filterjson, idstringvar, encodedtable) => {

    // We render the filter only once, so if we find it already, we don't render it.

    // eslint-disable-next-line no-console
    console.log(idstringvar);

    idstring = idstringvar;

    const selector = ".wunderbyte_table_container_" + idstring;
    const container = document.querySelector(selector);
    const filtercontainer = container.querySelector(".wunderbyteTableFilter");

    if (filtercontainer) {
      return;
    }

    Templates.renderForPromise('local_wunderbyte_table/filter', filterjson).then(({html}) => {

        // eslint-disable-next-line no-console
        console.log("encodedtable: ", encodedtable);

        container.insertAdjacentHTML('afterbegin', html);

        initializeCheckboxes(selector, idstring, encodedtable);

        return;
    }).catch(e => {
        // eslint-disable-next-line no-console
        console.log(e);
    });
};

/**
   * Render the checkboxes for the filer.
   * @param {string} idstring
   */
 export const renderSearchbox = (idstring) => {

    const selector = ".wunderbyte_table_container_" + idstring;
    const container = document.querySelector(selector);
    const searchcontainer = container.querySelector("input.search");

    if (searchcontainer) {
      return;
    }

    Templates.renderForPromise('local_wunderbyte_table/search', []).then(({html}) => {

        container.insertAdjacentHTML('afterbegin', html);

        initializeSearch(selector);

        return;
    }).catch(e => {
        // eslint-disable-next-line no-console
        console.log(e);
    });
};


/**
 * Function to initialize the search after rendering the searchbox.
 */
 export function initializeSearch() {

    const inputElement = document.querySelector(listContainerSelector + ' input.search');

    if (!inputElement) {
        return;
    }

    if (!inputElement.dataset.initialized) {

      inputElement.dataset.initialized = true;

      inputElement.addEventListener('keyup', () => {

        let now = Date.now();

        lastsearchinput = now;

        setTimeout(() => {

          const searchstring = getSearchInput();

          // If the timevalue after the wait is the same as before, we didn't have another input.
          // we want to make sure we do no loading while we are waiting for the answer.
          // And the iput string must be longr than 3.
          if (lastsearchinput === now
              && loading == false
              && searchstring !== null) {

            const filterobjects = getFilterOjects();

            callLoadData(idstring,
              encodedtable,
              0, // We set page to 0 because we need to start the container anew.
              null,
              null,
              null,
              null,
              null,
              filterobjects,
              searchstring);

          }
        }, 400);

        return;
      });
    }
}

/**
 * Returns json of active filters as json.
 * @returns {string}
 */
export function getFilterOjects() {

  return JSON.stringify(checked);
}

/**
 * Function to read the searchstring from the input leement.
 * @returns {null|string}
 */
export function getSearchInput() {

  const inputElement = document.querySelector(listContainerSelector + ' input.search');

  if (!inputElement) {
    return null;
  }

    let searchstring = null;

    if (inputElement.value.length > 3
        || inputElement.value.length === 0) {
      searchstring = inputElement.value;
    }

  return searchstring;
}

/**
 * Initialize Checkboxes.
 * @param {string} selector
 * @param {string} idstringvar
 * @param {string} encodedtablevar
 */
export function initializeCheckboxes(selector, idstringvar, encodedtablevar) {

    encodedtable = encodedtablevar;
    idstring = idstringvar;

    const filterContainer = document.querySelector(selector + " .wunderbyteTableFilter");

    if (!filterContainer || filterContainer.dataset.initialized) {
      return;
    }

    const allCheckboxes = filterContainer.querySelectorAll("input[type=checkbox]");

    if (!allCheckboxes) {
        return;
    }

    filterContainer.querySelectorAll(".form-group").forEach(e => {
        categories.push(e.getAttribute("name"));
        getChecked(e.getAttribute("name"));
    });

    allCheckboxes.forEach(el => {
        el.addEventListener("change", toggleCheckbox);
    });

    filterContainer.dataset.initialized = true;
}
