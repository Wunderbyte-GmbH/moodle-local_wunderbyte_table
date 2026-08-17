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

namespace local_wunderbyte_table\local\pdf;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/pdflib.php');

/**
 * PDF/A-2b variant of Moodle's TCPDF wrapper: PDF/A is enabled from the start.
 *
 * Use it wherever a document has to be PDF/A without changing an existing class
 * hierarchy; classes that must keep their own parent (e.g. plain TCPDF) use
 * {@see pdfa_trait} directly and call enable_pdfa() themselves.
 *
 * @package    local_wunderbyte_table
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class pdfa_pdf extends \pdf {
    use pdfa_trait;

    /**
     * PDF/A part written into the XMP metadata (2 = PDF/A-2b, 3 = PDF/A-3b).
     */
    public const PDFA_PART = 2;

    /**
     * Class constructor - same signature as Moodle's pdf class.
     *
     * @param string $orientation page orientation (P or L)
     * @param string $unit user measure unit (pt, mm, cm, in)
     * @param mixed $format page format (e.g. A4) or array with width and height
     * @param bool $unicode true if the input text is unicode
     * @param string $encoding charset encoding
     */
    public function __construct($orientation = 'P', $unit = 'mm', $format = 'A4', $unicode = true, $encoding = 'UTF-8') {
        parent::__construct($orientation, $unit, $format, $unicode, $encoding);
        $this->enable_pdfa(static::PDFA_PART);
    }
}
