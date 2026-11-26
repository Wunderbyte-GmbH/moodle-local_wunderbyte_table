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
 * Test table that arranges its columns differently for each template.
 *
 * @package local_wunderbyte_table
 * @category test
 * @copyright 2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_wunderbyte_table;

/**
 * Test table that arranges its columns differently for each template.
 *
 * @package local_wunderbyte_table
 * @category test
 * @copyright 2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class template_layout_table extends demo_table {
    /** @var string The list template, which is the default of the test tables. */
    public const LIST = 'local_wunderbyte_table/twtable_list';

    /** @var string The cards template. */
    public const CARDS = 'local_wunderbyte_table/twtable_cards';

    /**
     * The cards template gets the full name in the card body, the list template on the left side.
     *
     * @param string $template
     * @param int $viewparam
     * @return void
     */
    public function apply_template_layout(string $template, int $viewparam): void {
        $this->subcolumns = [];
        $this->add_subcolumns($template === self::CARDS ? 'cardbody' : 'leftside', ['fullname'], false);
    }
}
