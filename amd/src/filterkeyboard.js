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
 * Keyboard accessibility for the filter dropdowns (WCAG 2.1 AA, SC 2.1.1 / 2.4.3 / 2.4.7).
 *
 * Bootstrap treats a dropdown as a menu of links and therefore does three things that break a
 * dropdown filled with checkboxes:
 *  - the arrow keys try to focus the ".dropdown-item" elements, which here are <li> elements and
 *    cannot receive focus, so arrow navigation does nothing at all,
 *  - the document level keyup handler closes every open dropdown as soon as TAB is released, so
 *    tabbing from the first checkbox to the second one closes the filter and drops the focus,
 *  - ENTER never toggles a checkbox (only SPACE does natively).
 *
 * This module takes the keyboard handling for the filter dropdowns over from Bootstrap. All
 * handlers are attached to the dropdown itself and stop the propagation of the keys they handle,
 * so the delegated Bootstrap handlers on the document never see them.
 *
 * @module    local_wunderbyte_table/filterkeyboard
 * @copyright  Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

const SELECTORS = {
    CONTAINER: '.wunderbyte_table_container_',
    DROPDOWN: '.wunderbyteTableFilter .dropdowncontainer .dropdown',
    BUTTON: '.dropdownMenuButton',
    MENU: '.dropdown-menu',
    ITEMS: 'input:not([type="hidden"]):not([disabled]), select:not([disabled]), textarea:not([disabled]),'
        + ' button:not([disabled]), a[href]',
};

// Controls where the arrow keys have a meaning of their own (change the value, move the caret
// between the segments of a date). There the arrow keys must stay with the control.
const OWNARROWKEYS = ['date', 'time', 'datetime-local', 'month', 'week', 'number', 'range'];

const SHOWN = 'show';

/**
 * Attach the keyboard handling to every filter dropdown of one table.
 *
 * @param {string} idstring
 */
export function initializeFilterKeyboard(idstring) {

    const container = document.querySelector(SELECTORS.CONTAINER + idstring);
    if (!container) {
        return;
    }

    container.querySelectorAll(SELECTORS.DROPDOWN).forEach(dropdown => {

        if (dropdown.dataset.keyboardinitialized) {
            return;
        }

        const button = dropdown.querySelector(SELECTORS.BUTTON);
        const menu = dropdown.querySelector(SELECTORS.MENU);

        if (!button || !menu) {
            return;
        }

        // Set while TAB is being pressed inside this dropdown, see the focusout handler below.
        let tabbing = false;

        button.addEventListener('keydown', e => handleButtonKeydown(e, button, menu));
        menu.addEventListener('keydown', e => handleMenuKeydown(e, button, menu));

        // Bootstrap closes all dropdowns on the keyup of TAB. While the focus is still inside this
        // menu, that would throw the user out of the filter after every single tab step.
        dropdown.addEventListener('keydown', e => {
            if (e.key === 'Tab') {
                tabbing = true;
            }
        });
        menu.addEventListener('keyup', e => {
            if (e.key === 'Tab') {
                e.stopPropagation();
                tabbing = false;
            }
        });

        // Tabbing out of the menu closes it - that is the part of the Bootstrap behaviour we do
        // want, we only have to do it ourselves now. Closing on a click elsewhere stays with
        // Bootstrap: doing it here as well would close and immediately reopen the menu when the
        // user clicks the toggle button while the focus is still inside the menu.
        dropdown.addEventListener('focusout', e => {
            if (tabbing && (!e.relatedTarget || !dropdown.contains(e.relatedTarget))) {
                tabbing = false;
                closeMenu(button, menu, false);
            }
        });

        dropdown.dataset.keyboardinitialized = true;
    });
}

/**
 * Keys pressed while the dropdown button itself has the focus.
 *
 * @param {KeyboardEvent} e
 * @param {HTMLElement} button
 * @param {HTMLElement} menu
 */
function handleButtonKeydown(e, button, menu) {

    switch (e.key) {
        case 'ArrowDown':
        case 'ArrowUp':
            e.preventDefault();
            // Bootstrap would try to focus the <li> elements, which never works - we do it ourselves.
            e.stopPropagation();
            openMenu(button, menu);
            focusItem(menu, e.key === 'ArrowUp' ? 'last' : 'first');
            break;
        case 'Enter':
        case ' ':
            // Bootstrap opens the menu on the click the button fires anyway. We only move the focus
            // into the menu afterwards, so the keyboard user lands where the options are.
            focusItem(menu, 'first');
            break;
        case 'Escape':
            e.stopPropagation();
            closeMenu(button, menu, true);
            break;
        default:
            break;
    }
}

/**
 * Keys pressed while the focus is inside the open dropdown.
 *
 * @param {KeyboardEvent} e
 * @param {HTMLElement} button
 * @param {HTMLElement} menu
 */
