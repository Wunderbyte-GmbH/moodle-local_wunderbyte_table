<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * German plugin strings are defined here.
 *
 * @package     local_wunderbyte_table
 * @category    string
 * @copyright   2023 Wunderbyte GmbH <info@wunderbyte.at>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Wunderbyte Table';
$string['loading'] = 'Laden...';
$string['search'] = 'Suchen';
$string['sortby'] = 'Sortieren nach...';
$string['changesortorder'] = "Ändere die Sortierungsrichtung";
$string['orderup'] = "Von A nach Z sortiert";
$string['orderdown'] = "Von Z nach A sortiert";

$string['noentriesfound'] = "Keine Einträge gefunden";
$string['couldnotloaddata'] = "Konnte keine Daten laden";

$string['filter'] = 'Tabelle filtern';
$string['reload'] = "Tabelle neu laden";
$string['print'] = "Tabelle herunterladen";
$string['downloadas'] = "Tabelle laden als";

$string['norecords'] = "Keine Daten gefunden.";

// Capabilities.
$string['wunderbyte_table:canaccess'] = 'Kann auf Wunderbyte Table zugreifen';

// Caches.
$string['cachedef_cachedfulltable'] = 'Cache für die ganze Tabelle';
$string['cachedef_cachedrawdata'] = "Wunderbyte Table Standard Cache";
$string['cachedef_encodedtables'] = 'Cache für enkodierte Tabellen';

// Info messages over table.
$string['countlabel'] = '{$a->filteredrecords} von {$a->totalrecords} Einträgen gefunden ';
$string['filtercountmessage'] = '| {$a->filtersum} Filter auf: {$a->filtercolumns} | ';
$string['showallrecords'] = 'Alle Einträge anzeigen';

$string['checkallcheckbox'] = "Alles auswählen";
$string['functiondoesntexist'] = "Funktion des Aktionsbuttons exisitert nicht.";
$string['tableheadercheckbox'] = '<input type="checkbox" class="tableheadercheckbox">';

// Example strings.
$string['deletedatatitle'] = 'Möchten Sie diese Daten wirklich löschen?';
$string['deletedatabody'] = 'Sie sind dabei, diese Daten zu löschen: <br> "{$a->data}"';
$string['deletedatasubmit'] = 'Löschen';

$string['generictitle'] = 'Möchten Sie wirklich diese Daten bearbeiten?';
$string['genericbody'] = 'Sie sind dabei, diese Zeilen zu bearbeiten: <br> "{$a->data}"';
$string['genericsubmit'] = 'Bestätigen';

$string['somethingwentwrong'] = 'Etwas ist schiefgelaufen. Melden Sie den Fehler ihrem Admin';
$string['nocheckboxchecked'] = 'Keine checkbox ausgewählt';
$string['checkbox'] = 'Checkbox';
$string['module'] = 'Modul';
$string['apply_filter'] = 'Filter anwenden';

$string['pagelabel'] = 'Zeige {$a} Zeilen';

// Filter for timespan.
$string['displayrecords'] = 'Zeige Daten';
$string['within'] = 'innerhalb';
$string['overlapboth'] = 'überlappend mit';
$string['overlapstart'] = 'Beginn überlappend';
$string['overlapend'] = 'Ende überlappend';
$string['before'] = 'vor';
$string['after'] = 'nach';
$string['startvalue'] = 'Anfang';
$string['endvalue'] = 'Ende';
$string['selectedtimespan'] = 'gewählter Zeitspanne';
$string['timespan'] = 'Zeitspanne';
$string['flexoverlap'] = 'überlappend mit';

$string['change'] = 'Ändere';
$string['hourlastmodified'] = 'Stunde, zu der zuletzt aktualisiert wurde';

// Events.
$string['table_viewed'] = 'Tabelle angesehen';
$string['table_viewed_desc'] = 'Die NutzerIn hat folgende Tabelle angesehen: "{$a}"';
$string['action_executed'] = 'Aktion ausgeführt';
$string['action_executed_desc'] = 'Die NutzerIn hat eine Aktion "{$a->methodname}" in der folgenden Tabelle ausgeführt: "{$a->tablename}"';

// Settings.
$string['savesettingstodb'] = 'Speichere Wunderbyte Table-Einstellungen in der Datenbank';

// Bewlow this line, there are only strings for the demo site.
// Action Buttons demo names.
$string['nmmcns'] = 'Kein Modal, mehrere Requests, Keine Checkbox Auswahl';
$string['nmscns'] = 'Kein Modal, ein Request, keine Checkbox Auswahl';
$string['ymmcns'] = 'Modal, mehrere Requests, keine Checkbox Auswahl';
$string['ymscns'] = 'Modal, ein Request, keine Checkbox Auswahl';
$string['nmmcys'] = 'Kein Modal, mehrere Requests, Checkbox Auswahl';
$string['nmscys'] = 'Kein Modal, ein Request, Checkbox Auswahl';
$string['ymmcys'] = 'Modal, mehrere Requests, Checkbox Auswahl';
$string['ymscys'] = 'Modal, ein Request, Checkbox Auswahl';

$string['table1name'] = 'Demo Tabelle 1';
$string['table2name'] = 'Demo Tabelle 2';
$string['table3name'] = 'Demo Tabelle 3';
$string['table4name'] = 'Demo Tabelle 4';
$string['id'] = 'ID';
