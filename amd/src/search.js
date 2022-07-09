
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
var allElements = [];
var elementToHideSelector = '';
var listContainerSelector = '';
var elementToSearchSelector = '';

var idstring = '';
var encodedtable = '';

/**
 * Store some params globally.
 * @param {string} listContainer
 * @param {string} elementToHide
 * @param {string} elementToSearch
 */
export const init = (listContainer, elementToHide, elementToSearch) => {

    // eslint-disable-next-line no-console
    console.log(listContainer, "elementToHide: ", elementToHide, "elementToSearch:", elementToSearch);

    elementToHideSelector = elementToHide;
    listContainerSelector = listContainer;
    elementToSearchSelector = elementToSearch;

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
    filterobjects);

    setVisibility();
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
   * Compares checked boxes with classes of Elements and shows or hides them.
   */
  export const setVisibility = () => {
    allElements.forEach(function(el) {
      let display = true;
      categories.forEach(function(c) {

        let intersection = checked[c].length
          ? Array.from(Object.values(el.dataset)).filter((x) =>
              checked[c].includes(x)
            ).length
          : true;
        if (!intersection) {
          display = false;
          return;
        }
      });
      if (display) {
        el.style.display = "block";
      } else {
        el.style.display = "none";
      }
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
      inputElement.addEventListener('keyup', () => {

        searchInput(inputElement, elementToHideSelector, elementToSearchSelector);

        inputElement.dataset.initialized = true;
      });
    }
}

/**
 * Returns json of active filters as json.
 * @returns string
 * @returns string
 */
export function getFilterOjects() {

  return JSON.stringify(checked);
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

    if (filterContainer.dataset.initialized) {
      return;
    }

    const allCheckboxes = filterContainer.querySelectorAll("input[type=checkbox]");

    // Error gets spinner
    allElements = document.querySelectorAll(selector + " " + elementToHideSelector);

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
