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

// TCPDF method names (AddFont, Image, ...) are dictated by TCPDF.
// phpcs:disable moodle.NamingConventions.ValidFunctionName.LowercaseMethod

namespace local_wunderbyte_table\local\pdf;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/pdflib.php');
require_once($CFG->libdir . '/filelib.php');

/**
 * Optional PDF/A-2b support for TCPDF based classes (TCPDF itself or Moodle's pdf wrapper).
 *
 * The trait is inert until {@see self::enable_pdfa()} is called: all overrides pass straight
 * through to TCPDF, so a class using the trait behaves exactly like its parent as long as
 * PDF/A is not enabled. This lets plugins keep their existing PDF output byte-for-byte and
 * switch to PDF/A-2b behind an admin setting.
 *
 * Why the extra work - enabling TCPDF's own PDF/A mode is not enough in Moodle:
 * - Moodle's {@see \pdf} constructor does not expose TCPDF's $pdfa argument.
 * - Moodle ships TCPDF without the embeddable "pdfa*" replacements for the core fonts
 *   (helvetica, times, courier, symbol, zapfdingbats). In PDF/A mode TCPDF then throws
 *   "Could not include font definition file: pdfahelvetica" as soon as a core font is
 *   used - explicitly, through CSS (font-family: serif|sans-serif|monospace|Times ...) in
 *   an admin editable HTML template, or through TCPDF internals (SVG default font,
 *   header/footer font, monospaced font). All core fonts are therefore mapped onto the
 *   GNU FreeFont clones shipped with Moodle (helvetica -> freesans, times -> freeserif,
 *   courier -> freemono).
 * - TCPDF disables font subsetting and stream compression in PDF/A mode although both
 *   are allowed from PDF/A-2 on (a fully embedded FreeSerif+FreeSans document is ~2.5 MB,
 *   a subset one ~150 KB); both are re-enabled.
 * - CMYK JPEGs would be embedded as DeviceCMYK, which PDF/A does not allow next to the
 *   sRGB output intent TCPDF writes; they are converted to RGB.
 * - Remote images are downloaded once per document with Moodle's curl (proxy and cURL
 *   security settings apply); an image that cannot be loaded is left out instead of
 *   aborting the document.
 *
 * Only PDF/A-2 and PDF/A-3 are supported: PDF/A-1 forbids transparency (alpha channel
 * logos) and requires a CIDSet stream for font subsets that TCPDF does not write.
 *
 * @package    local_wunderbyte_table
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
trait pdfa_trait {
    /**
     * Whether PDF/A output was enabled for this document.
     *
     * @var bool
     */
    protected bool $pdfaenabled = false;

    /**
     * Remote image URL => local path of the downloaded copy (null = could not be loaded).
     *
     * @var array
     */
    protected array $remoteimages = [];

    /**
     * Core fonts (not embeddable in Moodle's TCPDF) mapped onto embeddable TrueType fonts.
     *
     * @return array
     */
    protected function get_pdfa_fontmap(): array {
        return [
            'helvetica' => 'freesans',
            'times' => 'freeserif',
            'courier' => 'freemono',
            'symbol' => 'freeserif',
            'zapfdingbats' => 'freeserif',
        ];
    }

    /**
     * Switches the document to PDF/A output. Call right after construction, before content is added.
     *
     * @param int $part PDF/A part written into the XMP metadata (2 = PDF/A-2b, 3 = PDF/A-3b)
     * @return void
     */
    public function enable_pdfa(int $part = 2): void {
        if ($part < 2) {
            throw new \coding_exception('Only PDF/A-2 and PDF/A-3 are supported.');
        }
        $this->pdfaenabled = true;
        // TCPDF writes converted / downloaded images to K_PATH_CACHE (Moodle's pdf class
        // creates it in its constructor, plain TCPDF subclasses do not).
        make_cache_directory('tcpdf');

        // Moodle's pdf class does not pass TCPDF's $pdfa constructor argument on, so the
        // (protected) flags are set afterwards. Everything TCPDF derives from them at
        // construction time (PDF version, compression) is recalculated below.
        $this->pdfa_mode = true;
        $this->pdfa_version = $part;
        $this->setPDFVersion();

        // TCPDF switches compression off in PDF/A mode. Flate streams are allowed in
        // every PDF/A part; the output was validated with veraPDF.
        $this->setCompression();
        $this->compress = true;

        // TCPDF's defaults for these point to core fonts (helvetica, courier). They are
        // remapped by AddFont() anyway, but set explicitly to keep the intent visible.
        $this->setHeaderFont(['freesans', '', PDF_FONT_SIZE_MAIN]);
        $this->setFooterFont(['freesans', '', PDF_FONT_SIZE_DATA]);
        $this->setDefaultMonospacedFont('freemono');
    }

    /**
     * Whether PDF/A output is enabled for this document.
     *
     * @return bool
     */
    public function is_pdfa(): bool {
        return $this->pdfaenabled;
    }

    /**
     * Imports a TrueType or Type1 or core font and makes it available.
     *
     * With PDF/A enabled this is the single choke point for fonts: SetFont(), the HTML/CSS
     * parser (getFontFamilyName), ImageSVG() and TCPDF internals all end up here. Core fonts
     * are remapped and font subsetting (disabled by TCPDF in PDF/A mode) is restored.
     *
     * @param string $family font family
     * @param string $style font style (B, I, BI, ...)
     * @param string $fontfile the font definition file
     * @param mixed $subset subsetting mode (true, false or 'default')
     * @return array|false font data or false in case of error
     */
    public function AddFont($family, $style = '', $fontfile = '', $subset = 'default') {
        if (!$this->pdfaenabled) {
            return parent::AddFont($family, $style, $fontfile, $subset);
        }
        [$family, $style] = $this->map_core_font((string)$family, (string)$style);

        // TCPDF forces full embedding in PDF/A mode. Subsets are fine for PDF/A-2/3
        // (only PDF/A-1 needs the CIDSet stream TCPDF does not write), so the flag is
        // dropped for the duration of the parent call. It only affects the subset switch
        // and the "pdfa" core font prefix in AddFont(), which is obsolete after the remap.
        $pdfamode = $this->pdfa_mode;
        $this->pdfa_mode = false;
        try {
            return parent::AddFont($family, $style, $fontfile, $subset);
        } finally {
            $this->pdfa_mode = $pdfamode;
        }
    }

    /**
     * Enable or disable default option for font subsetting.
     *
     * TCPDF forces full embedding in PDF/A mode; subsets are allowed from PDF/A-2 on
     * (see AddFont()), so with PDF/A enabled the caller's choice is kept.
     *
     * @param bool $enable if true enable font subsetting by default
     */
    public function setFontSubsetting($enable = true) {
        if (!$this->pdfaenabled) {
            parent::setFontSubsetting($enable);
            return;
        }
        $this->font_subsetting = $enable ? true : false;
    }

    /**
     * Puts an image in the page.
     *
     * With PDF/A enabled, CMYK JPEGs are converted to RGB PNGs first (TCPDF would embed
     * them as DeviceCMYK, which PDF/A does not allow next to the sRGB output intent) and
     * remote images are downloaded with Moodle's curl - unreachable ones are left out.
     *
     * @param string $file name of the file containing the image, an URL, or '@' followed by the raw image data
     * @param float|null $x abscissa of the upper-left corner
     * @param float|null $y ordinate of the upper-left corner
     * @param float $w width of the image in the page
     * @param float $h height of the image in the page
     * @param string $type image format (JPEG, PNG, ...); auto-detected if empty
     * @param mixed $link URL or identifier returned by AddLink()
     * @param string $align indicates the alignment of the pointer next to image insertion
     * @param mixed $resize if true resize (reduce) the image to fit $w and $h
     * @param int $dpi dot-per-inch resolution used on resize
     * @param string $palign allows to center or align the image on the current line
     * @param bool $ismask true if this image is a mask for other images
     * @param mixed $imgmask image object returned by this function or false
     * @param mixed $border indicates if borders must be drawn around the cell
     * @param mixed $fitbox if not false scale image dimensions proportionally to fit within the ($w, $h) box
     * @param bool $hidden if true do not display the image
     * @param bool $fitonpage if true the image is resized to not exceed page dimensions
     * @param bool $alt if true the image will be added as alternative and not directly printed
     * @param array $altimgs array of alternate images keys
     * @return mixed image information
     */
    public function Image(
        $file,
        $x = null,
        $y = null,
        $w = 0,
        $h = 0,
        $type = '',
        $link = '',
        $align = '',
        $resize = false,
        $dpi = 300,
        $palign = '',
        $ismask = false,
        $imgmask = false,
        $border = 0,
        $fitbox = false,
        $hidden = false,
        $fitonpage = false,
        $alt = false,
        $altimgs = []
    ) {
        if ($this->pdfaenabled) {
            [$file, $type] = $this->normalise_image_source($file, (string)$type);
        }
        return parent::Image(
            $file,
            $x,
            $y,
            $w,
            $h,
            $type,
            $link,
            $align,
            $resize,
            $dpi,
            $palign,
            $ismask,
            $imgmask,
            $border,
            $fitbox,
            $hidden,
            $fitonpage,
            $alt,
            $altimgs
        );
    }

    /**
     * Maps a core font family (optionally carrying TCPDF's B/I suffix) onto its embeddable replacement.
     *
     * @param string $family font family as passed to AddFont()
     * @param string $style font style as passed to AddFont()
     * @return array [family, style]
     */
    protected function map_core_font(string $family, string $style): array {
        $base = trim($family);
        // TCPDF accepts style suffixes on the family name (helveticaB, helveticaBI, ...).
        $suffix = '';
        if (substr($base, -1) === 'I') {
            $suffix = 'I';
            $base = substr($base, 0, -1);
        }
        if (substr($base, -1) === 'B') {
            $suffix = 'B' . $suffix;
            $base = substr($base, 0, -1);
        }
        $fontmap = $this->get_pdfa_fontmap();
        $key = strtolower($base);
        if (!isset($fontmap[$key])) {
            return [$family, $style];
        }
        return [$fontmap[$key], $style . $suffix];
    }

    /**
     * Converts CMYK JPEG image sources to RGB PNG data; everything else is passed through.
     *
     * Remote images that cannot be loaded (or decoded) on the server are dropped: the
     * returned file is null, which makes TCPDF::Image() return without drawing anything.
     *
     * @param mixed $file image source as accepted by TCPDF::Image()
     * @param string $type image type as passed to TCPDF::Image()
     * @return array [file, type]
     */
    protected function normalise_image_source($file, string $type): array {
        if (!is_string($file) || $file === '' || !function_exists('imagecreatefromstring')) {
            return [$file, $type];
        }
        if (strtolower($type) === 'svg' || preg_match('/\.svg(\?.*)?$/i', $file)) {
            return [$file, $type];
        }

        $data = null;
        if ($file[0] === '@') {
            $data = substr($file, 1);
            $info = @getimagesizefromstring($data);
        } else {
            $source = $file[0] === '*' ? substr($file, 1) : $file;
            if (preg_match('#^[a-z][a-z0-9+.-]*://#i', $source)) {
                // Remote image (e.g. a logo URL in an admin template): downloaded once per
                // document with Moodle's curl (proxy settings, timeouts) into a request
                // scoped file, so TCPDF neither fetches it again nor caches a failed fetch.
                $file = $this->fetch_remote_image($source);
                if ($file === null) {
                    // Not reachable from the server (404, typo, no outbound network) or not
                    // a decodable image: generate the document without it, as TCPDF does for
                    // unreachable URLs itself. Image() returns false for an empty file.
                    debugging(
                        'pdfa: image skipped, it could not be loaded from the server: ' . $source,
                        DEBUG_DEVELOPER
                    );
                    return [null, $type];
                }
                $source = $file;
            }
            $info = @getimagesize($source);
            if ($info && $this->is_cmyk_jpeg($info)) {
                $data = @file_get_contents($source);
            }
        }

        if (!$info || !$this->is_cmyk_jpeg($info) || !is_string($data) || $data === '') {
            return [$file, $type];
        }

        $image = @imagecreatefromstring($data);
        if (!$image) {
            return [$file, $type];
        }
        ob_start();
        imagepng($image);
        $png = ob_get_clean();
        imagedestroy($image);
        if (empty($png)) {
            return [$file, $type];
        }
        return ['@' . $png, 'PNG'];
    }

    /**
     * Downloads a remote image once per document and returns the local (request scoped) path.
     *
     * @param string $url
     * @return string|null path of the downloaded image, null when it cannot be loaded or decoded
     */
    protected function fetch_remote_image(string $url): ?string {
        if (array_key_exists($url, $this->remoteimages)) {
            return $this->remoteimages[$url];
        }
        $this->remoteimages[$url] = null;
        // Moodle's curl wrapper: proxy settings, blocked hosts, redirects.
        $curl = new \curl();
        $curl->setopt(['CURLOPT_CONNECTTIMEOUT' => 5, 'CURLOPT_TIMEOUT' => 15]);
        $data = $curl->get($url);
        if (
            !empty($curl->error)
            || $curl->get_errno()
            || (int)($curl->info['http_code'] ?? 0) !== 200
            || !is_string($data)
            || $data === ''
        ) {
            return null;
        }
        $info = @getimagesizefromstring($data);
        if (!$info) {
            return null;
        }
        // The extension is only cosmetic, TCPDF detects the type from the image data.
        $path = make_request_directory() . '/' . md5($url) . image_type_to_extension((int)$info[2], true);
        if (file_put_contents($path, $data) === false) {
            return null;
        }
        $this->remoteimages[$url] = $path;
        return $path;
    }

    /**
     * Whether getimagesize() info describes a four channel (CMYK) JPEG.
     *
     * @param array $info result of getimagesize()/getimagesizefromstring()
     * @return bool
     */
    protected function is_cmyk_jpeg(array $info): bool {
        return ($info['mime'] ?? '') === 'image/jpeg' && (int)($info['channels'] ?? 3) === 4;
    }
}
