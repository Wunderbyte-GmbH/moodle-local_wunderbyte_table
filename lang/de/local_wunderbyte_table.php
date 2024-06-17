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

// General strings.
$badgeexp = '<span class="badge bg-danger text-light"><i class="fa fa-flask" aria-hidden="true"></i> Experimentell</span>';
$string['changesortorder'] = "Ändere die Sortierungsrichtung";
$string['couldnotloaddata'] = "Konnte keine Daten laden";
$string['customizewbtable'] = 'Wunderbyte Table anpassen';
$string['downloadas'] = "Tabelle laden als";
$string['edittable'] = "Editieren";
$string['filter'] = 'Tabelle filtern';
$string['loading'] = 'Laden...';
$string['noentriesfound'] = "Keine Einträge gefunden";
$string['norecords'] = "Keine Daten gefunden.";
$string['orderdown'] = "Von Z nach A sortiert";
$string['orderup'] = "Von A nach Z sortiert";
$string['print'] = "Tabelle herunterladen";
$string['reload'] = "Tabelle neu laden";
$string['search'] = 'Suchen';
$string['sortby'] = 'Sortieren nach...';

// Capabilities.
$string['wunderbyte_table:canaccess'] = 'Kann auf Wunderbyte Table zugreifen';
$string['wunderbyte_table:canedittable'] = 'Kann auf Wunderbyte Table editieren';

// Caches.
$string['cachedef_cachedfulltable'] = 'Wunderbyte Table: Cache für die ganze Tabelle';
$string['cachedef_cachedrawdata'] = "Wunderbyte Table: Standard Cache";
$string['cachedef_encodedtables'] = 'Wunderbyte Table: Cache für enkodierte Tabellen';
$string['cachedef_cachedfilters'] = 'Wunderbyte Table: Cache für Filter';

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
$string['logfiltercaches'] = 'Debug-Feature: Speichere Query-Hashes in der DB.
Das hat negative Auswirkugen auf die Performance ' . $badgeexp;
$string['allowsearchincolumns'] = 'Erlaube Suche in Spalten mittels ":" ' . $badgeexp;

// Hourlist filter.
$string['from0to1'] = "00:00 - 01:00";
$string['from1to2'] = "01:00 - 02:00";
$string['from2to3'] = "02:00 - 03:00";
$string['from3to4'] = "03:00 - 04:00";
$string['from4to5'] = "04:00 - 05:00";
$string['from5to6'] = "05:00 - 06:00";
$string['from6to7'] = "06:00 - 07:00";
$string['from7to8'] = "07:00 - 08:00";
$string['from8to9'] = "08:00 - 09:00";
$string['from9to10'] = "09:00 - 10:00";
$string['from10to11'] = "10:00 - 11:00";
$string['from11to12'] = "11:00 - 12:00";
$string['from12to13'] = "12:00 - 13:00";
$string['from13to14'] = "13:00 - 14:00";
$string['from14to15'] = "14:00 - 15:00";
$string['from15to16'] = "15:00 - 16:00";
$string['from16to17'] = "16:00 - 17:00";
$string['from17to18'] = "17:00 - 18:00";
$string['from18to19'] = "18:00 - 19:00";
$string['from19to20'] = "19:00 - 20:00";
$string['from20to21'] = "20:00 - 21:00";
$string['from21to22'] = "21:00 - 22:00";
$string['from22to23'] = "22:00 - 23:00";
$string['from23to24'] = "23:00 - 24:00";

// Edit filter.
$string['wbtablefiltersettingsheader'] = 'Filter anpassen';
$string['wbtabletablesettingsheader'] = 'Weitere Einstellungen';
$string['filterinactive'] = 'Verberge den Filter';
$string['showfilter'] = 'Zeige diesen Filter';
$string['editfiltername'] = 'Bearbeite den Filternamen';
$string['allowedittable'] = 'Bearbeite Tabelleneinstellungen (zum Testen, nicht auf produktiven Websites verwenden) ' . $badgeexp;
$string['showdownloadbutton'] = 'Download-Button anzeigen';
$string['applyfilterondownload'] = 'Filter auf Download anwenden';
$string['showreloadbutton'] = 'Reload-Button anzeigen';
$string['showcountlabel'] = 'Anzahl der gefundenen Einträge anzeigen';
$string['stickyheader'] = 'Header fixieren (sticky header)';
$string['showrowcountselect'] = 'Dropdown für Anzahl der anzuzeigenden Zeilen anzeigen';
$string['placebuttonandpageelementsontop'] = 'Buttons und Elemente oben statt unten anzeigen';
$string['tableheight'] = 'Tabellen-Höhe';
$string['tableheight_help'] = 'Die Tabellenhöhe wird in Pixel angegeben, sinnvolle Werte beginnen daher bei ca. 300.
Bei 0 wird die Höhe automatisch anhand der angezeigten Tabellen berechnet.';
$string['pagesize'] = 'Anzahl der Einträge pro Seite';
$string['addcheckboxes'] = 'Für jede Zeile eine Checkbox anzeigen';
$string['filteronloadinactive'] = 'Filter standardmäßig zugeklappt';
$string['infinitescroll'] = 'Dynamisches Weiterscrollen aktivieren (infinite scrolling)';

$string['datepicker'] = 'Datumsfilter';
$string['hourlist'] = 'Stundenlistenfilter';
$string['standardfilter'] = 'Standardfilter';
$string['weekdays'] = 'Wochentagefilter';

$string['tableheadersortableitem'] = '<i class="fa fa-arrows" aria-label="Sortieren"></i>';

// Errors.
$string['valuehastobeint'] = "Wert muss eine Zahl sein";

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
