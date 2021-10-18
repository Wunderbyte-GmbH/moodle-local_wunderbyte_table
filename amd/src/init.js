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


// import * as Str from 'core/str';
import Ajax from 'core/ajax';

export const init = (idstring) => {
    callLoadData(idstring);
};

export const callLoadData = (
    idstring,
    page = null,
    tsort = null,
    thide = null,
    tshow = null,
    tdir = null,
    treset = null) => {

    let table = document.getElementById('a' + idstring);
    let spinner = document.querySelector('#a' + idstring + 'spinner .spinner-border');
    spinner.classList.toggle('hidden');
    table.classList.toggle('hidden');

    let encodedtable = table.getAttribute('data-encodedtable');

    Ajax.call([{
        methodname: "local_wunderbyte_table_load_data",
        args: {
            'encodedtable': encodedtable,
            'page': page,
            'tsort': tsort,
            'thide': thide,
            'tshow': tshow,
            'tdir': tdir,
            'treset': treset
        },
        done: function(res) {

            let frag = document.createRange().createContextualFragment(res.content);

            if (!page) {
                const activepage = frag.querySelector('li.page-item active');
                if (activepage) {
                    page = activepage.getAttribute('data-page-number');
                }
            }

            replaceDownloadLink(idstring, frag);
            replaceResetTableLink(idstring, frag, page);
            replacePaginationLinks(idstring, frag);
            replaceSortColumnLinks(idstring, frag, page);

            let table = document.getElementById('a' + idstring);

            while (table.firstChild) {
                table.removeChild(table.lastChild);
            }
            table.appendChild(frag);
            spinner.classList.toggle('hidden');
            table.classList.toggle('hidden');
        },
        fail: function() {
            spinner.classList.toggle('hidden');
            table.classList.toggle('hidden');
        }
    }]);
};

export const replaceSortColumnLinks = (idstring, frag, page) => {

    var arrayOfItems = frag.querySelectorAll("th.header a");

    arrayOfItems.forEach(item => {
        var sortid = item.getAttribute('data-sortby');
        var sortorder = item.getAttribute('data-sortorder');
        var thide = item.getAttribute('data-action') == 'hide' ? item.getAttribute('data-column') : null;
        var tshow = item.getAttribute('data-action') == 'show' ? item.getAttribute('data-column') : null;

        item.setAttribute('href', '#');
        item.addEventListener('click', () => {
            callLoadData(idstring, page, sortid, thide, tshow, sortorder);
        });
    });
};

export const replaceResetTableLink = (idstring, frag, page) => {
    var arrayOfItems = frag.querySelectorAll("div.resettable");

    if (!arrayOfItems || arrayOfItems.length == 0) {
        return;
    }
    arrayOfItems.forEach(item => {
        var classofelement = item.getAttribute('class');
        if (classofelement.indexOf('resettable') >= 0) {
            let listOfChildren = item.querySelectorAll('a');
            listOfChildren.forEach(subitem => {
                subitem.setAttribute('href', '#');
                subitem.addEventListener('click', () => {
                    callLoadData(idstring, page, null, null, null, null, 1);
                });
            });
        }
    });
};

export const replacePaginationLinks = (idstring, frag) => {
    var arrayOfPageItems = frag.querySelectorAll(".page-item");

    if (!arrayOfPageItems || arrayOfPageItems.length == 0) {
        return;
    }
    arrayOfPageItems.forEach(item => {
        var element = item.querySelector('a');
        if (!element) {
            return;
        }
        var url = element.getAttribute("href");
        var pageNumber;
        if (url != undefined && url != '#') {
            let newurl = new URL(url);
            var urlParams = new URLSearchParams(newurl.search);
            pageNumber = urlParams.get('page');
        }
        element.setAttribute('href', '#');
        if (pageNumber) {
            element.addEventListener('click', () => {
                callLoadData(idstring, pageNumber);
            });
        }
    });
};

export const replaceDownloadLink = (idstring, frag) => {

    let table = document.getElementById('a' + idstring);
    let encodedtable = table.getAttribute('data-encodedtable');

    encodedtable = encodeURIComponent(encodedtable);

    var arrayOfItems = frag.querySelectorAll("form");

    arrayOfItems.forEach(item => {
        if (item.tagName == 'FORM') {
            let newnode = document.createElement('input');
            newnode.setAttribute('type','hidden');
            newnode.setAttribute('name', 'encodedtable');
            newnode.setAttribute('value', encodedtable);
            item.appendChild(newnode);
        }
    });
};