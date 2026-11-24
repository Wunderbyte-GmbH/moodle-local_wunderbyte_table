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

namespace local_wunderbyte_table\local\exception;

use dml_read_exception;

/**
 * The database could not read the rows of a table (or count them) for a download.
 *
 * It is the read error of the database with a name of its own, so the script of a download can tell
 * it apart from read errors of other queries, e.g. the ones a column runs for every row. The query of
 * the table is the only one that reads all rows at once, so it is the only one that can fail because
 * of the amount of data. A script may react to that, e.g. by asking the user to narrow the table down.
 *
 * As a dml_read_exception it is shown and logged like the original error wherever nobody reacts to it.
 *
 * @package local_wunderbyte_table
 * @copyright 2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class download_query_exception extends dml_read_exception {
    /** @var dml_read_exception The read error as thrown by the database layer. */
    public dml_read_exception $original;

    /**
     * Constructor.
     *
     * @param dml_read_exception $original the read error as thrown by the database layer
     */
    public function __construct(dml_read_exception $original) {
        $this->original = $original;
        parent::__construct($original->error, $original->sql, $original->params);
    }
}
