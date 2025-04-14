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

namespace local_wunderbyte_table\form;

defined('MOODLE_INTERNAL') || die();

global $CFG;

use context;
use context_system;
use core_form\dynamic_form;
use local_wunderbyte_table\filters\filters_info;
use local_wunderbyte_table\local\settings\tablesettings;
use local_wunderbyte_table\wunderbyte_table;
use moodle_url;
use stdClass;

/**
 * Dynamic edit table form.
 * @copyright   2024 Wunderbyte GmbH <info@wunderbyte.at>
 * @author      Georg Maißer
 * @package     local_wunderbyte_table
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class edittable extends dynamic_form {
    /**
     * {@inheritdoc}
     * @see moodleform::definition()
     */
    public function definition() {

        $mform = $this->_form;

        $customdata = $this->_customdata;
        $ajaxformdata = $this->_ajaxformdata;

        $data = $ajaxformdata ?? $customdata;

        // If we open an existing campaign, we need to save the id right away.
        if (!empty($ajaxformdata['id'])) {
            $mform->addElement('hidden', 'id', $ajaxformdata['id']);
        }

        $mform->addElement(
            'header',
            'wbtablefiltersettingsheader',
            get_string('wbtablefiltersettingsheader', 'local_wunderbyte_table')
        );
        filters_info::defintion($mform, $data);

        $mform->addElement(
            'header',
            'wbtabletablesettingsheader',
            get_string('wbtabletablesettingsheader', 'local_wunderbyte_table')
        );
        tablesettings::definition($mform, (array)$data);
    }

    /**
     * Definition after data
     *
     * @return void
     *
     */
    public function definition_after_data() {
        $mform = $this->_form;
        $customdata = $this->_customdata;
        $ajaxformdata = $this->_ajaxformdata;
    }

    /**
     * Process data for dynamic submission
     * @return object $data
     */
    public function process_dynamic_submission() {
        $data = parent::get_data();

        $newdata = new stdClass();

        filters_info::process_data($data, $newdata);

        tablesettings::process_data($data, $newdata);

        return $newdata;
    }

    /**
     * Set data for dynamic submission.
     * @return void
     */
    public function set_data_for_dynamic_submission(): void {

        $data = (object)$this->_ajaxformdata;

        $encodedtable = $data->encodedtable;
        if (empty($encodedtable)) {
            // Do nothing if table is missing.
            return;
        }
        $table = wunderbyte_table::instantiate_from_tablecache_hash($encodedtable);

        filters_info::set_data($data, $table);
        tablesettings::set_data($data, $table);
        $this->set_data($data);
    }

    /**
     * Campaings validation.
     *
     * @param array $data
     * @param array $files
     * @return array
     *
     */
    public function validation($data, $files) {
        $errors = [];

        if (!is_number($data['gs_wb_pagesize'])) {
            $errors['gs_wb_pagesize'] = get_string('valuehastobeint', 'local_wunderbyte_table');
        }

        if (!is_number($data['gs_wb_infinitescroll'])) {
            $errors['gs_wb_infinitescroll'] = get_string('valuehastobeint', 'local_wunderbyte_table');
        }

        return $errors;
    }


    /**
     * Get page URL for dynamic submission.
     * @return moodle_url
     */
    protected function get_page_url_for_dynamic_submission(): moodle_url {
        return new moodle_url('/local/wunderbyte_table/demo.php');
    }

    /**
     * Get context for dynamic submission.
     * @return context
     */
    protected function get_context_for_dynamic_submission(): context {
        return context_system::instance();
    }

    /**
     * Check access for dynamic submission.
     * @return void
     */
    protected function check_access_for_dynamic_submission(): void {
        // Perhaps we will need a specific campaigns capability.
        require_capability('local/wunderbyte_table:canedittable', context_system::instance());
    }
}
