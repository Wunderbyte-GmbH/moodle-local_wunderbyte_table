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
 * Tests for the PDF/A capable TCPDF wrapper.
 *
 * @package local_wunderbyte_table
 * @category test
 * @copyright 2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_wunderbyte_table\local\pdf;

use advanced_testcase;

/**
 * Structural PDF/A-2b checks on the generated PDF (the full ISO check needs veraPDF, see
 * assert_verapdf()). The assertions act as tripwires for the TCPDF internals the wrapper
 * relies on: XMP identification, output intent, no unembedded core fonts, subsetting.
 *
 * @package local_wunderbyte_table
 * @category test
 * @copyright 2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \local_wunderbyte_table\local\pdf\pdfa_pdf
 */
final class pdfa_pdf_test extends advanced_testcase {
    /**
     * Fonts that must never appear unembedded (TCPDF core fonts).
     */
    private const COREFONTS = '#/BaseFont\s*/(Helvetica|Times|Courier|Symbol|ZapfDingbats)#';

    /**
     * Sets up the test environment.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
    }

    /**
     * Creates a document with one page and the given HTML.
     *
     * @param string $html
     * @param callable|null $extra callback receiving the pdf before output
     * @param bool $headerfooter print TCPDF's default header/footer
     * @return string the PDF
     */
    private function render(string $html, ?callable $extra = null, bool $headerfooter = false): string {
        $pdf = new pdfa_pdf('P', 'mm', 'A4', true, 'UTF-8');
        $pdf->SetCreator('local_wunderbyte_table');
        $pdf->SetTitle('pdfa test');
        $pdf->setPrintHeader($headerfooter);
        $pdf->setPrintFooter($headerfooter);
        $pdf->AddPage();
        if ($html !== '') {
            $pdf->writeHTML($html, true, false, true, false, '');
        }
        if ($extra) {
            $extra($pdf);
        }
        $out = $pdf->Output('test.pdf', 'S');
        $this->assert_pdfa2b($out);
        return $out;
    }

    /**
     * Structural PDF/A-2b assertions shared by all tests.
     *
     * @param string $pdf
     */
    private function assert_pdfa2b(string $pdf): void {
        $this->assertStringStartsWith('%PDF-1.7', $pdf);
        $this->assertStringContainsString('<pdfaid:part>2</pdfaid:part>', $pdf);
        $this->assertStringContainsString('<pdfaid:conformance>B</pdfaid:conformance>', $pdf);
        $this->assertStringContainsString('/OutputIntents', $pdf);
        $this->assertStringContainsString('/GTS_PDFA1', $pdf);
        $this->assertDoesNotMatchRegularExpression(self::COREFONTS, $pdf, 'Unembedded core font found');
        $this->assertStringNotContainsString('/DeviceCMYK', $pdf);
        // Every font descriptor carries an embedded font program.
        $descriptors = preg_match_all('#/Type\s*/FontDescriptor#', $pdf);
        $this->assertGreaterThan(0, $descriptors);
        $this->assertSame($descriptors, preg_match_all('#/FontFile2\s+\d+ 0 R#', $pdf));
        $this->assert_verapdf($pdf);
    }

    /**
     * Runs veraPDF when VERAPDF_BIN points to the CLI (optional local check, skipped otherwise).
     *
     * @param string $pdf
     */
    private function assert_verapdf(string $pdf): void {
        $bin = getenv('VERAPDF_BIN');
        if (empty($bin) || !is_executable($bin)) {
            return;
        }
        $file = make_request_directory() . '/pdfa.pdf';
        file_put_contents($file, $pdf);
        $output = shell_exec(escapeshellarg($bin) . ' -f 2b --format text -v ' . escapeshellarg($file) . ' 2>&1');
        $this->assertStringStartsWith('PASS', trim((string)$output), "veraPDF: $output");
    }

    /**
     * Plain text ends up as a small, subset, PDF/A-2b marked document.
     */
    public function test_plain_text_is_pdfa2b_and_subset(): void {
        $pdf = $this->render('<p>Hello PDF/A äöü €</p>');
        // TCPDF disables subsetting in PDF/A mode; the wrapper restores it. A fully
        // embedded FreeSerif alone is 1.8 MB.
        $this->assertLessThan(300 * 1024, strlen($pdf));
        $this->assertMatchesRegularExpression('#/BaseFont\s*/[A-Z]{6}\+Free#', $pdf, 'Font is not a subset');
        // Compression is re-enabled although TCPDF turns it off in PDF/A mode.
        $this->assertStringContainsString('/FlateDecode', $pdf);
    }

