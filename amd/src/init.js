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
import Ajax from 'core/ajax';
import Templates from 'core/templates';
import Notification from 'core/notification';

import {initializeCheckboxes, getFilterObjects} from 'local_wunderbyte_table/filter';
import {initializeSearch, getSearchInput} from 'local_wunderbyte_table/search';
import {initializeSort, getSortSelection} from 'local_wunderbyte_table/sort';
import {initializeReload} from 'local_wunderbyte_table/reload';
import {initializeActionButton} from 'local_wunderbyte_table/actionbutton';
import {initializeEditTableButton} from 'local_wunderbyte_table/edittable';
import {initializeReordering} from 'local_wunderbyte_table/reordering';
import {initializeRowsSelect} from './rowsdisplayselect';
import {
    initializeResetFilterButton,
    updateUrlWithFilterSearchSort,
    updateDownloadUrlWithFilterSearchSort
} from './filter';
import {initializeFilterSearch} from './filtersearch';

import {get_string as getString} from 'core/str';

// All these variables will be objects with the idstring so their tables as identifiers.
var loadings = {};
export var queries = {};
var scrollpages = {};
var tablejss = {};
var scrollingelement = {};

var moreThanOneTable = false;
export const SELECTORS = {
    CONTAINER: ".wunderbyte_table_container_",
    FILTER: " .wunderbyteTableFilter",
    WBTABLE: "wunderbyte-table-",
    DOWNLOADELEMENT: "form.wb-table-download-buttons",
};

/**
 * Gets called from mustache template.
 * @param {string} idstring
 * @param {string} encodedtable
 */
export const init = (idstring, encodedtable) => {

    // eslint-disable-next-line no-console
    console.log('init booking ' + idstring, moreThanOneTable);

    if (!queries[idstring]) {
        checkInTable(idstring, encodedtable);
    }

    // Check if there is more than 1 tables, excluding tables created because of search.
    let counter = 0;
    counter = Object.entries(queries).length;

    if (counter > 1) {
        // Check if all have the same value for encodedtable.
        const firstEncodedTable = Object.values(queries)[0]?.encodedtable;
        const allSame = Object.entries(queries).every(obj => obj.encodedtable === firstEncodedTable);
        if (!allSame) {
            moreThanOneTable = true;
        }
    }

    if (idstring && encodedtable) {

        if (!scrollpages.hasOwnProperty(idstring)) {

            if (infinitescrollEnabled(idstring)) {
                scrollpages[idstring] = 0;
            } else {
                scrollpages[idstring] = -1;
            }

        }
        respondToVisibility(idstring, encodedtable, callLoadData);
    }

};

/**
 * Handle Click on Dropdown
 * @param {string} idstring
 */
const initHandleDropdown = (idstring) => {
    const nocheckbox = document.querySelectorAll('.wunderbyte_table_container_' + idstring + ' .hierarchy > button');
    const withcheckbox = document.querySelectorAll('.wunderbyte_table_container_' + idstring + ' .hierarchy > span > button');
    if (nocheckbox) {
        nocheckbox.forEach(element => {
            element.addEventListener('click', function(event) {
                event.stopPropagation();
                const sibling = element.nextElementSibling;
                sibling.classList.toggle("show");
                event.preventDefault();
            });
        });
    }
     if (withcheckbox) {
        withcheckbox.forEach(element => {
            element.addEventListener('click', function(event) {
                event.stopPropagation();
                const parent = element.parentElement;
                const sibling = parent.nextElementSibling;
                sibling.classList.toggle("show");
                event.preventDefault();
            });
        });
    }
};

/**
 * Handle Click on Dropdown
 *
 */