function handleMenuKeydown(e, button, menu) {

    const items = getItems(menu);
    const current = items.indexOf(document.activeElement);

    switch (e.key) {
        case 'ArrowDown':
            if (ownsArrowKeys(document.activeElement)) {
                break;
            }
            e.preventDefault();
            e.stopPropagation();
            moveFocus(items, current, 1);
            break;
        case 'ArrowUp':
            if (ownsArrowKeys(document.activeElement)) {
                break;
            }
            e.preventDefault();
            e.stopPropagation();
            moveFocus(items, current, -1);
            break;
        case 'Home':
            if (isTextEntry(document.activeElement)) {
                // In a text field HOME/END belong to the caret, not to the option list.
                break;
            }
            e.preventDefault();
            e.stopPropagation();
            focusItem(menu, 'first');
            break;
        case 'End':
            if (isTextEntry(document.activeElement)) {
                break;
            }
            e.preventDefault();
            e.stopPropagation();
            focusItem(menu, 'last');
            break;
        case 'Enter':
            // A checkbox is only toggled by SPACE natively. ENTER is what most users try first and
            // it is what the ARIA authoring practices ask for in a listbox-like popup.
            if (document.activeElement && document.activeElement.type === 'checkbox') {
                e.preventDefault();
                e.stopPropagation();
                toggleCheckbox(document.activeElement);
            }
            break;
        case 'Escape':
            e.preventDefault();
            e.stopPropagation();
            closeMenu(button, menu, true);
            break;
        default:
            break;
    }
}

/**
 * All focusable controls of the menu in DOM order, skipping the hidden ones.
 *
 * @param {HTMLElement} menu
 * @returns {Array} array of elements
 */
function getItems(menu) {
    return Array.from(menu.querySelectorAll(SELECTORS.ITEMS)).filter(item => {
        if (item.hidden || (item.closest('[hidden]') !== null)) {
            return false;
        }
        // Elements hidden via CSS (collapsed hierarchy branches, search filtered entries) have no
        // layout box. The checkboxes of the standard filter are visually hidden via opacity, which
        // keeps the layout box intact, so they are correctly treated as reachable.
        return item.getClientRects().length > 0;
    });
}

/**
 * Move the focus relative to the currently focused item, stopping at both ends.
 *
 * @param {Array} items
 * @param {number} current index of the focused item, -1 if the focus is not on an item
 * @param {number} direction 1 or -1
 */
function moveFocus(items, current, direction) {
    if (!items.length) {
        return;
    }
    if (current === -1) {
        items[direction > 0 ? 0 : items.length - 1].focus();
        return;
    }
    const next = current + direction;
    if (next < 0 || next >= items.length) {
        return;
    }
    items[next].focus();
}

/**
 * Focus the first or the last item of the menu.
 *
 * The search field of a filter is positioned by init.js in a timeout after the menu was opened, so
 * the focus is set in a timeout as well - otherwise it would be set before the field is visible.
 *
 * @param {HTMLElement} menu
 * @param {string} position 'first' or 'last'
 */
function focusItem(menu, position) {
    setTimeout(() => {
        if (!menu.classList.contains(SHOWN)) {
            return;
        }
        const items = getItems(menu);
        if (!items.length) {
            return;
        }
        items[position === 'last' ? items.length - 1 : 0].focus();
    }, 50);
}

/**
 * Toggle a checkbox exactly the way a mouse click or SPACE would.
 *
 * click() and not a hand made change event: several filters (the subtree cascade of the tree
 * filter, the "select all" of the hierarchical filter, the label handling in init.js) listen for
 * click, so only this way ENTER behaves like every other way of ticking the box.
 *
 * @param {HTMLInputElement} checkbox
 */
function toggleCheckbox(checkbox) {
    checkbox.click();
}

/**
 * Open the menu via Bootstrap, if it is not open already.
 *
 * @param {HTMLElement} button
 * @param {HTMLElement} menu
 */
function openMenu(button, menu) {
    if (!menu.classList.contains(SHOWN)) {
        button.click();
    }
}

/**
 * Close the menu via Bootstrap (a click on the toggle), so that Bootstrap keeps its own state,
 * aria-expanded and the popper cleanup consistent.
 *
 * @param {HTMLElement} button
 * @param {HTMLElement} menu
 * @param {boolean} refocus whether the focus returns to the button
 */
function closeMenu(button, menu, refocus) {
    if (menu.classList.contains(SHOWN)) {
        button.click();
    }
    if (refocus) {
        button.focus();
    }
}

/**
 * Whether the arrow keys belong to the control itself (select, date, time, number).
 *
 * @param {HTMLElement} element
 * @returns {boolean}
 */
function ownsArrowKeys(element) {
    if (!element) {
        return false;
    }
    return element.tagName === 'SELECT'
        || (element.tagName === 'INPUT' && OWNARROWKEYS.includes(element.type));
}

/**
 * Whether the element takes text input, where HOME and END have their own meaning.
 *
 * @param {HTMLElement} element
 * @returns {boolean}
 */
function isTextEntry(element) {
    if (!element) {
        return false;
    }
    return element.tagName === 'TEXTAREA'
        || (element.tagName === 'INPUT' && ['text', 'search', 'number', 'date', 'time'].includes(element.type));
}
