<?php
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
 * Contains class mod_questionnaire\output\indexpage
 *
 * @package    local_wunderbyte_table
 * @copyright  2020 Wunderbyte Gmbh <info@wunderbyte.at>
 * @author     Georg Maißer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 **/
// phpcs:ignoreFile

namespace local_wunderbyte_table\output;

use local_wunderbyte_table\demo_table;
use local_wunderbyte_table\filters\types\datepicker;
use local_wunderbyte_table\filters\types\hierarchicalfilter;
use local_wunderbyte_table\filters\types\hourlist;
use local_wunderbyte_table\filters\types\intrange;
use local_wunderbyte_table\filters\types\standardfilter;
use local_wunderbyte_table\filters\types\weekdays;
use local_wunderbyte_table\wunderbyte_table;
use renderable;
use renderer_base;
use templatable;

define('TABLE1NAME', get_string('table1name', 'local_wunderbyte_table'));
define('TABLE2NAME', get_string('table2name', 'local_wunderbyte_table'));
define('TABLE3NAME', get_string('table3name', 'local_wunderbyte_table'));
define('TABLE4NAME', get_string('table4name', 'local_wunderbyte_table'));

/**
 * demo class is used to render several demo tables for use in demo template (tabs).
 * @package local_wunderbyte_table
 *
 */
class demo implements renderable, templatable {

    /**
     * An idstring for the table & spinner.
     *
     * @var string
     */
    public $idstring;

    /**
     * The encoded settings for the sql table.
     *
     * @var string
     */
    public $encodedtable;

    /**
     * Constructor.
     *
     */
    public function __construct() {

    }