const initHandleDropdownFocusSearch = () => {

    const checkboxes = document.querySelectorAll('.filterelement.filterouter');
    if (checkboxes) {
        Array.from(checkboxes).forEach(cb => {
            cb.addEventListener('click', function(event) {
                event.currentTarget.parentElement.parentElement.parentElement.firstElementChild.style.display = 'none';
            });
        });
    }

    const elements = document.querySelectorAll('.wunderbyteTableFilter .dropdownMenuButton');
    if (elements) {
        Array.from(elements).forEach(element => {
            if (element.nextElementSibling.firstElementChild.children[1] &&
                element.nextElementSibling.firstElementChild.children[1].hidden !== true) {
                element.classList.add('hideFocus');
            }
            element.addEventListener('click', function(event) {
                if (event.currentTarget == element) {
                    setTimeout(() => {
                        if (!element.nextElementSibling.firstElementChild.children[1].hidden) {
                            element.nextElementSibling.firstElementChild.children[1].focus();
                            const buttonHeight = element.clientHeight;
                            element.nextElementSibling.firstElementChild.children[1].style.height = buttonHeight + 'px';
                            const heightWm = buttonHeight + 3;
                            if (element.nextElementSibling.firstElementChild.children[0].firstElementChild.innerHTML &&
                                element.nextElementSibling.firstElementChild.children[0].firstElementChild.innerHTML.length > 28) {
                                element.nextElementSibling.firstElementChild.children[0].firstElementChild.innerHTML =
                                    element.nextElementSibling.firstElementChild.children[0].firstElementChild.innerHTML
                                        .substring(0, 28);
                            }
                            element.nextElementSibling.firstElementChild.children[1].style.top = '-' + heightWm + 'px';
                            element.nextElementSibling.firstElementChild.children[0].style.display = 'block';
                            const posLabel = buttonHeight + 20;
                            element.nextElementSibling.firstElementChild.children[0].style.top = '-' + posLabel + 'px';
                        } else {
                            element.nextElementSibling.firstElementChild.children[0].style.display = 'none';
                        }
                    }, 0);
                }
            });
        });
    }
};

/**
 * Toggle aside block with filters.
 * @param {string} idstring
 */
const initToggleAside = (idstring) => {
    const togglebutton = document.querySelector('#asidecollapse_' + idstring);

    if (togglebutton) {
        togglebutton.addEventListener('click', () => {
            const aside = document.querySelector('.wunderbyte_table_container_' + idstring + ' aside');
            aside.classList.toggle('inactive');
            const wbtable = document.querySelector('.wunderbyte_table_container_' + idstring);
            wbtable.classList.toggle('inactivefilter');
            if (!aside.classList.contains('inactive')) {
            aside.childNodes[1].focus();
            }
        });
    }

};

/**
 * React on visibility change.
 * @param {string} idstring
 * @param {string} encodedtable
 * @param {function} callback
 */
function respondToVisibility(idstring, encodedtable, callback) {

    const identifier = 'a' + idstring;
    let element = document.querySelector('#' + identifier);

    // If we find the table element AND if it has the encoded table set, we abort this.
    // Hereby we avoid to run JS multiple times.
    if (element && !element.dataset.encodedtable) {
        element.dataset.encodedtable = encodedtable;
    } else {

        // We abort everything else, but we run again the components initialization.
        // important, as parts of the table might have been reloaded.
        initializeComponents(idstring, encodedtable);
        return;
    }

    // We only make this callback during init if there is the spinner running.
    // We don't want to run all of this if we don't use lazyloading.
    let spinner = document.querySelector("#" + identifier + 'spinner');

    if ((spinner !== null) && !isHidden(spinner)) {

        var observer = new MutationObserver(function() {
            if (!isHidden(element)) {
                this.disconnect();

                callback(idstring, encodedtable);
            }
        });

        const hiddenElement = returnHiddenElement(element);

        if (hiddenElement !== null) {

            observer.observe(hiddenElement, {attributes: true});
        } else {
            callback(idstring, encodedtable);
        }

    } else {

        const selector = ".wunderbyte_table_container_" + idstring;
        const container = document.querySelector(selector);

        if (container != undefined) {
            // This is what we do when we didn't lazyload.
            // replaceLinksInFrag(idstring, encodedtable, element, null);
            addLinksToPagination(idstring, encodedtable, element);

            initializeComponents(idstring, encodedtable);

            // Check to see if scrolling near bottom of page; load more photos
            // This shoiuld only be added once.

            // As this can only be here once per table, we mark the table.
            addScrollFunctionality(idstring, encodedtable, element);
            initToggleAside(idstring);

            initHandleDropdown(idstring);
            initHandleDropdownFocusSearch();
        }

    }
}

