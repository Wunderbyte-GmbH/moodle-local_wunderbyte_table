
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

var checked = {};
var categories = [];
var allElements = [];

export const init = (listContainerSelector, elementToHide, elementToSearch) => {

    // eslint-disable-next-line no-console
    console.log(listContainerSelector, elementToHide, elementToSearch);

    const inputElement = document.querySelector(listContainerSelector + ' input.search');

    if (!inputElement) {
        return;
    }

    inputElement.addEventListener('keyup', () => {

        searchInput(inputElement, elementToHide, elementToSearch);
    });

    const listContainer = document.querySelector(listContainerSelector);

    // eslint-disable-next-line no-console
    console.log('listContainer ', listContainer);

    const allCheckboxes = listContainer.querySelector("input[type=checkbox]");

    if (!allCheckboxes) {
        return;
    }
};

/**
 * Initialize Checkboxes.
 * @param {string} selector
 * @returns
 */
function initializeCheckboxes(selector) {

    // eslint-disable-next-line no-console
    console.log('initializeCheckboxes ');

    const listContainer = document.querySelector(selector);

    const allCheckboxes = listContainer.querySelectorAll("input[type=checkbox]");

    if (!allCheckboxes) {
        return;
    }

    // eslint-disable-next-line no-console
    console.log('allCheckboxes ', allCheckboxes);

    listContainer.querySelectorAll(".form-group").forEach(e => {
        categories.push(e.getAttribute("name"));
        getChecked(e.getAttribute("name"));
    });

    allCheckboxes.forEach(el => {
        el.addEventListener("change", toggleCheckbox);
    });


}

export const searchInput = (inputElement, elementToHide, elementToSearch) => {
    let filter, li, a, i, txtValue;

    filter = inputElement.value.toUpperCase();

    // eslint-disable-next-line no-console
    console.log(filter);

    li = document.querySelectorAll(elementToHide);

    // eslint-disable-next-line no-console
    console.log(li);

    for (i = 0; i < li.length; i++) {
        if (elementToSearch) {
            a = li[i].querySelector(elementToSearch);
        } else {
            a = li[i];
        }

        txtValue = a.textContent || a.innerText;

        // eslint-disable-next-line no-console
        console.log(txtValue);

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
    getChecked(e.target.name);
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

    // eslint-disable-next-line no-console
    console.log(categories);
    allElements.forEach(function(el) {
      let display = true;
      categories.forEach(function(c) {

        // eslint-disable-next-line no-console
        console.log(el.classList);
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
  export const renderFilter = (filterjson, idstring) => {

    // eslint-disable-next-line no-console
    console.log('filterjson', filterjson);

    Templates.renderForPromise('local_wunderbyte_table/filter', filterjson).then(({html}) => {

        // eslint-disable-next-line no-console
        console.log('renderFilter', html);
        const selector = ".wunderbyte_table_container_" + idstring;

        // eslint-disable-next-line no-console
        console.log('selector', selector);

        const container = document.querySelector(selector);

        // eslint-disable-next-line no-console
        console.log(container);

        container.insertAdjacentHTML('afterbegin', html);

        initializeCheckboxes(selector);

        return;
    }).catch(e => {
        // eslint-disable-next-line no-console
        console.log(e);
    });
};