    /**
     * Callers routinely call setFontSubsetting(true) after construction; TCPDF turns that
     * into "false" in PDF/A mode, which would silently switch back to full embedding.
     */
    public function test_set_font_subsetting_keeps_subsets(): void {
        $pdf = $this->render('<p>Hello <b>bold</b> <i>italic</i></p>', function (pdfa_pdf $pdf) {
            // Called after content was written on purpose as well: the flag is read at AddFont() time.
            $pdf->setFontSubsetting(true);
            $pdf->SetFont('freesans', 'B', 12);
            $pdf->Cell(0, 10, 'sans bold', 0, 1);
        });
        $this->assertLessThan(300 * 1024, strlen($pdf));
        $this->assertDoesNotMatchRegularExpression('#/BaseFont\s*/Free#', $pdf, 'Fully embedded font found');
    }

    /**
     * Core fonts requested via CSS, SetFont() (with style suffixes) and monospaced
     * elements are mapped onto embeddable fonts instead of crashing on the missing
     * pdfa* font files.
     */
    public function test_core_fonts_are_remapped(): void {
        $html = '<style>h1 { font-family: times; } .m { font-family: monospace; } .s { font-family: sans-serif; }</style>'
            . '<h1>Times heading</h1><p class="m">mono</p><p class="s">sans</p><pre>pre</pre><tt>tt</tt>'
            . '<p style="font-family: helvetica bold">helvetica bold</p>';
        $pdf = $this->render($html, function (pdfa_pdf $pdf) {
            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->Cell(0, 10, 'helvetica bold', 0, 1);
            $pdf->SetFont('helveticaBI', '', 12);
            $pdf->Cell(0, 10, 'helveticaBI suffix', 0, 1);
            $pdf->SetFont('courier', '', 12);
            $pdf->Cell(0, 10, 'courier', 0, 1);
            $pdf->SetFont('times', 'I', 12);
            $pdf->Cell(0, 10, 'times italic', 0, 1);
            $pdf->SetFont('symbol', '', 12);
            $pdf->Cell(0, 10, 'symbol', 0, 1);
            $pdf->SetFont('zapfdingbats', '', 12);
            $pdf->Cell(0, 10, 'zapfdingbats', 0, 1);
        });
        foreach (['FreeSans', 'FreeSansBold', 'FreeSansBoldOblique', 'FreeSerif', 'FreeSerifItalic', 'FreeMono'] as $font) {
            $this->assertMatchesRegularExpression('#/BaseFont\s*/[A-Z]{6}\+' . $font . '\b#', $pdf, "$font missing");
        }
    }

    /**
     * TCPDF's default header/footer machinery selects the header/footer font itself
     * (helvetica by default) before calling Header()/Footer().
     */
    public function test_default_header_footer_do_not_crash(): void {
        $pdf = $this->render('<p>body</p>', null, true);
        $this->assertStringContainsString('FreeSans', $pdf);
    }

    /**
     * Images: alpha PNG (allowed in PDF/A-2), SVG (uses helvetica internally), links.
     */
    public function test_images_and_links(): void {
        global $CFG;
        $png = $this->create_alpha_png();
        $svg = '@<svg xmlns="http://www.w3.org/2000/svg" width="100" height="40" viewBox="0 0 100 40">'
            . '<rect width="100" height="40" fill="#f98012"/><text x="10" y="25">svg</text></svg>';
        $html = '<img src="data:image/png;base64,' . base64_encode($png) . '" width="60">'
            . '<p><a href="https://example.com/?a=1&amp;b=2">link</a></p>';
        $pdf = $this->render($html, function (pdfa_pdf $pdf) use ($png, $svg, $CFG) {
            $pdf->Image('@' . $png, 10, 100, 40, 0);
            $pdf->ImageSVG($svg, 10, 130, 40, 0);
            $pdf->ImageSVG($CFG->dirroot . '/pix/moodlelogo.svg', 60, 130, 40, 0);
        });
        // Alpha channel = soft mask; allowed from PDF/A-2 on.
        $this->assertStringContainsString('/SMask', $pdf);
        $this->assertStringContainsString('/URI (https://example.com/?a=1&b=2)', $pdf);
    }