    /**
     * Renders demo table 1. Table 1 displays pagination and all types of demo action buttons.
     *
     * @return demo_table
     *
     */
    private function render_table_1() {
        // The $uniqueid Should be composed by ASCII alphanumeric characters, underlines and spaces only!
        // It is recommended to avoid of usage of simple single words like "table" to reduce chance of affecting by Moodle`s core CSS
        $table = new demo_table('demotable_1');

        // Add template switcher to table.
        $table->add_template_to_switcher('local_wunderbyte_table/twtable_list', get_string('viewlist', 'local_wunderbyte_table'), true);
        $table->add_template_to_switcher('local_wunderbyte_table/twtable_cards', get_string('viewcards', 'local_wunderbyte_table'));

        $columns = [
            'id' => get_string('id', 'local_wunderbyte_table'),
            'username' => get_string('username'),
            'firstname' => get_string('firstname'),
            'lastname' => get_string('lastname'),
            'email' => get_string('email'),
            'action' => get_string('action'),
            'department' => get_string('department'),
            'timemodified' => get_string('modified'),

        ];

        // $table->add_subcolumns('cardbody', ['id', 'username', 'firstname', 'lastname', 'email']);
        // Number of items must be equal.
        $table->define_headers(array_values($columns));
        $table->define_columns(array_keys($columns));

        // Here you can use add_subcolumns with 'cardfooter" to show content in cardfooter.

        // Not in use right now, this is how an image is added to the card.
        // With the two lines below, image is shown only in card header.
        // The image value should be eg. <img src="..." class="card-img-top d-md-none">.
        // Use add_subcolumns with 'cardimage" and image like shown above.

        // This adds the width to all normal columns.
        // $table->add_classes_to_subcolumns('cardbody', ['columnclass' => 'wunderbytetd']);
        // This avoids showing all keys in list view.
        // $table->add_classes_to_subcolumns('cardbody', ['columnkeyclass' => 'd-md-none']);

        // Override naming for columns. one could use getstring for localisation here.
        // $table->add_classes_to_subcolumns('cardbody', ['keystring' => 'Moodle id'], ['id']);

        // To hide key in cardheader, set only for special columns.
        // $table->add_classes_to_subcolumns('cardheader', ['columnkeyclass' => 'hidden'], ['firstanme']);

        // Keys are already hidden by for lists, but here we also hide some keys for cards.
        // $table->add_classes_to_subcolumns('cardbody', ['columnkeyclass' => 'hidden'], ['firstname']);
        // $table->add_classes_to_subcolumns('cardbody', ['columnkeyclass' => 'hidden'], ['lastname']);
        // To hide value in card body (because this value is shown in header already).
        // $table->add_classes_to_subcolumns('cardbody', ['columnvalueclass' => 'd-none d-md-block'], ['fullname']);
        // Set Classes not linked to the individual records or columns but for the container.
        // $table->set_tableclass('listheaderclass', 'card d-none d-md-block');
        // $table->set_tableclass('cardheaderclass', 'card-header d-md-none bg-warning');
        // $table->set_tableclass('cardbodyclass', 'card-body row');

        $standardfilter = new standardfilter('username', get_string('username'));
        $table->add_filter($standardfilter);

        $hierarchicalfilter = new hierarchicalfilter('firstname', get_string('firstname'));

        $hierarchicalfilter->add_options(
            [
                'Anna' => [
                    'parent' => 'A',
                    'localizedname' => 'Anna localized',
                ],
                'Billy' => [
                    'parent' => 'B',
                ],
                'Leon' => [
                    'parent' => 'L',
                ],
                'other' => [
                    'localizedname' => get_string('other', 'local_wunderbyte_table'),
                ]
        ]);
        $table->add_filter($hierarchicalfilter);

        $standardfilter = new standardfilter('lastname', get_string('lastname'));
        //$table->add_filter($standardfilter);

        $standardfilter = new standardfilter('email', get_string('email'));
        $table->add_filter($standardfilter);

        // To test this explode filter, add values separeted with "," to the department field in users table. 1 & 2 will be translated as defined below.
        $standardfilter = new standardfilter('department', get_string('department'));
        $standardfilter->add_options(
            [
            'explode' => ',',
            '1' => 'first department',
            '2' => 'second department',
        ]);
        $table->add_filter($standardfilter);

        $hourslistfilter = new hourlist('timemodified', get_string('hourlastmodified', 'local_wunderbyte_table'));
        $table->add_filter($hourslistfilter);

        $table->define_fulltextsearchcolumns(['username', 'firstname', 'lastname']);
        $table->define_sortablecolumns(['id', 'username', 'firstname', 'lastname', 'email']);

        // When true and action buttons are present, checkboxes will be rendered to every line / record.
        $table->addcheckboxes = true;
        // $table->sortablerows = true;

        // Add action buttons to bottom of table. Demo of all defined types.
        // Define if it triggers a modal, if records need to be selected
        // and if a single call for all records or multiple calls (one for each selected record) are triggered.
        $table->actionbuttons[] = [
            'label' => get_string('nmmcns', 'local_wunderbyte_table'), // 'NoModal, MultipleCall, NoSelection'-> Name of your action button.
            'class' => 'btn btn-success', // Example colors bootstrap 4 classes.
            'href' => '#',
            'methodname' => 'deleteitem', // The method needs to be added to your child of wunderbyte_table class.
            'nomodal' => true, // If set to true, there is no modal and the method will be called directly.
            'selectionmandatory' => false, // When set to true, action will only be triggered, if elements are selected.
            'data' => [ // Will be added eg as data-id = $values->id, so values can be transmitted to the method above.
                'id' => 'id',
                'titlestring' => 'deletedatatitle', // Will be shown in modal title
                'bodystring' => 'deletedatabody', // Will be shown in modal body in case elements are selected
                'labelcolumn' => 'firstname', // The Labelcolumn is important because it will be picked for human verification in the modal.
                'noselectionbodystring' => 'specialbody', // Will be displayed in modal body in case no data is selected
                'submitbuttonstring' => 'deletedatasubmit', // Modal Button String
                'component' => 'local_wunderbyte_table', // Localization of strings
            ]
        ];
        $table->actionbuttons[] = [
            'label' => get_string('nmscns', 'local_wunderbyte_table'), // 'NoModal, SingleCall, NoSelection'
            'class' => 'btn btn-primary',
            'href' => '#',
            'id' => -1, // This forces single call execution.
            //'formname' => '', // To open dynamic form, instead of just confirmation modal.
            'methodname' => 'additem',
            'nomodal' => true,
            'selectionmandatory' => false,
            'data' => [ // Will be added eg as data-id = $values->id, so values can be transmitted to the method above.
                'id' => 'id',
                // 'title' => get_string('title'), Localized title to be displayed as title in dynamic form (formname).
            ],
        ];
        $table->actionbuttons[] = [
            'label' => get_string('ymmcns', 'local_wunderbyte_table'),// '+Modal, MultipleCall, NoSelection'
            'class' => 'btn btn-danger',
            'href' => '#',
            'methodname' => 'deleteitem',
            // 'formname' => 'local_myplugin\\form\\edit_mytableentry', // To include a dynamic form to open and edit entry in modal.
            'nomodal' => false,
            'selectionmandatory' => false,
            'data' => [
                // 'title' => get_string('title'), Localized title to be displayed as title in dynamic form (formname).
                'id' => 'id',
                'titlestring' => 'deletedatatitle',
                'bodystring' => 'deletedatabody',
                'submitbuttonstring' => 'deletedatasubmit',
                'component' => 'local_wunderbyte_table',
                'labelcolumn' => 'firstname',
                'noselectionbodystring' => 'specialbody',
            ]
        ];

        $table->actionbuttons[] = [
            'label' => get_string('ymscns', 'local_wunderbyte_table'), // '+Modal, SingleCall, NoSelection'
            'class' => 'btn btn-warning',
            'href' => '#',
            'methodname' => 'additem',
            // 'formname' => 'local_myplugin\\form\\edit_mytableentry', // To include a dynamic form to open and edit entry in modal.
            'nomodal' => false,
            'id' => -1,
            'selectionmandatory' => false,
            'data' => [
                'id' => 'id',
                // 'title' => get_string('title'), Localized title to be displayed as title in dynamic form (formname).
                'titlestring' => 'deletedatatitle',
                'bodystring' => 'adddatabody',
                'submitbuttonstring' => 'deletedatasubmit',
                'component' => 'local_wunderbyte_table',
                'labelcolumn' => 'firstname',
                'noselectionbodystring' => 'adddatabody',
            ]
        ];
        // This is for calling a form.
        // When id is set to -1, the key checkedids will hold comma separated string with the ids.
        $table->actionbuttons[] = [
            'label' => 'myform', // '+Modal, SingleCall, NoSelection'
            'class' => 'btn btn-warning',
            'href' => '#',
            // 'methodname' => 'additem',
            'formname' => 'local_wunderbyte_table\\form\\demoform', // To include a dynamic form to open and edit entry in modal.
            'nomodal' => false,
            'id' => -1,
            'selectionmandatory' => false,
            'data' => [
                'id' => 'id',
                // 'title' => get_string('title'), Localized title to be displayed as title in dynamic form (formname).
                'titlestring' => 'deletedatatitle',
                'bodystring' => 'adddatabody',
                'submitbuttonstring' => 'deletedatasubmit',
                'component' => 'local_wunderbyte_table',
                'labelcolumn' => 'firstname',
                'noselectionbodystring' => 'adddatabody',
            ]
        ];

        $table->actionbuttons[] = [
            'label' => get_string('nmmcys', 'local_wunderbyte_table'), // 'NoModal, MultipleCall, Selection'
            'class' => 'btn btn-success',
            'href' => '#',
            'methodname' => 'deleteitem',
            // 'formname' => 'local_myplugin\\form\\edit_mytableentry', // To include a dynamic form to open and edit entry in modal.
            'nomodal' => true,
            'selectionmandatory' => true,
            'data' => [
                'id' => 'id',
                'titlestring' => 'deletedatatitle',
                // 'title' => get_string('title'), Localized title to be displayed as title in dynamic form (formname).
                'bodystring' => 'deletedatabody',
                'submitbuttonstring' => 'deletedatasubmit',
                'component' => 'local_wunderbyte_table',
                'labelcolumn' => 'firstname',
            ]
        ];
        $table->actionbuttons[] = [
            'label' => get_string('nmscys', 'local_wunderbyte_table'), // 'NoModal, SingleCall, Selection'
            'class' => 'btn btn-primary',
            'href' => '#',
            'id' => -1,
            'formname' => '', // To open dynamic form, instead of just confirmation modal.
            'methodname' => 'additem',
            'nomodal' => true,
            'selectionmandatory' => true,
            'data' => [
                'id' => 'id',
            ],
        ];
        $table->actionbuttons[] = [
            'label' => get_string('ymmcys', 'local_wunderbyte_table'), // '+Modal, MultipleCall, Selection',
            'class' => 'btn btn-danger',
            'href' => '#',
            'methodname' => 'deleteitem',
            // 'formname' => 'local_myplugin\\form\\edit_mytableentry', // To include a dynamic form to open and edit entry in modal.
            'nomodal' => false,
            'selectionmandatory' => true,
            'data' => [
                'id' => 'id',
                'titlestring' => 'deletedatatitle',
                // 'title' => get_string('title'), Localized title to be displayed as title in dynamic form (formname).
                'bodystring' => 'deletedatabody',
                'submitbuttonstring' => 'deletedatasubmit',
                'component' => 'local_wunderbyte_table',
                'labelcolumn' => 'firstname',
            ]
        ];

        $table->actionbuttons[] = [
            'label' => get_string('ymscys', 'local_wunderbyte_table'), // '+Modal, SingleCall, Selection'.
            'class' => 'btn btn-warning',
            'href' => '#',
            'methodname' => 'deleteitem',
            // 'formname' => 'local_myplugin\\form\\edit_mytableentry', // To include a dynamic form to open and edit entry in modal.
            'nomodal' => false,
            'selectionmandatory' => true,
            'id' => -1,
            'data' => [
                'id' => 'id',
                'titlestring' => 'deletedatatitle',
                'bodystring' => 'deletedatabody',
                // 'title' => get_string('title'), Localized title to be displayed as title in dynamic form (formname).
                'submitbuttonstring' => 'deletedatasubmit',
                'component' => 'local_wunderbyte_table',
                'labelcolumn' => 'firstname',
            ]
        ];

        // Way to sort by default for more than one columns.
        // $table->set_sortdata([
        //     [
        //         'sortby' => 'username',
        //         'sortorder' => wunderbyte_table::SORTORDER_ASC,
        //     ],
        //     [
        //         'sortby' => 'firstname',
        //         'sortorder' => wunderbyte_table::SORTORDER_DESC,
        //     ],
        // ]);
        $table->sort_default_column = 'username';
        $table->sort_default_order = SORT_ASC; // Or SORT_DESC.

        // Work out the sql for the table.
        $table->set_filter_sql('*', "(SELECT * FROM {user} ORDER BY id ASC LIMIT 112 ) as s1", '1=1', '');

        $table->cardsort = true;

        // $table->tabletemplate = 'local_wunderbyte_table/twtable_list';

        $table->pageable(true);

        $table->gotopage = true;

        $table->stickyheader = true;
        $table->showcountlabel = true;
        // $table->showfilterontop = true;
        // $table->showdownloadbuttonatbottom = true;
        $table->showdownloadbutton = true;
        $table->showreloadbutton = true;
        $table->showrowcountselect = true;
        //$table->filteronloadinactive = true; // By default, filter will be displayed next to table. Set filteronloadinactive true, if you want them to be hidden on load.

        return $table->outhtml(10, true);
    }

