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

namespace local_wunderbyte_table\output;

use local_wunderbyte_table\demo_table;
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
     * @param string $idstring
     * @param string $encodedtable
     */
    public function __construct() {

    }

    private function render_table_1() {

        $table = new demo_table(TABLE1NAME);

        // $table->add_subcolumns('cardbody', ['id', 'username', 'firstname', 'lastname', 'email']);
        $table->define_headers(['id', 'username', 'firstname', 'lastname', 'email', 'action']);
        $table->define_columns(['id', 'username', 'firstname', 'lastname', 'email', 'action']);

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

        $table->define_filtercolumns(['id', 'username', 'firstname', 'lastname', 'email']);
        $table->define_fulltextsearchcolumns(['username', 'firstname', 'lastname']);
        $table->define_sortablecolumns(['id', 'username', 'firstname', 'lastname']);

        // When true and action buttons are present, checkboxes will be rendered to every line.
        $table->addcheckboxes = true;

        $table->actionbuttons[] = [
            'label' => 'NoModal, MultipleCall, NoSelection', // Name of your action button.
            'class' => 'btn btn-success',
            'href' => '#',
            'methodname' => 'deleteitem', // The method needs to be added to your child of wunderbyte_table class.
            // 'formname' => 'local_myplugin\\form\\edit_mytableentry', // To include a dynamic form to open and edit entry in modal.
            'nomodal' => true,
            'selectionmandatory' => false, // When set to true, action will only be triggered, if elements are selected.
            'data' => [ // Will be added eg as data-id = $values->id, so values can be transmitted to the method above.
                'id' => 'id',
                'titlestring' => 'deletedatatitle', // Will be shown in modal title
                'bodystring' => 'deletedatabody', // Will be shown in modal body in case elements are selected
                'labelcolumn' => 'firstname', // The Labelcolumn is important because it will be picked for human verification in the modal.
                'noselectionbodystring' => 'specialbody', // Will be displayed in modal bode in case no data is selected
                'submitbuttonstring' => 'deletedatasubmit', // Modal Button String
                'component' => 'local_wunderbyte_table', // Localization of strings
            ]
        ];
        $table->actionbuttons[] = [
            'label' => 'NoModal, SingleCall, NoSelection', // Name of your action button.
            'class' => 'btn btn-primary',
            'href' => '#',
            'id' => -1, // This forces single call execution.
            'formclass' => '', // To open dynamic form, instead of just confirmation modal.
            'methodname' => 'additem', // The method needs to be added to your child of wunderbyte_table class.
            'nomodal' => true, // If set to true, there is no modal and the method will be called directly.
            'selectionmandatory' => false,
            'data' => [ // Will be added eg as data-id = $values->id, so values can be transmitted to the method above.
                'id' => 'id',
            ],
        ];
        $table->actionbuttons[] = [
            'label' => '+Modal, MultipleCall, NoSelection', // Name of your action button.
            'class' => 'btn btn-danger',
            'href' => '#',
            'methodname' => 'deleteitem', // The method needs to be added to your child of wunderbyte_table class.
            // 'formname' => 'local_myplugin\\form\\edit_mytableentry', // To include a dynamic form to open and edit entry in modal.
            'nomodal' => false,
            'selectionmandatory' => false, // You might want to add a 'noselectionbodystring' to data which will be shown in the modal in case there are no elements selected.
            'data' => [ // Will be added eg as data-id = $values->id, so values can be transmitted to the method above.
                'id' => 'id',
                'titlestring' => 'deletedatatitle',
                'bodystring' => 'deletedatabody',
                'submitbuttonstring' => 'deletedatasubmit',
                'component' => 'local_wunderbyte_table',
                'labelcolumn' => 'firstname', // The Labelcolumn is important because it will be picked for human verification in the modal.
                'noselectionbodystring' => 'specialbody', // Will be applied in case no data is selected
            ]
        ];

        $table->actionbuttons[] = [
            'label' => '+Modal, SingleCall, NoSelection', // Name of your action button.
            'class' => 'btn btn-warning',
            'href' => '#',
            'methodname' => 'additem', // The method needs to be added to your child of wunderbyte_table class.
            // 'formname' => 'local_myplugin\\form\\edit_mytableentry', // To include a dynamic form to open and edit entry in modal.
            'nomodal' => false,
            'id' => -1,
            'selectionmandatory' => false,
            'data' => [ // Will be added eg as data-id = $values->id, so values can be transmitted to the method above.
                'id' => 'id',
                'titlestring' => 'deletedatatitle',
                'bodystring' => 'adddatabody',
                'submitbuttonstring' => 'deletedatasubmit',
                'component' => 'local_wunderbyte_table',
                'labelcolumn' => 'firstname', // The Labelcolumn is important because it will be picked for human verification in the modal.
                'noselectionbodystring' => 'adddatabody', // Will be applied in case no data is selected
            ]
        ];

        $table->actionbuttons[] = [
            'label' => 'NoModal, MultipleCall, Selection', // Name of your action button.
            'class' => 'btn btn-success',
            'href' => '#',
            'methodname' => 'deleteitem', // The method needs to be added to your child of wunderbyte_table class.
            // 'formname' => 'local_myplugin\\form\\edit_mytableentry', // To include a dynamic form to open and edit entry in modal.
            'nomodal' => true,
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
            'label' => 'NoModal, SingleCall, Selection', // Name of your action button.
            'class' => 'btn btn-primary',
            'href' => '#',
            'id' => -1, // This forces single call execution.
            'formclass' => '', // To open dynamic form, instead of just confirmation modal.
            'methodname' => 'additem', // The method needs to be added to your child of wunderbyte_table class.
            'nomodal' => true, // If set to true, there is no modal but the method will be called directly.
            'selectionmandatory' => true,
            'data' => [ // Will be added eg as data-id = $values->id, so values can be transmitted to the method above.
                'id' => 'id',
            ],
        ];
        $table->actionbuttons[] = [
            'label' => '+Modal, MultipleCall, Selection', // Name of your action button.
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
            'label' => '+Modal, SingleCall, Selection', // Name of your action button.
            'class' => 'btn btn-warning',
            'href' => '#',
            'methodname' => 'deleteitem', // The method needs to be added to your child of wunderbyte_table class.
            // 'formname' => 'local_myplugin\\form\\edit_mytableentry', // To include a dynamic form to open and edit entry in modal.
            'nomodal' => false,
            'selectionmandatory' => true,
            'id' => -1,
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
        $table->set_filter_sql('*', "{user}", '1=1', '');

        $table->cardsort = true;

        $table->tabletemplate = 'local_wunderbyte_table/twtable_list';

        //$table->infinitescroll = 20;
        $table->pageable(true);

        $table->stickyheader = true;
        $table->showcountlabel = true;
        $table->showdownloadbutton = true;
        $table->showreloadbutton = true;
        $table->showrowcountselect = true;


        return $table->outhtml(10, true);
    }

    private function render_table_2() {

        $table = new demo_table(TABLE2NAME);

        $columns = [
            'id' => get_string('id', 'local_wunderbyte_table'),
            'fullname' => get_string('fullname', 'local_wunderbyte_table'),
            'shortname' => get_string('shortname', 'local_wunderbyte_table'),
            'action' => get_string('action', 'local_wunderbyte_table')];

        $filtercolumns = [
            'id' => get_string('id', 'local_wunderbyte_table'),
            'fullname' => get_string('fullname', 'local_wunderbyte_table'),
            'shortname' => get_string('shortname', 'local_wunderbyte_table'),
        ];


        $table->define_headers(array_values($columns));
        $table->define_columns(array_keys($columns));

        $table->define_filtercolumns(array_keys($filtercolumns));
        $table->define_fulltextsearchcolumns(array_keys($filtercolumns));
        $table->define_sortablecolumns($filtercolumns);

        // When true and action buttons are present, checkboxes will be rendered to every line.
        $table->addcheckboxes = true;

        $table->actionbuttons[] = [
            'label' => get_string('add', 'core'), // Name of your action button.
            'class' => 'btn btn-primary',
            'href' => '#',
            'id' => -1, // This forces single call execution.
            'formclass' => '', // To open dynamic form, instead of just confirmation modal.
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
                'labelcolumn' => 'firstname', // The Labelcolumn is important because it will be picked for human verification in the modal.
            ]
        ];

        $table->sort_default_column = 'fullname';

        $table->set_filter_sql('*', "{course}", '1=1', '');

        $table->cardsort = true;

        $table->tabletemplate = 'local_wunderbyte_table/twtable_list';

        //$table->infinitescroll = 10;
        $table->pageable(true);

        $table->stickyheader = false;
        $table->showcountlabel = true;
        $table->showdownloadbutton = true;
        $table->showreloadbutton = true;
        $table->showrowcountselect = true;


        return $table->outhtml(10, true);
    }

    private function render_table_3() {

        $table = new demo_table(TABLE3NAME);

        $table->define_headers(['id', 'course', 'module', 'action']);
        $table->define_columns(['id', 'course', 'module', 'action']);

        $table->define_filtercolumns(['id', 'course', 'module']);
        $table->define_fulltextsearchcolumns(['id', 'course', 'module']);
        $table->define_sortablecolumns(['id', 'course', 'module']);

        // When true and action buttons are present, checkboxes will be rendered to every line.
        $table->addcheckboxes = true;

        $table->actionbuttons[] = [
            'label' => get_string('add', 'core'), // Name of your action button.
            'class' => 'btn btn-primary',
            'href' => '#',
            'id' => -1, // This forces single call execution.
            'formclass' => '', // To open dynamic form, instead of just confirmation modal.
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
                'labelcolumn' => 'firstname', // The Labelcolumn is important because it will be picked for human verification in the modal.
            ]
        ];

        $table->sort_default_column = 'id';

        // Work out the sql for the table.
        $table->set_filter_sql('*', "{course_modules}", '1=1', '');

        $table->cardsort = true;

        $table->tabletemplate = 'local_wunderbyte_table/twtable_list';

        $table->infinitescroll = 10;
        $table->pageable(true);

        $table->stickyheader = true;
        $table->showcountlabel = true;
        $table->showdownloadbutton = true;
        $table->showreloadbutton = true;
        $table->showrowcountselect = true;

        list($idstring, $encodedtable, $html) = $table->lazyouthtml(10, true);

        return $html;

    }

    private function render_table_4() {

        $table = new demo_table(TABLE4NAME);

        $table->define_headers(['id', 'username', 'firstname', 'lastname', 'email', 'action']);
        $table->define_columns(['id', 'username', 'firstname', 'lastname', 'email', 'action']);

        $table->define_filtercolumns(['id', 'username', 'firstname', 'lastname', 'email']);
        $table->define_fulltextsearchcolumns(['username', 'firstname', 'lastname']);
        $table->define_sortablecolumns(['id', 'username', 'firstname', 'lastname']);

        $table->addcheckboxes = true;

        $table->actionbuttons[] = [
            'label' => 'NoModal, SingleCall', // Name of your action button.
            'class' => 'btn btn-primary',
            'href' => '#',
            'id' => -1, // This forces single call execution.
            'formclass' => '', // To open dynamic form, instead of just confirmation modal.
            'methodname' => 'additem', // The method needs to be added to your child of wunderbyte_table class.
            'nomodal' => true, // If set to true, there is no modal but the method will be called directly.
            'data' => [ // Will be added eg as data-id = $values->id, so values can be transmitted to the method above.
                'id' => 'id',
            ],
        ];

        $table->actionbuttons[] = [
            'label' => 'Modal, MultipleCall', // Name of your action button.
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
                'labelcolumn' => 'firstname', // The Labelcolumn is important because it will be picked for human verification in the modal.
            ]
        ];

        $table->actionbuttons[] = [
            'label' => 'Modal, SingleCall', // Name of your action button.
            'class' => 'btn btn-warning',
            'href' => '#',
            'methodname' => 'deleteitem', // The method needs to be added to your child of wunderbyte_table class.
            // 'formname' => 'local_myplugin\\form\\edit_mytableentry', // To include a dynamic form to open and edit entry in modal.
            'nomodal' => false,
            'id' => -1,
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
        $table->set_filter_sql('*', "{user}", '1=1', '');

        $table->cardsort = true;

        $table->tabletemplate = 'local_wunderbyte_table/twtable_list';

        $table->infinitescroll = 7;
        $table->pageable(true);

        $table->stickyheader = false;
        $table->showcountlabel = true;
        $table->showdownloadbutton = true;
        $table->showreloadbutton = true;
        $table->showrowcountselect = true;


        return $table->outhtml(10, true);
    }


    /**
     * Render data for use in template without need of renderer_base
     *
     * @return array
     */
    public function return_as_array():array {
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