/**
 * Return the next scrollable element.
 * @param {*} node
 * @returns {*} node
 */
function getScrollParent(node) {
    if (node === null) {
        return null;
    }
    if (node.scrollHeight > node.clientHeight) {
        if (doublecheckScrollable(node)) {
            // In some cases (lazyouthtml table), we need to doublecheck if the element is scrollable.
            return node;
        } else {
            return getScrollParent(node.parentNode);
        }
    } else {
        return getScrollParent(node.parentNode);
    }
}

/**
 * Function to check if element is scrollable by checking overflow.
 * @param {*} node
 * @returns {boolean}
 */
function doublecheckScrollable(node) {
    const styles = window.getComputedStyle(node);
    const isScrollable = styles.overflow === 'scroll' || styles.overflow === 'auto';

    return isScrollable;
}

/**
 * Function to check visibility of element.
 * @param {*} el
 * @returns {boolean}
 */
export const isHidden = (el) => {
    var style = window.getComputedStyle(el);
    return ((style.display === 'none') || (style.visibility === 'hidden'));
};

/**
 * Reloads the rendered table and sets it to the div with the right identifier.
 * @param {string} idstring
 * @param {string} encodedtable
 * @param {null|int} page
 * @param {null|string} tsort
 * @param {null|string} thide
 * @param {null|string} tshow
 * @param {null|int} tdir
 * @param {null|int} treset
 * @param {null|string} filterobjects
 * @param {null|string} searchtext
 * @param {null|bool} replacerow
 * @param {null|bool} replacecomponentscontainer
 */