    /**
     * Renders demo table 2. With records including timecode, demo of filter for time and timespan.
     *
     * @return demo_table
     *
     */
    private function render_table_2() {
        // The $uniqueid Should be composed by ASCII alphanumeric characters, underlines and spaces only!
        // It is recommended to avoid of usage of simple single words like "table" to reduce chance of affecting by Moodle`s core CSS
        $table = new demo_table('demotable_2');

        $columns = [
            'id' => get_string('id', 'local_wunderbyte_table'),
            'fullname' => get_string('fullname'),
            'shortname' => get_string('shortname'),
            'action' => get_string('action'),
            'startdate' => get_string('startdate'),
            'enddate' => get_string('enddate'),
            'timecreated' => get_string('timecreated'),
        ];

        $standardfilter = new standardfilter('fullname', get_string('fullname'));
        $table->add_filter($standardfilter);

        $standardfilter = new standardfilter('shortname', get_string('shortname'));
        $table->add_filter($standardfilter);

        $datepicker = new datepicker('enddate', get_string('enddate'));
        // For the datepicker, we need to add special options.
        $datepicker->add_options(
            'standard',
            '<',
            get_string('apply_filter', 'local_wunderbyte_table'),
            'now',
        );
        $table->add_filter($datepicker);

        $datepicker = new datepicker(
            'startdate',
            get_string('timespan', 'local_wunderbyte_table'),
            'enddate'
        );
        // For the datepicker, we need to add special options.
        $datepicker->add_options(
            'in between',
            '<',
            get_string('apply_filter', 'local_wunderbyte_table'),
            '1680130800',
            'now'
        );
        $table->add_filter($datepicker);

        $hourslistfilter = new hourlist('timecreated', "timecreated");
        $table->add_filter($hourslistfilter);

        $table->define_headers(array_values($columns));
        $table->define_columns(array_keys($columns));

        $table->define_fulltextsearchcolumns(['fullname', 'shortname']);
        $table->define_sortablecolumns($columns);

        // When true and action buttons are present, checkboxes will be rendered to every line.
        $table->addcheckboxes = true;

        $table->actionbuttons[] = [
            'label' => get_string('add', 'core'), // Name of your action button.
            'class' => 'btn btn-primary',
            'href' => '#',
            'id' => -1, // This forces single call execution.
            'formname' => '', // To open dynamic form, instead of just confirmation modal.
            'methodname' => 'additem', // The method needs to be added to your child of wunderbyte_table class.
            'nomodal' => true, // If set to true, there is no modal but the method will be called directly.
            'data' => [ // Will be added eg as data-id = $values->id, so values can be transmitted to the method above.
                'id' => 'id',
            ],
        ];

        $table->actionbuttons[] = [
            'label' => get_string('delete', 'core'), // Name of your action button.
            'class' => 'btn btn-danger',
            'href' => '#',
            'methodname' => 'deleteitem', // The method needs to be added to your child of wunderbyte_table class.
            // 'formname' => 'local_myplugin\\form\\edit_mytableentry', // To include a dynamic form to open and edit entry in modal.
            'nomodal' => false,
            'data' => [ // Will be added eg as data-id = $values->id, so values can be transmitted to the method above.
                'id' => 'id',
                'titlestring' => 'deletedatatitle',
                'bodystring' => 'deletedatabody',
                'submitbuttonstring' => 'deletedatasubmit',
                'component' => 'local_wunderbyte_table',
                'labelcolumn' => 'fullname', // The Labelcolumn is important because it will be picked for human verification in the modal.
            ]
        ];

        $table->sort_default_column = 'fullname';

        $table->set_filter_sql('*', "(SELECT * FROM {course} ORDER BY id ASC LIMIT 112) as s1", '1=1', '');

        $table->cardsort = true;

        $table->tabletemplate = 'local_wunderbyte_table/twtable_list';

        $table->pageable(true);

        $table->stickyheader = false;
        $table->showcountlabel = true;
        $table->showdownloadbutton = true;
        $table->showreloadbutton = true;
        $table->showrowcountselect = true;
        $table->filteronloadinactive = true;

        return $table->outhtml(10, true);
    }