    /**
     * CMYK JPEGs would be embedded as DeviceCMYK, which PDF/A does not allow together with
     * the sRGB output intent. They are converted to RGB on all input paths of Image().
     */
    public function test_cmyk_jpeg_is_converted_to_rgb(): void {
        $file = __DIR__ . '/../../fixtures/cmyk.jpg';
        $info = getimagesize($file);
        $this->assertSame(4, $info['channels'], 'Fixture is expected to be a CMYK JPEG');
        $data = file_get_contents($file);

        // Sanity check: without the wrapper the JPEG is embedded as CMYK.
        $plain = new \pdf('P', 'mm', 'A4');
        $plain->setPrintHeader(false);
        $plain->setPrintFooter(false);
        $plain->AddPage();
        $plain->Image($file, 10, 10, 30, 0);
        $this->assertStringContainsString('/DeviceCMYK', $plain->Output('plain.pdf', 'S'));

        $html = '<img src="data:image/jpeg;base64,' . base64_encode($data) . '" width="60">'
            . '<img src="' . $file . '" width="60">';
        $pdf = $this->render($html, function (pdfa_pdf $pdf) use ($file, $data) {
            $pdf->Image($file, 10, 100, 30, 0);
            $pdf->Image('@' . $data, 50, 100, 30, 0);
        });
        // TCPDF de-duplicates identical images, so only the presence and colour space are checked.
        $this->assertGreaterThanOrEqual(1, preg_match_all('#/Subtype\s*/Image#', $pdf));
        $this->assertStringContainsString('/DeviceRGB', $pdf);
    }

    /**
     * A remote image that cannot be loaded on the server (typo in the URL, 404, no outbound
     * network) must not abort the document - TCPDF itself skips unreachable URLs silently,
     * and the pre-fetch for the CMYK check must not turn that into "Unable to get the size
     * of the image".
     */
    public function test_unreachable_remote_image_is_skipped(): void {
        // Connection refused immediately (discard port), no network needed. Moodle's curl
        // security blocks localhost by default anyway - either way the image is unavailable.
        $url = 'http://127.0.0.1:9/logo.png';
        $pdf = $this->render(
            '<table><tr><td><img src="' . $url . '" width="100"></td><td>text</td></tr></table>',
            function (pdfa_pdf $pdf) use ($url) {
                $this->assertFalse($pdf->Image($url, 10, 100, 40, 0));
                $pdf->Cell(0, 10, 'after image', 0, 1);
            }
        );
        $this->assertSame(0, preg_match_all('#/Subtype\s*/Image#', $pdf));
        $messages = array_column($this->getDebuggingMessages(), 'message');
        $this->resetDebugging();
        // The <img> tag and the direct Image() call both report the skipped image (the URL is
        // only fetched once per document); Moodle's curl may add its own "URL is blocked" message.
        $this->assertCount(2, preg_grep('/image skipped/', $messages));
    }

    /**
     * Existing callers may still pass explicit fonts and options the way they did for \pdf.
     */
    public function test_explicit_free_fonts_and_metadata(): void {
        $pdf = $this->render('', function (pdfa_pdf $pdf) {
            $pdf->SetAuthor('someone@example.com');
            $pdf->SetSubject('subject');
            $pdf->SetKeywords('a, b');
            $pdf->SetFont('freesans', '', 10);
            $pdf->Cell(0, 10, 'Page ' . $pdf->getAliasNumPage() . '/' . $pdf->getAliasNbPages(), 0, 1);
            $pdf->SetAlpha(0.5);
            $pdf->Rect(10, 50, 30, 10, 'F');
            $pdf->SetAlpha(1);
            $pdf->StartTransform();
            $pdf->Rotate(90);
            $pdf->Cell(20, 5, 'rotated', 1, 1);
            $pdf->StopTransform();
        });
        $this->assertStringContainsString('<rdf:li>someone@example.com</rdf:li>', $pdf);
        $this->assertStringContainsString('/Author', $pdf);
    }

    /**
     * Creates a small RGBA PNG with GD.
     *
     * @return string
     */
    private function create_alpha_png(): string {
        $image = imagecreatetruecolor(40, 20);
        imagesavealpha($image, true);
        imagealphablending($image, false);
        imagefill($image, 0, 0, imagecolorallocatealpha($image, 0, 0, 0, 127));
        imagefilledrectangle($image, 5, 5, 35, 15, imagecolorallocatealpha($image, 249, 128, 18, 40));
        ob_start();
        imagepng($image);
        $png = ob_get_clean();
        imagedestroy($image);
        return $png;
    }
}