export const callLoadData = (
    idstring,
    encodedtable,
    page = null,
    tsort = null,
    thide = null,
    tshow = null,
    tdir = null,
    treset = null,
    filterobjects = null,
    searchtext = null,
    replacerow = false,
    replacecomponentscontainer = false) => {

    if (loadings[idstring] && !replacerow) {
        return;
    }
    // We reset scrollpage with 0 when we come from the filter.
    if (page !== null) {

        if (infinitescrollEnabled(idstring)) {
            scrollpages[idstring] = page;
        } else {
            scrollpages[idstring] = -1;
        }
    }

    // We always have to see if we need to apply a filter. Reload might come from scroll, but filter has to be applied nevertheless.
    if (filterobjects === null) {
        filterobjects = getFilterObjects(idstring);
    }
    // We always have to see if we need to apply a searchtextfilter.
    if (searchtext === null) {
        searchtext = getSearchInput(idstring);
    }
    // We always have to see if we need to apply a sortorder.
    if (tsort === null) {
        tsort = getSortSelection(idstring);
    }

    const table = document.getElementById('a' + idstring);

    // We don't want to update URL for lazyout tables that be loaded (have childnodes) at this point.
    if (moreThanOneTable !== true && (typeof table === 'object' && table !== null)) {
        if (table.childNodes.length > 0) {
            updateUrlWithFilterSearchSort(filterobjects, searchtext, tsort, tdir);
        }
    }

    let container = document.querySelector(".wunderbyte_table_container_" + idstring);
    if (container) {

        const downloadelement = container.querySelector(SELECTORS.DOWNLOADELEMENT);

        if (downloadelement && downloadelement.dataset.applyfilter) {

            updateDownloadUrlWithFilterSearchSort(idstring, filterobjects, searchtext, tsort, tdir);
        }
    }

    // This is now the individual spinner from the wunderbyte table template.
    let spinner = document.querySelector('#a' + idstring + 'spinner .spinner-border');

    // If we replace the whole table, we show the spinner. If we only add rows in infinite scroll, we don't.
    if (scrollpages[idstring] == 0
        && !replacerow) {
        if (spinner) {
            spinner.classList.remove('hidden');
        }
    }

    // We also have the indidual load spinner.
    // Show the call spinner.
    let callspinner = document.querySelector(".wunderbyte_table_container_" + idstring + " .wb-table-call-spinner");
    if (callspinner) {
        callspinner.classList.remove('hidden');
    }

    // This is used to store information for reload etc.
    checkInTable(
        idstring,
        encodedtable,
        page,
        tsort,
        thide,
        tshow,
        tdir,
        treset,
        filterobjects,
        searchtext,
        replacerow
    );

    loadings[idstring] = true;

    Ajax.call([{
        methodname: "local_wunderbyte_table_load_data",
        args: {
            'encodedtable': encodedtable,
            'page': page,
            'tsort': tsort,
            'thide': thide,
            'tshow': tshow,
            'tdir': tdir,
            'treset': treset,
            'wbtfilter': filterobjects,
            'searchtext': searchtext
        },
        done: async function(res) {
            // Hide the call spinner.
            let callspinner = document.querySelector(".wunderbyte_table_container_" + idstring + " .wb-table-call-spinner");
            if (callspinner) {
                callspinner.classList.add('hidden');
            }

            let jsonobject = '';
            try {
                jsonobject = JSON.parse(res.content);
            } catch (e) {

                const message = await getString('couldnotloaddata', 'local_wunderbyte_table');

                Notification.addNotification({
                    message,
                    type: "danger"
                });

                // We need say we are not loading anymore.
                loadings[idstring] = false;

                // eslint-disable-next-line no-console
                console.log(e);
                return;
            }

            let rendertemplate = res.template;

            // We can always expect a wunderbyte table container at this point.
            // The container will hold wunderbyteTableClass, wunderbyteTableFilter, wunderbyteTableSearch classes.
            let container = document.querySelector(".wunderbyte_table_container_" + idstring);

            if (!container) {
                return;
            }

            let componentscontainer = container.querySelector(".wunderbyte_table_components");
            if (replacecomponentscontainer) {
                componentscontainer = null;
            }

            // If we only increase the scrollpage, we don't need to render everything again.
            if (replacerow
                || (scrollpages[idstring] > 0)) {

                // Also, we want to use the table instead of the container template.
                const rowtemplate = rendertemplate + '_row';

                if (!jsonobject.table.hasOwnProperty('rows')) {
                    // We set the scrollpage to -1 which means that we don't reload anymore.
                    scrollpages[idstring] = -1;
                    loadings[idstring] = false;
                    return;
                }
                let rows = jsonobject.table.rows;

                // We create an array of promises where every line is rendered individually.
                const promises = rows.map(row => {
                    Templates.renderForPromise(rowtemplate, row).then(({html, js}) => {

                        if (replacerow) {

                            // We need the id.
                            const filterobject = JSON.parse(filterobjects);
                            const rowid = filterobject.id;

                            Templates.replaceNode("#a" + idstring
                                + " .rows-container tr[data-id='" + rowid + "']", html, js);
                        } else {
                            // Here we add the rendered content to the table div.
                            Templates.appendNodeContents('#a' + idstring + " .rows-container", html, js);
                        }

                        return true;
                    }).catch(e => {
                        // eslint-disable-next-line no-console
                        console.log(e);
                    });
                    return true;
                });

                if (!tablejss.hasOwnProperty(idstring)) {

                    const promise = returnPromiseToSaveJS(rendertemplate, jsonobject, idstring);

                    promises.push(promise);
                }

                // Once all the promises are fullfilled, we set loading to false.
                Promise.all(promises).then(() => {

                    setTimeout(() => {
                        // We only added rows, but they might need some js from the table, so we add the table js again.
                        Templates.appendNodeContents('#a' + idstring, '', tablejss[idstring]);

                    }, 100);

                    loadings[idstring] = false;

                    return;
                }).catch(e => {
                    // eslint-disable-next-line no-console
                    console.log(e);
                });

                return;
            }

            const promises = [];
            if (!componentscontainer) {
                // If the componentscontainer is not yet rendered, we render the container. else, only the table.
                rendertemplate = rendertemplate + '_container';
            } else {
                const sortselector = '.wunderbyteTableSelect';
                promises.push(Templates.renderForPromise('local_wunderbyte_table/component_sort', jsonobject).then(({html, js}) => {
                    const element = container.querySelector(sortselector);
                    Templates.replaceNode(element, html, js);
                    // Make sure the element is working.
                    initializeComponents(idstring, encodedtable);
                    return true;
                }).catch(ex => {
                    // eslint-disable-next-line no-console
                    console.log(ex);
                }));
            }

            let frag = container.querySelector(".wunderbyteTableClass");

            // If we called a sorting and the result is an empty array, we don't need to render.
            let rows = jsonobject.table.rows;
            if (tsort && (!rows || rows.length < 1)) {

                if (spinner) {
                    spinner.classList.add('hidden');
                }
                if (table) {
                    table.classList.remove('hidden');
                }

                loadings[idstring] = false;

            } else {
                // We render the html with the right template.
                promises.push(Templates.renderForPromise(rendertemplate, jsonobject).then(({html, js}) => {

                    if (componentscontainer) {
                        // Now we clean the existing table.
                        while (frag.firstChild) {
                            frag.removeChild(frag.lastChild);
                        }

                        // Here we add the rendered content to the table div.
                        Templates.appendNodeContents('#a' + idstring, html, js);
                    } else {
                        // Here we try to render the whole.hro
                        const parent = container.parentElement;
                        container.remove();
                        Templates.appendNodeContents(parent, html, js);

                        container = document.querySelector(".wunderbyte_table_container_" + idstring);
                    }
                    if (container == undefined) {
                        return true;
                    }
                    addLinksToPagination(idstring, encodedtable, container);

                    // When everything is done, we loaded fine.
                    loadings[idstring] = false;

                    if (spinner) {
                        spinner.classList.add('hidden');
                    }
                    if (table) {
                        table.classList.remove('hidden');
                    }

                    // Make sure all elements are working.
                    initializeComponents(idstring, encodedtable);

                    if (!container) {
                        return true;
                    }
                    const element = container.querySelector('#a' + idstring);

                    // This is the place where we are after lazyloading. We check if we need to reinitialize scrolllistener:
                    addScrollFunctionality(idstring, encodedtable, element);
                    let scrolltotop = false;
                    if (container && container.classList.contains('wunderbyte_table_scroll_on')) {
                        scrolltotop = true;
                    }
                    if (container && scrolltotop) {
                        const navbar = document.querySelector('.navbar');

                        if (navbar) {

                            const navbarHeight = navbar.offsetHeight;

                            const offsetPosition = container.getBoundingClientRect().top + window.pageYOffset - navbarHeight;

                            window.scrollTo({
                                top: offsetPosition,
                                behavior: 'smooth'
                            });
                        } else {
                            container.scrollIntoView({
                                behavior: 'smooth',
                                block: 'start'
                            });
                        }
                    }

                    return true;
                }).catch(ex => {
                    loadings[idstring] = false;
                    Notification.addNotification({
                        message: 'failed rendering ' + ex,
                        type: "danger"
                    });
                }));
            }

            // We excecute the promises from the array one after the other.
            // eslint-disable-next-line no-unused-vars
            const x = await promises[0];
            // eslint-disable-next-line no-unused-vars
            const y = await promises[1];
        },
        fail: function(err) {

            // If we have an error, resetting the table might be enough. we do that.
            // To avoid a loop, we only do this in special cases.
            if ((treset != 1)) {
                callLoadData(idstring, encodedtable, page, null, null, null, null, 1);
            } else {
                let node = document.createElement('DIV');
                let textnode = document.createTextNode(err.message);
                node.appendChild(textnode);
                table.appendChild(node);
                spinner.classList.add('hidden');
                table.classList.remove('hidden');

                // Hide the call spinner.
                let callspinner = document.querySelector(".wunderbyte_table_container_" + idstring + " .wb-table-call-spinner");
                if (callspinner) {
                    callspinner.classList.add('hidden');
                }
            }
        }
    }]);
};