    /**
     * Renders demo table 3. A lazy load table with sticky header and infinitescroll.
     *
     * @return demo_table
     *
     */
    private function render_table_3() {
        // The $uniqueid Should be composed by ASCII alphanumeric characters, underlines and spaces only!
        // It is recommended to avoid of usage of simple single words like "table" to reduce chance of affecting by Moodle`s core CSS
        $table = new demo_table('demotable_3');

        $columns = [
            'id' => get_string('id', 'local_wunderbyte_table'),
            'course' => get_string('course'),
            'module' => get_string('module', 'local_wunderbyte_table'),
            'idnumber' => get_string('idnumber'),
            'added' => get_string('timecreated'),
            'action' => get_string('action'),
        ];

        $table->define_headers(array_values($columns));
        $table->define_columns(array_keys($columns));

        $filtercolumns = [];

        $standardfilter = new standardfilter('course', get_string('course'));
        $table->add_filter($standardfilter);
        $standardfilter = new standardfilter('module',  get_string('module', 'local_wunderbyte_table'));
        $table->add_filter($standardfilter);
        $standardfilter = new standardfilter('idnumber',  get_string('idnumber', 'local_wunderbyte_table'));
        $table->add_filter($standardfilter);
        $hourslistfilter = new hourlist('added', "Added");
        $table->add_filter($hourslistfilter);

        //$table->define_fulltextsearchcolumns(array_keys($filtercolumns));
        $table->define_sortablecolumns(['id', 'course', 'module', 'idnumber']);

        // When true and action buttons are present, checkboxes will be rendered to every line.
        $table->addcheckboxes = true;
        $table->actionbuttons[] = [
            'label' => get_string('add'), // Name of your action button.
            'class' => 'btn btn-primary',
            'href' => '#',
            'id' => -1, // This forces single call execution.
            //'formname' => '', // To open dynamic form, instead of just confirmation modal.
            'methodname' => 'additem', // The method needs to be added to your child of wunderbyte_table class.
            'nomodal' => false, // If set to true, there is no modal but the method will be called directly.
            'data' => [ // Will be added eg as data-id = $values->id, so values can be transmitted to the method above.
                'id' => 'id',
            ],
        ];

        $table->actionbuttons[] = [
            'label' => get_string('delete'), // Name of your action button.
            'class' => 'btn btn-danger',
            'href' => '#',
            'methodname' => 'deleteitem', // The method needs to be added to your child of wunderbyte_table class.
            // 'formname' => 'local_myplugin\\form\\edit_mytableentry', // To include a dynamic form to open and edit entry in modal.
            'nomodal' => false,
            'selectionmandatory' => true,
            'data' => [ // Will be added eg as data-id = $values->id, so values can be transmitted to the method above.
                'id' => 'id',
                'titlestring' => 'deletedatatitle',
                'bodystring' => 'deletedatabody',
                'submitbuttonstring' => 'deletedatasubmit',
                'component' => 'local_wunderbyte_table',
                'labelcolumn' => 'firstname', // The Labelcolumn is important because it will be picked for human verification in the modal.
            ]
        ];

        $table->actionbuttons[] = [
            'label' => get_string('change', 'local_wunderbyte_table'), // Name of your action button.
            'class' => 'btn btn-warning',
            'href' => '#',
            'methodname' => 'deleteitem', // The method needs to be added to your child of wunderbyte_table class.
            // 'formname' => 'local_myplugin\\form\\edit_mytableentry', // To include a dynamic form to open and edit entry in modal.
            'nomodal' => false,
            'selectionmandatory' => true,
            'id' => -1, // Single Call execution
            'data' => [ // Will be added eg as data-id = $values->id, so values can be transmitted to the method above.
                'id' => 'id',
                'titlestring' => 'deletedatatitle',
                'bodystring' => 'deletedatabody',
                'submitbuttonstring' => 'deletedatasubmit',
                'component' => 'local_wunderbyte_table',
                'labelcolumn' => 'firstname', // The Labelcolumn is important because it will be picked for human verification in the modal.
            ]
        ];

        $table->sort_default_column = 'id';

        // Work out the sql for the table.
        $table->set_filter_sql('*', "(SELECT * FROM {course_modules} ORDER BY id ASC LIMIT 112) as s1", '1=1', '');

        $table->cardsort = true;

        $table->tabletemplate = 'local_wunderbyte_table/twtable_list';

        $table->pageable(true);

        $table->infinitescroll = 10; // Triggering reload of records when scrolling to bottom of table. Define the number of records being loaded.
        $table->stickyheader = false;
        $table->showcountlabel = true;
        $table->showdownloadbutton = true;
        $table->showreloadbutton = true;
        $table->showrowcountselect = true;
        // $table->scrolltocontainer = false;

        // $table->hide_filter();

        // To lazy load wunderbyte table (eg. for loading in tabs or modals)
        // you need to call $table->lazyout() instead of $table->out.
        // While out will return the html to echo, lazyout echos right away.
        list($idstring, $encodedtable, $html) = $table->lazyouthtml(10, true);

        return $html;

    }

