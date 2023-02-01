
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

import ModalFactory from 'core/modal_factory';
import ModalEvents from 'core/modal_events';
import Ajax from 'core/ajax';
import {showNotification} from 'local_wunderbyte_table/notifications';
import {wbTableReload} from 'local_wunderbyte_table/reload';
import {get_strings} from 'core/str';

const SELECTOR = {
  ACTIONBUTTON: '.wb_action_button',
  CHECKBOX: 'input.wb-checkbox',
};

/**
 * Function to add click listener to acton button.
 * @param {string} selector
 * @param {string} idstring
 * @param {string} encodedtable
 * @returns {void}
 */
 export function initializeActionButton(selector, idstring, encodedtable) {

      const container = document.querySelector(selector);
      const actionbuttons = container.querySelectorAll(SELECTOR.ACTIONBUTTON);

      // eslint-disable-next-line no-console
      console.log('initializeActionButton', container, actionbuttons);

      actionbuttons.forEach(button => {
          if (button.dataset.initialized) {
            return;
          }

          button.dataset.initialized = true;

          button.addEventListener('click', e => {

            const target = e.target;

            // eslint-disable-next-line no-console
            console.log('transmit data', target);

            showConfirmationModal(button, 'title', 'body', 'button', idstring, encodedtable);
          });
      });
}

/**
 * Shows generic confirmation modal.
 * @param {*} button
 * @param {string} titleText
 * @param {string} bodyText
 * @param {string} saveButtonText
 * @param {string} idstring
 * @param {string} encodedtable
 */
async function showConfirmationModal(button, titleText, bodyText, saveButtonText, idstring, encodedtable) {

  const id = button.dataset.id;
  const methodname = button.dataset.methodname;
  const data = button.dataset; // Get all the data of the clicked button.

  var checkedids = [];
  const labelarray = [];

  // If the id is 0, we return for all checked checkboxes.
  // if not, just for the current one.
  if (id < 1) {
    const container = document.querySelector('#a' + idstring);
    const checkboxes = container.querySelectorAll(SELECTOR.CHECKBOX);
    // eslint-disable-next-line no-console
    console.log(SELECTOR.CHECKBOX, checkboxes);

    // Create an array of ids of the checked boxes.
    checkboxes.forEach(x => {
        if (x.checked) {

          // If the key labelcolumn is defined, we use this.
          if (data.labelcolumn) {

            const name = container.querySelector('[data-id="' + x.id + '"] [data-label="' + data.labelcolumn + '"]').textContent;
            labelarray.push(name);
          } else {
            labelarray.push(x.id);
          }
          checkedids.push(x.id);
      }
    });

    data.checkedids = checkedids;
  } else {
    checkedids = [id];
  }

  const datastring = labelarray.join('<br>') ?? '';

  // eslint-disable-next-line no-console
  console.log(datastring, checkedids);

  let strings = [
    {
      key: button.dataset.titlestring ?? 'generictitle',
      component: button.dataset.component ?? 'local_wunderbyte_table',
    },
    {
      key: button.dataset.bodystring ?? 'genericbody',
      component: button.dataset.component ?? 'local_wunderbyte_table',
      param: {
        // eslint-disable-next-line block-scoped-var
        data: datastring,
      }
    },
    {
      key: button.dataset.submitbuttonstring ?? 'genericsubmit',
      component: button.dataset.component ?? 'local_wunderbyte_table',
    },
  ];

  // eslint-disable-next-line no-console
  console.log(strings);

  const localizedstrings = await get_strings(strings);

  // eslint-disable-next-line no-console
  console.log(localizedstrings);

  ModalFactory.create({type: ModalFactory.types.SAVE_CANCEL}).then(modal => {

    modal.setTitle(localizedstrings[0]);
        modal.setBody(localizedstrings[1]);
        modal.setSaveButtonText(localizedstrings[2]);
        modal.getRoot().on(ModalEvents.save, function() {

            // If there is only one id, we transmit one call.
            if (id != 0) {
              transmitAction(id, methodname, JSON.stringify(data), idstring, encodedtable);
            } else { // Zero means we want single line execution.
              // eslint-disable-next-line block-scoped-var
              checkedids.forEach(cid => {
                transmitAction(cid, methodname, JSON.stringify(data), idstring, encodedtable);
              });
            }
        });

        modal.show();
        return modal;
  }).catch(e => {
      // eslint-disable-next-line no-console
      console.log(e);
  });
}

/**
 * Ajax function to handle action buttons.
 * @param {int} id
 * @param {string} methodname
 * @param {string} datastring
 * @param {string} idstring
 * @param {string} encodedtable
 */
function transmitAction(id, methodname, datastring, idstring, encodedtable) {
  Ajax.call([{
    methodname: "local_wunderbyte_table_execute_action",
    args: {
        'id': parseInt(id),
        'methodname': methodname,
        'data': datastring,
        'encodedtable': encodedtable,
    },
    done: function(data) {

        if (data.success == 1) {
          // eslint-disable-next-line no-console
          console.log('success');
          showNotification(data.message, "success");
        } else {
          showNotification(data.message, "danger");
        }
        wbTableReload('a' + idstring, encodedtable, id);

    },
    fail: function(ex) {
        // eslint-disable-next-line no-console
        console.log("ex:" + ex);
    },
}]);
}