/**
 * Add the scroll functionality to the right table.
 * @param {*} idstring
 * @param {*} encodedtable
 * @param {*} element
 * @returns {void}
 */
function addScrollFunctionality(idstring, encodedtable, element) {

    // First we check if scroll functioanlity is enabled.
    if (!infinitescrollEnabled(idstring)) {
        return;
    }

    if (element.dataset.scrollinitialized) {
        return;
    }

    element.dataset.scrollinitialized = true;

    const scrollableelement = getScrollParent(element);

    if (scrollableelement) {
        scrollableelement.addEventListener('scroll', () => {

            if (!scrollingelement.hasOwnProperty(idstring)) {
                scrollingelement[idstring] = 'scrollElement';
            } else {
                if (scrollingelement[idstring] === 'scrollElement') {
                    scrollListener(element, idstring, encodedtable);
                }
            }
        });
    }

    // It's not easy to decide which is the right, so we have to add both.
    window.addEventListener('scroll', () => {

        if (!scrollingelement.hasOwnProperty(idstring)) {
            scrollingelement[idstring] = 'window';
        } else {
            if (scrollingelement[idstring] === 'window') {
                scrollListener(element, idstring, encodedtable);
            }
        }
    });

}

/**
 * To be called in the scroll listener.
 * @param {node} element
 * @param {string} idstring
 * @param {string} encodedtable
 * @returns {void}
 */
