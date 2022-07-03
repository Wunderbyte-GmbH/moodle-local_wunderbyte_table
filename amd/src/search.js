
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
    console.log(filterobjects);

    callLoadData(idstring,
    encodedtable,
    null,
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
   * @param {string} idstring
   */
  export const renderFilter = (filterjson, idstringvar) => {

    Templates.renderForPromise('local_wunderbyte_table/filter', filterjson).then(({html}) => {

      idstring = idstringvar;

        const selector = ".wunderbyte_table_container_" + idstring;

        const container = document.querySelector(selector);

        encodedtable = container.querySelector('#a' + idstring).dataset.encodedtable;

        container.insertAdjacentHTML('afterbegin', html);

        initializeCheckboxes(selector);

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

    Templates.renderForPromise('local_wunderbyte_table/search', []).then(({html}) => {


        const selector = ".wunderbyte_table_container_" + idstring;

        const container = document.querySelector(selector);

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
 function initializeSearch() {

    const inputElement = document.querySelector(listContainerSelector + ' input.search');

    if (!inputElement) {
        return;
    }

    inputElement.addEventListener('keyup', () => {

        searchInput(inputElement, elementToHideSelector, elementToSearchSelector);
    });
}

/**
 * Returns json of active filters as json.
 * @returns string
 */
function getFilterOjects() {

  return JSON.stringify(checked);
}

/**
 * Initialize Checkboxes.
 * @param {string} selector
 * @param {string} idstring
 * @param {string} encodedtable
 */
function initializeCheckboxes(selector) {

    const listContainer = document.querySelector(selector);

    const allCheckboxes = listContainer.querySelectorAll("input[type=checkbox]");

    // Error gets spinner
    allElements = document.querySelectorAll(selector + " " + elementToHideSelector);

    if (!allCheckboxes) {
        return;
    }

    listContainer.querySelectorAll(".form-group").forEach(e => {
        categories.push(e.getAttribute("name"));
        getChecked(e.getAttribute("name"));
    });

    allCheckboxes.forEach(el => {
        el.addEventListener("change", toggleCheckbox);
    });
}
