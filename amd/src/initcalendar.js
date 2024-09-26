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
 * @package    local_urise
 * @author     Christian Badusch
 * @copyright  2024 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Handle Calendar.
 *
 * @module     local_urise
 * @copyright  2024 Wunderbyte GmbH
 * @author     Georg Maißer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


import {
    get_strings as getStrings,
 } from 'core/str';

/**
 * [Description for init]
 *
 * @param {string} id
 * @return [type]
 *
 */
export async function init(id) {

    const calendarElement = document.getElementById(id);
    const data = calendarElement.dataset.rowswithdates;
    const datesobject = JSON.parse(data);


    const isLeapYear = (year) => {
        return (
            (year % 4 === 0 && year % 100 !== 0 && year % 400 !== 0) ||
            (year % 100 === 0 && year % 400 === 0)
        );
    };
    const getFebDays = (year) => {
        return isLeapYear(year) ? 29 : 28;
    };
    let calendar = document.querySelector('.calendar');

    const strings = [
        {
          key: 'january',
          component: 'local_wunderbyte_table',
        },
        {
          key: 'february',
          component: 'local_wunderbyte_table',
        },
        {
          key: 'march',
          component: 'local_wunderbyte_table',
        },
        {
        key: 'april',
        component: 'local_wunderbyte_table',
        },
        {
        key: 'may',
        component: 'local_wunderbyte_table',
        },
        {
        key: 'june',
        component: 'local_wunderbyte_table',
        },
        {
        key: 'july',
        component: 'local_wunderbyte_table',
        },
        {
        key: 'august',
        component: 'local_wunderbyte_table',
        },
        {
        key: 'september',
        component: 'local_wunderbyte_table',
        },
        {
        key: 'october',
        component: 'local_wunderbyte_table',
        },
        {
        key: 'november',
        component: 'local_wunderbyte_table',
        },
        {
        key: 'december',
        component: 'local_wunderbyte_table',
        },
      ];

    const localizedstrings = await getStrings(strings);
    const monthNames = localizedstrings;
    let monthPicker = document.querySelector('#month-picker');
    const dayTextFormate = document.querySelector('.day-text-formate');
    const timeFormate = document.querySelector('.time-formate');
    const dateFormate = document.querySelector('.date-formate');

    monthPicker.onclick = () => {
        monthList.classList.remove('hideonce');
        monthList.classList.remove('hide');
        monthList.classList.add('show');
        dayTextFormate.classList.remove('showtime');
        dayTextFormate.classList.add('hidetime');
        timeFormate.classList.remove('showtime');
        timeFormate.classList.add('hideTime');
        dateFormate.classList.remove('showtime');
        dateFormate.classList.add('hideTime');
    };

    const generateCalendar = (month, year) => {

        const highlightSessions = [];
        datesobject.forEach(session => {
            const date = new Date(session.coursestarttime * 1000);
            const object = {url: session.url, timestamp: date};
            highlightSessions.push(object);
        });
        let calendarDays = document.querySelector('.calendar-days');
        calendarDays.innerHTML = '';
        let calendarHeaderYear = document.querySelector('#year');
        let daysOfMonth = [
            31,
            getFebDays(year),
            31,
            30,
            31,
            30,
            31,
            31,
            30,
            31,
            30,
            31,
        ];

        monthPicker.innerHTML = monthNames[month];

        calendarHeaderYear.innerHTML = year;

        let firstDay = new Date(year, month);


        for (let i = 0; i <= daysOfMonth[month] + firstDay.getDay() - 1; i++) {
            let day = document.createElement('div');
            if (i >= firstDay.getDay()) {
                let dayNumber = i - firstDay.getDay() + 1;
                day.innerHTML = dayNumber;

                let dayDate = new Date(year, month, dayNumber);

                let matchedHighlightSession = null;
                let isHighlighted = highlightSessions.some(highlightSession => {
                    if (highlightSession.timestamp.getDate() === dayDate.getDate() &&
                        highlightSession.timestamp.getMonth() === dayDate.getMonth() &&
                        highlightSession.timestamp.getFullYear() === dayDate.getFullYear()) {
                        matchedHighlightSession = highlightSession; // Store the matched session
                        return true;
                    }
                    return false;
                });

                if (isHighlighted && matchedHighlightSession) {
                    day.classList.add('highlight-date'); // Add a class to highlight

                    // Create an <a> element with the specified class and href
                    let link = document.createElement('a');
                    link.classList.add('stretched-link');
                    link.href = matchedHighlightSession.url;
                // Insert the <a> element into the div
                day.appendChild(link);
                }

            }
            calendarDays.appendChild(day);
        }
    };

    let monthList = calendar.querySelector('.month-list');
    monthNames.forEach((e, index) => {
        let month = document.createElement('div');
        month.innerHTML = `<div>${e}</div>`;

        monthList.append(month);
        month.onclick = () => {
            currentMonth.value = index;
            generateCalendar(currentMonth.value, currentYear.value);
            monthList.classList.replace('show', 'hide');
            dayTextFormate.classList.remove('hideTime');
            dayTextFormate.classList.add('showtime');
            timeFormate.classList.remove('hideTime');
            timeFormate.classList.add('showtime');
            dateFormate.classList.remove('hideTime');
            dateFormate.classList.add('showtime');
        };
    });
    (function() {
        monthList.classList.add('hideonce');
    })();
    document.querySelector('#pre-year').onclick = () => {
        --currentYear.value;
        generateCalendar(currentMonth.value, currentYear.value);
    };
    document.querySelector('#next-year').onclick = () => {
        ++currentYear.value;
        generateCalendar(currentMonth.value, currentYear.value);
    };

    let currentDate = new Date();
    let currentMonth = {value: currentDate.getMonth()};
    let currentYear = {value: currentDate.getFullYear()};
    generateCalendar(currentMonth.value, currentYear.value);
}