function scrollListener(element, idstring, encodedtable) {
    // We only want to scroll, if the element is visible.
    // So, if we find a hidden element in the parent, we don't scroll.
    if (returnHiddenElement(element)) {
        return;
    }
    const elementtop = element.getBoundingClientRect().top;
    const screenheight = document.body.scrollHeight;

    let container = document.querySelector(".wunderbyte_table_container_" + idstring);
    const tableelement = container.querySelector('[class^="' + SELECTORS.WBTABLE + '"]');

    // If we can't find this table element, we abort.
    if (!tableelement) {
        return;
    }

    const tableelementheight = tableelement.getBoundingClientRect().height;

    if (!loadings[idstring] && scrollpages[idstring] >= 0) {
        if (elementtop + tableelementheight - screenheight < 0) {
            scrollpages[idstring] = scrollpages[idstring] + 1;
            callLoadData(idstring,
                encodedtable,
                scrollpages[idstring],
                null,
                null,
                null,
                null,
                null,
                null,
                null);
        }
    }
}

/**
 * If the element or one of its parents is hidden, we return it. the hiddenn element.
 * Else we return null.
 * @param {node} element
 * @returns {null|node}
 */
function returnHiddenElement(element) {
    // We look if we find a hidden parent. If not, we load right away.
    while (element !== null) {
        if (!isHidden(element)) {
            element = element.parentElement;
        } else {
            return element;
        }
    }
    return null;
}

/**
 * The rendered table has links we can't use. We replace them with eventlisteners and use the callLoadData function.
 * @param {string} idstring
 * @param {string} encodedtable
 * @param {DocumentFragment} frag
 */
