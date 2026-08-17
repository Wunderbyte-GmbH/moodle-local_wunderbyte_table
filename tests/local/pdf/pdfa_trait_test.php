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
 * Tests for the opt-in PDF/A trait.
 *
 * @package local_wunderbyte_table
 * @category test
 * @copyright 2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_wunderbyte_table\local\pdf;

use advanced_testcase;
use TCPDF;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/pdflib.php');

/**
 * The trait must be inert on a plain TCPDF class until enable_pdfa() is called - plugins
 * rely on that to keep their existing PDF output when the PDF/A setting is off.
 *
 * @package local_wunderbyte_table
 * @category test
 * @copyright 2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \local_wunderbyte_table\local\pdf\pdfa_trait
 */
final class pdfa_trait_test extends advanced_testcase {
    /**
     * Sets up the test environment.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
        // Plain TCPDF writes data-URI images into K_PATH_CACHE and falls back to the system
        // temp dir (with a notice) when it does not exist; Moodle's pdf class creates it.
        make_cache_directory('tcpdf');
    }

    /**
     * A plain TCPDF subclass using the trait.
     *
     * @return TCPDF
     */
    private function create_tcpdf_with_trait(): TCPDF {
        return new class ('P', 'pt', 'A4', true, 'UTF-8', false) extends TCPDF {
            use pdfa_trait;
        };
    }

    /**
     * Writes the same content the shopping cart receipt writes: core font header, HTML with CSS fonts, image.
     *
     * @param TCPDF $pdf
     * @return string
     */
    private function write_content(TCPDF $pdf): string {
        $file = __DIR__ . '/../../fixtures/cmyk.jpg';
        $pdf->SetCreator('TCPDF');
        $pdf->SetTitle('trait test');
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->setFontSubsetting(true);
        $pdf->AddPage();
        $pdf->SetFont('helvetica', 'B', 20);
        $pdf->Cell(0, 20, 'Header in helvetica', 0, 1);
        $pdf->writeHTML(
            '<style>h1 { font-family: times; }</style><h1>Times heading</h1><p>Body äöü</p>'
            . '<img src="data:image/jpeg;base64,' . base64_encode(file_get_contents($file)) . '" width="60">',
            true,
            false,
            true,
            false,
            ''
        );
        $pdf->Image($file, 40, 400, 60, 0);
        return $pdf->Output('trait.pdf', 'S');
    }

    /**
     * Without enable_pdfa() the output is what plain TCPDF produces: no PDF/A markers,
     * unembedded core fonts, CMYK image untouched.
     */
    public function test_trait_is_inert_until_enabled(): void {
        $withtrait = $this->create_tcpdf_with_trait();
        $this->assertFalse($withtrait->is_pdfa());
        $pdf = $this->write_content($withtrait);

        $this->assertStringNotContainsString('pdfaid:part', $pdf);
        $this->assertStringNotContainsString('/OutputIntents', $pdf);
        $this->assertMatchesRegularExpression('#/BaseFont\s*/Helvetica-Bold#', $pdf);
        $this->assertMatchesRegularExpression('#/BaseFont\s*/Times-(Roman|Bold)#', $pdf);
        $this->assertStringContainsString('/DeviceCMYK', $pdf);

        // Structurally identical to a plain TCPDF document (object types and fonts).
        $plain = $this->write_content(new TCPDF('P', 'pt', 'A4', true, 'UTF-8', false));
        $this->assertSame($this->structure($plain), $this->structure($pdf));
    }

    /**
     * With enable_pdfa() the same content becomes PDF/A-2b.
     */
    public function test_enable_pdfa_switches_to_pdfa2b(): void {
        $withtrait = $this->create_tcpdf_with_trait();
        $withtrait->enable_pdfa();
        $this->assertTrue($withtrait->is_pdfa());
        $pdf = $this->write_content($withtrait);

        $this->assertStringStartsWith('%PDF-1.7', $pdf);
        $this->assertStringContainsString('<pdfaid:part>2</pdfaid:part>', $pdf);
        $this->assertStringContainsString('<pdfaid:conformance>B</pdfaid:conformance>', $pdf);
        $this->assertStringContainsString('/OutputIntents', $pdf);
        $this->assertDoesNotMatchRegularExpression('#/BaseFont\s*/(Helvetica|Times|Courier)#', $pdf);
        $this->assertMatchesRegularExpression('#/BaseFont\s*/[A-Z]{6}\+FreeSansBold#', $pdf);
        $this->assertMatchesRegularExpression('#/BaseFont\s*/[A-Z]{6}\+FreeSerif#', $pdf);
        $this->assertStringNotContainsString('/DeviceCMYK', $pdf);
        $this->assertLessThan(400 * 1024, strlen($pdf));
    }

    /**
     * PDF/A-1 is not supported (no CIDSet for subsets, no transparency).
     */
    public function test_pdfa1_is_rejected(): void {
        $this->expectException(\coding_exception::class);
        $this->create_tcpdf_with_trait()->enable_pdfa(1);
    }

    /**
     * Comparable structure of a PDF: object types, fonts and filters in order (ids and dates differ).
     *
     * @param string $pdf
     * @return array
     */
    private function structure(string $pdf): array {
        preg_match_all('#/(Type|Subtype|BaseFont|Filter|ColorSpace)\s*/([A-Za-z0-9+_-]+)#', $pdf, $matches, PREG_SET_ORDER);
        return array_map(fn($m) => $m[1] . '=' . $m[2], $matches);
    }
}
