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
 * @package    local_shopping_cart
 * @copyright  Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import ModalForm from 'core_form/modalform';
import {get_string as getString} from 'core/str';

const SELECTORS = {
    EDITTABLEBUTTON: ' .wb_edit_button',
};

/**
 * Init function.
 * @param {*} selector
 * @param {*} idstring
 * @param {*} encodedtable
 */
export function initializeEditTableButton(selector, idstring, encodedtable) {

    const button = document.querySelector(selector + SELECTORS.EDITTABLEBUTTON);

    if (!button) {
        return;
    }

    // eslint-disable-next-line no-console
    console.log('run initializeEditTableButton');

    if (button.initialized) {
        return;
    } else {
        button.initialized = true;
    }

    button.addEventListener('click', (e) => editTableModal(e, idstring, encodedtable));
}

/**
 * Edit Table Modal.
 * @param {*} event
 * @param {*} idstring
 * @param {*} encodedtable
 */
export function editTableModal(event, idstring, encodedtable) {

    // We two parents up, we find the right element with the necessary information.
    const element = event.target;

    // eslint-disable-next-line no-console
    console.log('closest', element);

    // eslint-disable-next-line no-console
    console.log('values ', idstring);

    const modalForm = new ModalForm({

        // Name of the class where form is defined (must extend \core_form\dynamic_form):
        formClass: "local_wunderbyte_table\\form\\edittable",
        // Add as many arguments as you need, they will be passed to the form:
        args: {
            idstring,
            encodedtable,
        },
        // Pass any configuration settings to the modal dialogue, for example, the title:
        modalConfig: {title: getString('customizewbtable', 'local_wunderbyte_table')},
        // DOM element that should get the focus after the modal dialogue is closed:
        returnFocus: element
    });
    // Listen to events if you want to execute something on form submit.
    // Event detail will contain everything the process() function returned:
    modalForm.addEventListener(modalForm.events.FORM_SUBMITTED, response => {

        // eslint-disable-next-line no-console
        console.log('form submitted response: ', response);

        window.location.reload();
    });

    // Show the form.
    modalForm.show();

}