    /**
     * Renders demo table 4. Table with infinite scroll, triggering reload of records when scrolling to bottom of table.
     *
     * @return demo_table
     *
     */
    private function render_table_4() {
        // The $uniqueid Should be composed by ASCII alphanumeric characters, underlines and spaces only!
        // It is recommended to avoid of usage of simple single words like "table" to reduce chance of affecting by Moodle`s core CSS
        $table = new demo_table('demotable_4');

        $table->define_headers(['id', 'username', 'firstname', 'lastname', 'email', 'action', 'timecreated', 'timemodified']);
        $table->define_columns(['id', 'username', 'firstname', 'lastname', 'email', 'action', 'timecreated', 'timemodified']);

        $standardfilter = new standardfilter('firstname',  get_string('firstname'));
        $table->add_filter($standardfilter);
        $standardfilter = new standardfilter('lastname',  get_string('lastname'));
        $table->add_filter($standardfilter);
        $standardfilter = new standardfilter('email', get_string('email'));
        $table->add_filter($standardfilter);
        $intrangefilter = new intrange('username', "Range of numbers given in Username");
        $table->add_filter($intrangefilter);
        $weekdaysfilter = new weekdays(
            'timecreated',
            get_string('timecreated'),
            'timemodified',
            get_string('modified')
        );
        $table->add_filter($weekdaysfilter);

        //$table->define_fulltextsearchcolumns(['username', 'firstname', 'lastname']);
        $table->define_sortablecolumns(['id', 'username', 'firstname', 'lastname']);

        $table->addcheckboxes = true;

        $table->actionbuttons[] = [
            'label' => get_string('add'), // Name of your action button.
            'class' => 'btn btn-primary',
            'href' => '#',
            'id' => -1, // This forces single call execution.
            //'formname' => '', // To open dynamic form, instead of just confirmation modal.
            'methodname' => 'additem', // The method needs to be added to your child of wunderbyte_table class.
            'nomodal' => false, // If set to true, there is no modal but the method will be called directly.
            'data' => [ // Will be added eg as data-id = $values->id, so values can be transmitted to the method above.
                'id' => 'id',
            ],
        ];

        $table->actionbuttons[] = [
            'label' => get_string('delete'), // Name of your action button.
            'class' => 'btn btn-danger',
            'href' => '#',
            'methodname' => 'deleteitem', // The method needs to be added to your child of wunderbyte_table class.
            // 'formname' => 'local_myplugin\\form\\edit_mytableentry', // To include a dynamic form to open and edit entry in modal.
            'nomodal' => false,
            'selectionmandatory' => true,
            'data' => [ // Will be added eg as data-id = $values->id, so values can be transmitted to the method above.
                'id' => 'id',
                'titlestring' => 'deletedatatitle',
                'bodystring' => 'deletedatabody',
                'submitbuttonstring' => 'deletedatasubmit',
                'component' => 'local_wunderbyte_table',
                'labelcolumn' => 'firstname', // The Labelcolumn is important because it will be picked for human verification in the modal.
            ]
        ];

        $table->actionbuttons[] = [
            'label' => get_string('change', 'local_wunderbyte_table'), // Name of your action button.
            'class' => 'btn btn-warning',
            'href' => '#',
            'methodname' => 'deleteitem', // The method needs to be added to your child of wunderbyte_table class.
            // 'formname' => 'local_myplugin\\form\\edit_mytableentry', // To include a dynamic form to open and edit entry in modal.
            'nomodal' => false,
            'selectionmandatory' => true,
            'id' => -1, // Single Call execution
            'data' => [ // Will be added eg as data-id = $values->id, so values can be transmitted to the method above.
                'id' => 'id',
                'titlestring' => 'deletedatatitle',
                'bodystring' => 'deletedatabody',
                'submitbuttonstring' => 'deletedatasubmit',
                'component' => 'local_wunderbyte_table',
                'labelcolumn' => 'firstname', // The Labelcolumn is important because it will be picked for human verification in the modal.
            ]
        ];


        $table->sort_default_column = 'username';

        // Work out the sql for the table.
        $table->set_filter_sql('*', "(SELECT * FROM {user} ORDER BY id ASC LIMIT 112) as s1", '1=1', '');

        $table->cardsort = true;

        $table->tabletemplate = 'local_wunderbyte_table/twtable_list';

        $table->pageable(true);

        $table->infinitescroll = 7; // Triggering reload of records when scrolling to bottom of table. Define the number of records being loaded.
        $table->stickyheader = false;
        $table->showcountlabel = true;
        $table->showdownloadbutton = true;
        $table->showreloadbutton = true;
        $table->showrowcountselect = false;
        $table->filteronloadinactive = true;

        return $table->outhtml(10, true);
    }


    /**
     * Render data for use in template without need of renderer_base
     *
     * @return array
     */
    public function return_as_array():array {
        // $data = [
        //     'table1' => $this->render_table_1(),
        //     'tab1_name' => TABLE1NAME,
        // ];
        $data = [
            'table1' => $this->render_table_1(),
            'tab1_name' => TABLE1NAME,
            'table2' => $this->render_table_2(),
            'tab2_name' => TABLE2NAME,
            'table3' => $this->render_table_3(),
            'tab3_name' => TABLE3NAME,
            'table4' => $this->render_table_4(),
            'tab4_name' => TABLE4NAME,
        ];

        return $data;
    }

    /**
     * Prepare data for use in a template
     *
     * @param renderer_base $output
     * @return array
     */
    public function export_for_template(renderer_base $output) {

        return $this->return_as_array();
    }
}