function addLinksToPagination(idstring, encodedtable, frag) {
    if (!frag) {
        return;
    }

    var arrayOfPageItems = frag.querySelectorAll(".page-item");

    if (!arrayOfPageItems || arrayOfPageItems.length == 0) {
        return;
    }
    arrayOfPageItems.forEach(item => {

        let pageNumber = item.dataset.pagenumber;

        if (pageNumber) {
            --pageNumber;
            item.addEventListener('click', () => {
                callLoadData(idstring, encodedtable, pageNumber);
            });
        }
    });

    // Initialize go to page
    var selectbox = frag.querySelector("select.go-to-page-select");
    if (selectbox) {
        selectbox.addEventListener('change', (event) => {
            let target = event.target;
            // eslint-disable-next-line no-console
            console.log(target);
            let pagenumber = target.value - 1;
            callLoadData(idstring, encodedtable, pagenumber);

        });
    }
}

/**
 * Function to check if the talbe in question has infinitescroll enabled.
 * @param {string} idstring
 * @returns {bool}
 */
export function infinitescrollEnabled(idstring) {
    // If we don't find the infinitescrollelement, we don#t add the listener.
    const selector = ".wunderbyte_table_container_" + idstring;
    if (document.querySelector(selector + ' div.infinitescroll_enabled')) {
        return true;
    }
    return false;
}

/**
 * Initialize all the JS we need.
 * @param {string} idstring
 * @param {string} encodedtable
 */
function initializeComponents(idstring, encodedtable) {
    const selector = ".wunderbyte_table_container_" + idstring;

    initializeCheckboxes(selector, idstring, encodedtable);
    initializeSearch(selector, idstring, encodedtable);
    initializeSort(selector, idstring, encodedtable);
    initializeRowsSelect(selector, idstring, encodedtable);
    initializeFilterSearch(selector, idstring, encodedtable);
    initializeResetFilterButton(selector, idstring, encodedtable);
    initializeEditTableButton(selector, idstring, encodedtable);
    initializeReordering(selector, idstring, encodedtable);

    // A very strange error leads to a failed import from the reloadTable.js under some circumstances.
    // Reload has to be called with this precaution therefore.
    if (initializeReload) {
        initializeReload(selector, idstring, encodedtable);
    }
    initializeActionButton(selector, idstring, encodedtable);

}

/**
 * Function to return promise.
 * @param {*} rendertemplate
 * @param {*} jsonobject
 * @param {*} idstring
 * @returns {Promise}
 */
function returnPromiseToSaveJS(rendertemplate, jsonobject, idstring) {
    // eslint-disable-next-line no-unused-vars
    return Templates.renderForPromise(rendertemplate, jsonobject).then(({html, js}) => {

        tablejss[idstring] = js;

        return true;
    }).catch(e => {
        // eslint-disable-next-line no-console
        console.log(e);
    });
}

/**
 * Function to save queries. Has some logic which helps us to achieve the desired result.
 * @param {*} idstring
 * @param {*} encodedtable
 * @param {*} page
 * @param {*} tsort
 * @param {*} thide
 * @param {*} tshow
 * @param {*} tdir
 * @param {*} treset
 * @param {*} filterobjects
 * @param {*} searchtext
 * @param {*} replacerow
 */
function checkInTable(
    idstring,
    encodedtable,
    page = null,
    tsort = null,
    thide = null,
    tshow = null,
    tdir = null,
    treset = null,
    filterobjects = null,
    searchtext = null,
    replacerow = false) {

    // We don't want to save any queries that want to replace row.
    if (replacerow) {
        return;
    }

    queries[idstring] = {
        idstring,
        encodedtable,
        page,
        tsort,
        thide,
        tshow,
        tdir,
        treset,
        filterobjects,
        searchtext,
        replacerow: false // Replace row is always false.
    };
}