<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/**
 *
 */
class PdfGenerator
{
    private const WEBKIT = 'webkit';
    private const WEASYPRINT = 'weasyprint';
    private const WEBSERVICE = 'webservice';

    public const MPDF = 'mpdf';

    public $error;

    private $mode;
    private $location;

    /**
     * @param string $printMode allow to force a given print mode
     */
    public function __construct($printMode = '')
    {
        global $prefs;
        $this->mode = 'none';
        $this->error = false;

        if (empty($printMode)) {
            $printMode = $prefs['print_pdf_from_url'];
        }

        if ($printMode == self::WEBKIT) {
            $path = $prefs['print_pdf_webkit_path'];
            if (! empty($path) && is_executable($path)) {
                $this->mode = 'webkit';
                $this->location = $path;
            } else {
                if (! empty($path)) {
                    $this->error = tr('PDF webkit path "%0" not found.', $path);
                } else {
                    $this->error = tr('The PDF webkit path has not been set.');
                }
            }
        } elseif ($printMode == self::WEASYPRINT) {
            $path = $prefs['print_pdf_weasyprint_path'];
            if (! empty($path) && is_executable($path)) {
                $this->mode = 'weasyprint';
                $this->location = $path;
            } else {
                if (! empty($path)) {
                    $this->error = tr('PDF WeasyPrint path "%0" not found.', $path);
                } else {
                    $this->error = tr('The PDF WeasyPrint path has not been set.');
                }
            }
        } elseif ($printMode == self::WEBSERVICE) {
            $path = $prefs['print_pdf_webservice_url'];
            if (! empty($path)) {
                $this->mode = 'webservice';
                $this->location = $path;
            } else {
                if (! empty($path)) {
                    $this->error = tr('PDF webservice URL "%0" not found.', $path);
                } else {
                    $this->error = tr('The PDF webservice URL has not been set.');
                }
            }
        } elseif ($printMode == self::MPDF) {
            if (class_exists('\\Mpdf\\Mpdf')) {
                $this->mode = 'mpdf';
            } else {
                $this->error = tr('The package mPDF is not installed. You can install it using packages.');
            }
        }
        if ($this->error) {
            $this->error = tr('PDF generation failed.') . ' ' . $this->error . ' '
                . tr('This is set by the administrator (search for "print" in the control panels to locate the setting).');
        }
    }

    /**
     * @param $file
     * @param array $params
     * @return mixed
     */
    public function getPdf($file, array $params, $pdata = '')
    {
        return TikiLib::lib('tiki')->allocate_extra(
            'print_pdf',
            function () use ($file, $params, $pdata) {
                global $prefs, $base_url, $tikiroot;

                if ($prefs['auth_token_access'] == 'y') {
                    $perms = Perms::get();

                    require_once 'lib/auth/tokens.php';
                    $tokenlib = AuthTokens::build($prefs);
                    $params['TOKEN'] = $tokenlib->createToken(
                        $tikiroot . $file,
                        $params,
                        $perms->getGroups(),
                        ['timeout' => 120]
                    );
                }
                if ((isset($params['printpages']) && is_array($params['printpages'])) || (isset($params['printpages']) && is_array($params['printstructures']))) {
                    if (is_array($params['printpages'])) {
                        $params['printpages'] = implode('&', $params['printpages']);
                    } else {
                        $params['printpages'] = implode('&', $params['printstructures']);
                    }
                    //getting parsed data
                    foreach ($params['pages'] as $page) {
                        $pdata .= $page['parsed'];
                    }
                } elseif (empty($pdata) && ! empty($params['page'])) {
                    $tikilib = TikiLib::lib('tiki');
                    $page_info = $tikilib->get_page_info($params['page']);
                    if ($page_info) {
                        $pdata = TikiLib::lib('parser')->parse_data($page_info['data'], ['is_html' => $page_info['is_html'], 'print' => 'y', 'namespace' => $page_info['namespace']]);
                    }
                }
                $url = $base_url . $file . '?' . http_build_query($params, '', '&');
                $return = $this->{$this->mode}($url, $pdata, $params);

                if ($prefs['auth_token_access'] == 'y') {
                    // clean up token created above just in case PDF needs to access images etc
                    $data = $tokenlib->getToken($params['TOKEN']);
                    $tokenlib->deleteToken($data['tokenId']);
                }

                return $return;
            }
        );
    }

    /**
     * @param $url
     * @return null
     */
    private function none($url)
    {
        return null;
    }

    /**
     * @param $url
     * @return mixed
     */
    private function webkit($url)
    {
        // Make sure shell_exec is available
        if (! function_exists('shell_exec')) {
            die(tra('Required function shell_exec is not enabled.'));
        }

        // escapeshellarg will replace all % characters with spaces on Windows
        // So, decode the URL before sending it to the commandline
        $urlDecoded = urldecode($url);
        $arg = escapeshellarg($urlDecoded);

        // Write a temporary file, instead of using stdout
        // There seemed to be encoding issues when using stdout (on Windows 7 64 bit).

        // Use temp/public. It is cleaned up during a cache clean, in case some files are left
        $filename = 'temp/public/out' . mt_rand() . '.pdf';

        // Run shell_exec command to generate out file
        // NOTE: this requires write permissions
        $quotedFilename = '"' . $filename . '"';
        $quotedCommand = '"' . $this->location . '"';

        `$quotedCommand -q $arg $quotedFilename`;

        // Read the out file
        $pdf = file_get_contents($filename);

        // Delete the outfile
        unlink($filename);

        return $pdf;
    }

    /**
     * @param $url
     * @return mixed
     */
    private function weasyprint($url)
    {
        // Make sure shell_exec is available
        if (! function_exists('shell_exec')) {
            die(tra('Required function shell_exec is not enabled.'));
        }

        // escapeshellarg will replace all % characters with spaces on Windows
        // So, decode the URL before sending it to the commandline
        $urlDecoded = urldecode($url);
        $arg = escapeshellarg($urlDecoded);

        // Write a temporary file, instead of using stdout
        // There seemed to be encoding issues when using stdout (on Windows 7 64 bit).

        // Use temp/public. It is cleaned up during a cache clean, in case some files are left
        $filename = 'temp/public/out' . mt_rand() . '.pdf';

        // Run shell_exec command to generate out file
        // NOTE: this requires write permissions
        $quotedFilename = '"' . $filename . '"';
        $quotedCommand = '"' . $this->location . '"';

        // redirect STDERR to null with 2>/dev/null becasue it outputs plenty of irrelevant warnings (hopefully nothing critical)
        `$quotedCommand $arg $quotedFilename 2>/dev/null`;

        // Read the out file
        $pdf = file_get_contents($filename);

        // Delete the outfile
        unlink($filename);

        return $pdf;
    }

    /**
     * @param $url
     * @return bool
     */
    private function webservice($url)
    {
        global $tikilib;

        $target = $this->location . '?' . $url;
        return $tikilib->httprequest($target);
    }

    /**
     * @param $url string - address of the item to print as PDF
     * @return string     - contents of the PDF
     */
    private function mpdf($url, $parsedData = '', $params = [])
    {
        global $prefs;

        if ($parsedData != '') {
            $html = $parsedData;
        } else {
            $title = tr('Page not printed ! ');
            $mes = tr('It looks like you are trying to print a blank page.');
            if (preg_match('/\bpage=([^&]+)/', $url, $matches)) {
                $page = substr($matches[0], 5, null);
                Feedback::warning(['mes' => $mes, 'title' => $title]);
                TikiLib::lib('access')->redirect($page);
                die();
            }
            throw new Services_Exception($title . $mes);
        }

       //getting n replacing images
        $tempImgArr = [];
        $wikilib = TikiLib::lib('wiki');
        //checking and getting plugin_pdf parameters if set
        $pdfSettings = $this->getPDFSettings($html, $prefs, $params);
        //Add page title with content enabled in prefs and page indiviual settings
        if (($prefs['feature_page_title'] == 'y' && isset($params['page']) && $wikilib->get_page_hide_title($params['page']) == 0 && $pdfSettings['pagetitle'] != 'n') || $pdfSettings['pagetitle'] == 'y') {
            $html = '<h1>' . $params['page'] . '</h1>' . $html;
        }

        if ($pdfSettings['toc'] == 'y') {   //checking toc
           //checking links
            if ($pdfSettings['toclinks'] == 'y') {
                $links = "links=\"1\"";
            }
           //checking toc heading
            if ($pdfSettings['tocheading']) {
                $tocpreHTML = htmlspecialchars("<h1>" . $pdfSettings['tocheading'] . "</h1>", ENT_QUOTES);
            }
            $html = "<html><tocpagebreak toc-odd-footer-name=\"footer-without-pagination\"  toc-odd-footer-value=\"1\"" . $links . " toc-preHTML=\"" . $tocpreHTML . "\" toc-resetpagenum=\"1\" toc-suppress=\"on\" />" . $html . "</html>";
        }
        $this->_parseHTML($html);
        $this->_getImages($html, $tempImgArr);
        $defaults = new \Mpdf\Config\ConfigVariables();
        $defaultVariables = $defaults->getDefaults();
        $mpdfConfig = [
            'fontDir' => array_merge([TIKI_PATH . '/' . FONTAWESOME_WEBFONTS_PATH . '/'], $defaultVariables['fontDir']),
            'mode' => 'utf8',
            'format' => $pdfSettings['pagesize'],
            'margin_left' => $pdfSettings['margin_left'],
            'margin_right' => $pdfSettings['margin_right'],
            'margin_top' => $pdfSettings['margin_top'],
            'margin_bottom' => $pdfSettings['margin_bottom'],
            'margin_header' => $pdfSettings['margin_header'],
            'margin_footer' => $pdfSettings['margin_footer'],
            'orientation' => $pdfSettings['orientation'],
            'setAutoTopMargin' => 'stretch',
            'setAutoBottomMargin' => 'stretch',
            'tempDir' => TIKI_PATH . '/temp/mpdf'
        ];

        if (! file_exists($mpdfConfig['tempDir'])) {
            mkdir($mpdfConfig['tempDir'], 0770, true);
        }

        $mpdf = new \Mpdf\Mpdf($mpdfConfig);
        $mpdf->curlAllowUnsafeSslRequests = ($prefs['print_pdf_mpdf_allow_unsafe_ssl_requests'] ?? 'y') === 'y';

        //custom fonts add, currently fontawesome support is added, more fonts can be added in future
        $custom_fontdata = [
         'fontawesome' => [
            'R' => "fa-regular-400.ttf"
         ],
         'fontawesome-solid' => [
            'R' => 'fa-solid-900.ttf'
         ],
         'fontawesome-brands' => [
            'R' => "fa-brands-400.ttf"
         ]];

        //calling function to add custom fonts
        add_custom_font_to_mpdf($mpdf, $custom_fontdata);

        //for Cantonese support
        $mpdf->autoScriptToLang = true;
        $mpdf->autoLangToFont = true;

        $mpdf->SetTitle($params['page'] ?? '');

        //toc levels
        $mpdf->h2toc = isset($pdfSettings['toclevels']) ? $pdfSettings['toclevels'] : [];
        //password protection
        if ($pdfSettings['print_pdf_mpdf_password']) {
            $mpdf->SetProtection([], 'UserPassword', $pdfSettings['print_pdf_mpdf_password']);
        }

        $mpdf->CSSselectMedia = 'print';                // assuming you used this in the document header

        //getting main base css file
        $basecss = file_get_contents('themes/base_files/css/tiki_base.css'); // external css
        //apply available fonts to classes for printing
        $basecss .= '.far, .fa-regular { font-family: fontawesome; } .fa, .fas, .fa-solid { font-family: fontawesome-solid; } .fab, .fa-brands { font-family: fontawesome-brands; }';
        //getting theme css
        $themeLib = TikiLib::lib('theme');
        $themecss = $themeLib->get_theme_path($prefs['theme'], '', $prefs['theme'] . '.css');
        $themecss = file_get_contents($themecss) . 'b,strong{font-weight:bold !important;}';
        $extcss = file_get_contents('vendor_bundled/vendor/jquery/jquery-sheet/jquery.sheet.css');

        //checking if print friendly option is enabled, then attach print css otherwise theme styles will be retained by theme css
        if ($pdfSettings['print_pdf_mpdf_printfriendly'] == 'y') {
            $printcss = file_get_contents('themes/base_files/css/printpdf.css'); // external css
            $bodycss = 'tiki tiki-print'; //execluding theme css in case print friendly is set to yes.
        } else {//preserving theme styles by removing media print styles to print what is shown on screen
            $themecss = str_replace(["media print","color : fff"], ["media p","color : #fff"], $themecss);
            $printcss = file_get_contents('themes/base_files/css/printqueries.css'); //for bootstrap print hidden, screen hidden styles on divs
            $bodycss = '';
        }

        $pdfPages = $this->getPDFPages($html, $pdfSettings);
        $cssStyles = str_replace([".tiki","opacity: 0;","page-break-inside: avoid;"], ["","fill: #fff;opacity:0.3;stroke:black","page-break-inside: auto;"], '<style>' . $basecss . $themecss . $printcss . $extcss . $this->bootstrapReplace() . $prefs["header_custom_css"] . '</style>'); //adding css styles with first page content
        //PDF import templates will not work if background color is set, need to replace in css
        if (
            array_filter(array_column($pdfPages, 'pageContent'), function ($var) {
                return preg_match("/\bpdfinclude\b/i", $var);
            })
        ) {
            $cssStyles = str_replace(["background-color: #fff;","background:#fff;"], "background:none", $cssStyles);
        }
        //cover page checking
        if ($pdfSettings['coverpage_text_settings'] != '' || ($pdfSettings['coverpage_image_settings'] != '' && $pdfSettings['coverpage_image_settings'] != 'off')) {
            $coverPage = explode("|", $pdfSettings['coverpage_text_settings']);
            $coverImage = $pdfSettings['coverpage_image_settings'] != 'off' ? $pdfSettings['coverpage_image_settings'] : '';
            $mpdf->SetHTMLHeader();     //resetting header footer for cover page
            $mpdf->SetHTMLFooter();
            $mpdf->AddPage($pdfSettings['orientation'], '', '', '', '', 0, 0, 0, 0, 0, 0); //adding new page with 0 margins
            $coverPage[2] = $coverPage[2] == '' ? 'center' : $coverPage[2];
            //getting border settings
            if (count($coverPage) > 5) {
                $borderWidth = $coverPage[5] == '' ? 1 : $coverPage[5];
                $coverPageTextStyles = 'border:' . $borderWidth . ' solid ' . $coverPage[6] . ';';
            } else {
                $coverPageTextStyles = '';
            }
            $bgColor = $coverPage[3] == '' ? 'background-color:' . $coverPage[3] : '';
            $mpdf->WriteHTML('<body style="' . $bgColor . ';margin:0px;padding:0px"><div style="height:100%;background-image:url(' . $coverImage . ');padding:20px;background-repeat: no-repeat;background-position: center; "><div style="' . $coverPageTextStyles . 'height:95%;">
<div style="text-align:' . $coverPage[2] . ';margin-top:30%;color:' . $coverPage[4] . '"><div style=margin-bottom:10px;font-size:50px>' . $coverPage[0] . '</div>' . $coverPage[1] . '</div></div></body>');
        }
        //Checking bookmark
        if (is_array($pdfSettings['autobookmarks'])) {
            $mpdf->h2bookmarks = $pdfSettings['autobookmarks'];
        }
        $pageNo = 1;
        $pagesTotal = 1;
        $pdfLimit = ini_get('pcre.backtrack_limit');
        //end of coverpage generation
        foreach ($pdfPages as $pdfPage) {
            $resetPage = '';
            if ($pageNo == 1) {
                $resetPage = 1;
            }

            if (trim(strtolower($pdfPage['footer'])) != "off") {
                $mpdf->DefHTMLFooterByName(
                    'footer-without-pagination',
                    $this->processHeaderFooter($pdfPage['footer'], $params['page'] ?? '', 'bottom', false)
                );
            }

            if (strip_tags(trim($pdfPage['pageContent']), "img,pdfinclude") != '') { //including external pdf
                if (strpos($pdfPage['pageContent'], "<pdfinclude")) {
                    //getting src
                    $breakPageContent = str_replace(["<pdfpage>.","</pdfpage>","<pdfinclude src=","/>","\""], "", $pdfPage['pageContent']);
                    $breakPageContent = trim($breakPageContent);

                    if ($prefs['auth_token_access'] === 'y') {
                        global $tikiroot;
                        $fileId = 0;
                        if (preg_match('/dl(\d+)/', $breakPageContent, $parts)) {
                            $fileId = isset($parts[1]) ? $parts[1] : 0;
                            $params = ['fileId' => $fileId];
                            $tokenParam = '?TOKEN';
                        }
                        if (preg_match('/display(\d+)/', $breakPageContent, $parts)) {
                            $fileId = isset($parts[1]) ? $parts[1] : 0;
                            $params = ['fileId' => $fileId, 'display' => ''];
                            $tokenParam = '?TOKEN';
                        }
                        if (preg_match('/fileId=(\d+)/', $breakPageContent, $parts)) {
                            $fileId = isset($parts[1]) ? $parts[1] : 0;
                            $params = ['fileId' => $fileId];
                            $tokenParam = '&TOKEN';
                        }
                        if (preg_match('/fileId=(\d+)(.*)display/', $breakPageContent, $parts)) {
                            $fileId = isset($parts[1]) ? $parts[1] : 0;
                            $params = ['fileId' => $fileId, 'display' => ''];
                            $tokenParam = '&TOKEN';
                        }

                        if ($fileId > 0) {
                            $perms = Perms::get();
                            require_once 'lib/auth/tokens.php';
                            $tokenlib = AuthTokens::build($prefs);
                            $token = $tokenlib->createToken(
                                $tikiroot . 'tiki-download_file.php',
                                $params,
                                $perms->getGroups(),
                                ['timeout' => 72000, 'hits' => 1]
                            );

                            $breakPageContent = htmlspecialchars_decode($breakPageContent) . $tokenParam . '=' . $token;
                        }
                    }

                    $tmpExtPDF = "temp/tmp_" . rand(0, 999999999) . ".pdf";
                    file_put_contents($tmpExtPDF, fopen($breakPageContent, 'r'));
                    chmod($tmpExtPDF, 0755);
                    $finfo = finfo_open(FILEINFO_MIME_TYPE); //recheck if its valid pdf file
                    if (finfo_file($finfo, $tmpExtPDF) === 'application/pdf') {
                        try {
                            $pagecount = $mpdf->setSourceFile(
                                $tmpExtPDF
                            ); //temp file name
                            for ($i = 1; $i <= $pagecount; $i++) {
                                $tplId = $mpdf->importPage($i);
                                $size = $mpdf->getTemplateSize($tplId);
                                $orientation = isset($size['orientation']) ? $size['orientation'] : '';

                                $mpdf->SetHTMLHeader();
                                $mpdf->AddPage($orientation);
                                $mpdf->SetHTMLFooter();
                                $mpdf->UseTemplate($tplId);
                            }
                        } catch (Exception $e) {
                            $mpdf->WriteHTML("PDF not supported");
                        }
                    }
                    unlink($tmpExtPDF);
                } else {
                    //checking header and footer
                    if (trim(strtolower($pdfPage['header'])) == "off") {
                        $header = "";
                    } else {
                        $pdfPage['header'] == '' ? $header = $pdfSettings['header'] : $header = $pdfPage['header'];
                    }
                    if (trim(strtolower($pdfPage['footer'])) == "off" || $pdfPage['footer'] == "") {
                        $footer = "";
                    } elseif ($pdfPage['footer']) {
                        $footer = $pdfPage['footer'];
                    }
                    $mpdf->SetHTMLHeader($this->processHeaderFooter($header, $params['page'] ?? ''));
                    $mpdf->AddPage($pdfPage['orientation'], '', $resetPage, '', '', $pdfPage['margin_left'], $pdfPage['margin_right'], $pdfPage['margin_top'], $pdfPage['margin_bottom'], $pdfPage['margin_header'], $pdfPage['margin_footer'], '', '', '', '', '', '', '', '', '', $pdfPage['pagesize']);
                    $mpdf->SetHTMLFooter($this->processHeaderFooter($footer, $params['page'] ?? '', 'top')); //footer needs to be reset after page content is added
                    //checking watermark on page
                    $mpdf->SetWatermarkText($pdfPage['watermark']);
                    $mpdf->showWatermarkText = true;
                    $mpdf->SetWatermarkImage($pdfPage['watermark_image'], 0.15, '');
                    if ($pdfPage['background_image']) {
                        $mpdf->SetWatermarkImage($pdfPage['background_image'], 1);
                        $mpdf->watermarkImgBehind = true;
                    }
                    $mpdf->showWatermarkImage = true;
                    //hyperlink check
                    if ($pdfPage['hyperlinks'] != "") {
                        $pdfPage['pageContent'] = $this->processHyperlinks($pdfPage['pageContent'], $pdfPage['hyperlinks'], $pageCounter++);
                    }
                    if ($pdfPage['columns'] > 1) {
                        $mpdf->SetColumns($pdfPage['columns'], 'justify');
                    } else {
                        $mpdf->SetColumns(1, 'justify');
                    }
                    $backgroundImage = '';
                    if (isset($_GET['display']) && strstr($_GET['display'], 'pdf') !== false) {
                        $bgColor = "background: linear-gradient(top, '','');";
                    }
                    if ($pdfPage['background'] != '') {
                        $bgColor = "background: linear-gradient(top, " . $pdfPage['background'] . ", " . $pdfPage['background'] . ");";
                    }
                    $pdfPage['pageContent'] = $this->getHtmlLayout($pdfPage['pageContent']);

                    $mpdf->WriteHTML('<html><body class="' . $bodycss . '" style="margin:0px;padding:0px;">' . $cssStyles);
                    $pagesTotal += floor(strlen($pdfPage['pageContent']) / 3000);
                    //checking if page content is less than mPDF character limit, otherwise split it and loop to writeHTML
                    for ($charLimit = 0; $charLimit <= strlen($pdfPage['pageContent']); $charLimit += $pdfLimit) {
                        $content_slice = substr($pdfPage['pageContent'], $charLimit, $pdfLimit);
                        if ($content_slice) {
                            $mpdf->WriteHTML($content_slice);
                        }
                    }
                    $mpdf->WriteHTML('</body></html>');
                    $pageNo++;
                    $cssStyles = ''; //set to blank after added with first page
                }
            }
        }
        //resetting header,footer
        if ($pdfPages[count($pdfPages) - 1]['background_image']) {
            $mpdf->SetWatermarkImage($pdfPage['background_image'], 1);
            $mpdf->watermarkImgBehind = true;
        }
        trim(strtolower($pdfSettings['header'])) == "off" ? $mpdf->SetHTMLHeader() : $mpdf->SetHTMLHeader($this->processHeaderFooter($pdfSettings['header'], $params['page'] ?? ''));
        if (is_array($pdfPages) && ! empty($pdfPages) && $pdfPages[count($pdfPages) - 1]['footer'] == $pdfSettings['footer']) {
            isset($pdfSettings['footer']) && trim(strtolower($pdfSettings['footer'])) == "off" ? $mpdf->SetHTMLFooter() : $mpdf->SetHTMLFooter($this->processHeaderFooter($pdfSettings['footer'] ?? '', $params['page'] ?? '', 'top'));
        }

        $this->clearTempImg($tempImgArr);
        $tempFile = fopen("temp/public/pdffile_" . session_id() . ".txt", "w");
        fwrite($tempFile, ($pagesTotal * 30));
        return $mpdf->Output('', 'S');                  // Return as a string
    }

    public function getHtmlLayout($pageContent)
    {
        require_once('tiki-setup.php');
        $prefslib = TikiLib::lib('prefs');

        $modlib = TikiLib::lib('mod');

        include_once('tiki-module_controls.php');
        global $prefs, $user;

        clearstatcache();

        $modules_to_print = $prefs['print_pdf_modules'];

        $print_pdf_modules = $prefslib->getPreference('print_pdf_modules');
        if (isset($print_pdf_modules['options'])) {
            $print_pdf_modules_options = $print_pdf_modules['options'];
        } else {
            $print_pdf_modules_options = [];
        }

        $modules_to_print_contents = [];

        $modules = $modlib->get_modules_for_user($user);

        $modnames = [];

        if ($print_pdf_modules_options && is_array($print_pdf_modules_options)) {
            foreach ($print_pdf_modules_options as $module_key => $module_value) {
                if (is_string($module_key)) {
                    if (is_array($modules_to_print) && in_array($module_key, $modules_to_print)) {
                        $content = '';

                        if (isset($modules[$module_key]) && is_array($modules[$module_key])) {
                            foreach ($modules[$module_key] as & $mod_reference) {
                                $ref = (array) $mod_reference;
                                $mod_reference['data'] = new Tiki_Render_Lazy(
                                    function () use ($ref) {
                                        $modlib = TikiLib::lib('mod');
                                        return $modlib->execute_module($ref);
                                    }
                                );
                                $modnames[$ref['name']] = '';
                            }

                            $content = implode(
                                '',
                                array_map(
                                    function ($module) {
                                        return (isset($module['data']) ? $module['data'] : '');
                                    },
                                    $modules[$module_key]
                                )
                            );
                        }

                        $dir = '';
                        if (Language::isRTL()) {
                            $dir = ' dir="rtl"';
                        }

                        $modules_to_print_contents[$module_key] = <<<OUT
                        <div class="modules" id="$module_key" $dir>
                            $content
                        </div>
                        OUT;
                    } else {
                        $modules_to_print_contents[$module_key] = "";
                    }
                }
            }
        }

        $htmlLayout["staringPart"] = '';
        $htmlLayout["endingPart"] = '';

        if ($modules_to_print_contents['top_modules']) {
            $htmlLayout["staringPart"] = $htmlLayout["staringPart"] . '<div class="col-xs-12">' . $modules_to_print_contents['top_modules'] . '</div>';
        }
        if ($modules_to_print_contents['topbar_modules']) {
            $htmlLayout["staringPart"] = $htmlLayout["staringPart"] . '<div class="col-xs-12">' . $modules_to_print_contents['topbar_modules'] . '</div>';
        }

        $htmlLayout["staringPart"] = $htmlLayout["staringPart"] . '<div class="row">';

        if ($modules_to_print_contents['left_modules'] || $modules_to_print_contents['right_modules']) {
            $sideColumn = 'col-xs-4';

            if ($modules_to_print_contents['left_modules'] && $modules_to_print_contents['right_modules']) {
                $sideColumn = 'col-xs-2';
            }

            if ($modules_to_print_contents['left_modules']) {
                $htmlLayout["staringPart"] = $htmlLayout["staringPart"] . '<div class="' . $sideColumn . '">' . $modules_to_print_contents['left_modules'] . '</div>';
            }

            $htmlLayout["staringPart"] = $htmlLayout["staringPart"] . '<div class="col-xs-8">';

            if ($modules_to_print_contents['pagetop_modules']) {
                $htmlLayout["staringPart"] = $htmlLayout["staringPart"] . '<div>' . $modules_to_print_contents['pagetop_modules'] . '</div>';
            }

            if ($modules_to_print_contents['pagebottom_modules']) {
                $htmlLayout["endingPart"] = $htmlLayout["endingPart"] . '<div>' . $modules_to_print_contents['pagebottom_modules'] . '</div>';
            }

            $htmlLayout["endingPart"] = $htmlLayout["endingPart"] . '</div>';

            if ($modules_to_print_contents['right_modules']) {
                $htmlLayout["endingPart"] = $htmlLayout["endingPart"] . '<div class="' . $sideColumn . '">' . $modules_to_print_contents['right_modules'] . '</div>';
            }
        } else {
            if ($modules_to_print_contents['pagetop_modules']) {
                $htmlLayout["staringPart"] = $htmlLayout["staringPart"] . '<div class="col-xs-12">' . $modules_to_print_contents['pagetop_modules'] . '</div>';
            }
            if ($modules_to_print_contents['pagebottom_modules']) {
                $htmlLayout["endingPart"] = $htmlLayout["endingPart"] . '<div class="col-xs-12">' . $modules_to_print_contents['pagebottom_modules'] . '</div>';
            }
        }

        $htmlLayout["endingPart"] = $htmlLayout["endingPart"] . '</div>';


        //check if Module contains navbar and force display (when printing nav is by default display none)
        if (str_contains($htmlLayout["staringPart"], '<nav') || str_contains($htmlLayout["endingPart"], '<nav')) {
            $pageContent = str_replace("<body>", "<style>.navbar {display: block;}</style><body>", $pageContent);
        }

        if ($modules_to_print_contents['bottom_modules']) {
            $htmlLayout["endingPart"] = $htmlLayout["endingPart"] . '<div class="col-xs-12">' . $modules_to_print_contents['bottom_modules'] . '</div>';
        }

        $pageContent = str_replace("<body>", "<body style='margin:0px;padding:0px;'>" . $htmlLayout["staringPart"], $pageContent);
        $pageContent = str_replace("</body>", $htmlLayout["endingPart"] . "</body>", $pageContent);

        return $pageContent;
    }

    public function getPDFSettings($html, $prefs, $params)
    {
        $pdfSettings = [];
        if (! empty($html)) {
            //checking if pdf plugin is set and passed
            $html = cleanHtml($html, null, 'utf8');
            $doc = loadHTMLContent($html);

            $pdf = $doc->getElementsByTagName('pdfsettings')->item(0);
            $prefs['print_pdf_mpdf_pagesize'] = $prefs['print_pdf_mpdf_size'];
            if ($pdf) {
                if ($pdf->hasAttributes()) {
                    foreach ($pdf->attributes as $attr) {
                        //overridding global settings
                        $prefs['print_pdf_mpdf_' . $attr->nodeName] = $attr->nodeValue;
                    }
                }
            }
            //checking preferences
            $pdfSettings['print_pdf_mpdf_printfriendly'] = $prefs['print_pdf_mpdf_printfriendly'] != '' ? $prefs['print_pdf_mpdf_printfriendly'] : '';
            $orientation = ! empty($params['orientation']) ? $params['orientation'] : $prefs['print_pdf_mpdf_orientation'];
            $pdfSettings['orientation'] = $orientation != '' ? $orientation : 'P';
            $pdfSettings['pagesize'] = $prefs['print_pdf_mpdf_pagesize'] != '' ? $prefs['print_pdf_mpdf_pagesize'] : 'Letter';

            if (in_array($pdfSettings['pagesize'], ['Tabloid/Ledger', 'Tabloid-Ledger'])) {
                $pdfSettings['pagesize'] = 'Tabloid';
            }

            //custom size needs to be passed for Tabloid
            if ($prefs['print_pdf_mpdf_size'] == "Tabloid") {
                $pdfSettings['pagesize'] = [279,432];
            } elseif ($pdfSettings['orientation'] == 'L') {
                $pdfSettings['pagesize'] = $pdfSettings['pagesize'] . '-' . $pdfSettings['orientation'];
            }

            $pdfSettings['margin_left'] = $prefs['print_pdf_mpdf_margin_left'] != '' ? $prefs['print_pdf_mpdf_margin_left'] : '10';
            $pdfSettings['margin_right'] = $prefs['print_pdf_mpdf_margin_right'] != '' ? $prefs['print_pdf_mpdf_margin_right'] : '10';
            $pdfSettings['margin_top'] = $prefs['print_pdf_mpdf_margin_top'] != '' ? $prefs['print_pdf_mpdf_margin_top'] : '10';
            $pdfSettings['margin_bottom'] = $prefs['print_pdf_mpdf_margin_bottom'] != '' ? $prefs['print_pdf_mpdf_margin_bottom'] : '10';
            $pdfSettings['margin_header'] = $prefs['print_pdf_mpdf_margin_header'] != '' ? $prefs['print_pdf_mpdf_margin_header'] : '5';
            $pdfSettings['margin_footer'] = $prefs['print_pdf_mpdf_margin_footer'] != '' ? $prefs['print_pdf_mpdf_margin_footer'] : '5';
            $pdfSettings['header'] = str_ireplace("{PAGETITLE}", $params['page'] ?? '', $prefs['print_pdf_mpdf_header']);
            $pdfSettings['footer'] = str_ireplace("{PAGETITLE}", $params['page'] ?? '', $prefs['print_pdf_mpdf_footer']);
            $pdfSettings['print_pdf_mpdf_password'] = $prefs['print_pdf_mpdf_password'];
            $pdfSettings['toc'] = $prefs['print_pdf_mpdf_toc'] != '' ? $prefs['print_pdf_mpdf_toc'] : 'n';
            $pdfSettings['toclinks'] = $prefs['print_pdf_mpdf_toclinks'] != '' ? $prefs['print_pdf_mpdf_toclinks'] : 'n';
            $pdfSettings['tocheading'] = $prefs['print_pdf_mpdf_tocheading'];
            $pdfSettings['pagetitle'] = $prefs['print_pdf_mpdf_pagetitle'];
            $pdfSettings['watermark'] = $prefs['print_pdf_mpdf_watermark'];
            $pdfSettings['watermark_image'] = $prefs['print_pdf_mpdf_watermark_image'];
            $pdfSettings['coverpage_text_settings'] = str_ireplace("{PAGETITLE}", $params['page'] ?? '', $prefs['print_pdf_mpdf_coverpage_text_settings']);
            $pdfSettings['coverpage_image_settings'] = str_ireplace("{PAGETITLE}", $params['page'] ?? '', $prefs['print_pdf_mpdf_coverpage_image_settings']);
            $pdfSettings['hyperlinks'] = $prefs['print_pdf_mpdf_hyperlinks'];
            $pdfSettings['columns'] = $prefs['print_pdf_mpdf_columns'];
            $pdfSettings['background'] = $prefs['print_pdf_mpdf_background'];
            $pdfSettings['background_image'] = $prefs['print_pdf_mpdf_background_image'];
            $pdfSettings['autobookmarks'] = $prefs['print_pdf_mpdf_autobookmarks'];

            if ($pdfSettings['toc'] == 'y') {
                //toc levels
                ['H1' => 0, 'H2' => 1, 'H3' => 2];
                $toclevels = $prefs['print_pdf_mpdf_toclevels'] != '' ? $prefs['print_pdf_mpdf_toclevels'] : 'H1|H2|H3';
                $toclevels = explode("|", $toclevels);
                $pdfSettings['toclevels'] = [];
                for ($toclevel = 0; $toclevel < count($toclevels); $toclevel++) {
                    $pdfSettings['toclevels'][$toclevels[$toclevel]] = $toclevel;
                }
            }

            //Setting PDF bookmarks
            if ($pdfSettings['autobookmarks']) {
                $bookmark = explode("|", $pdfSettings['autobookmarks']);
                $pdfSettings['autobookmarks'] = [];
                for ($level = 0; $level < count($bookmark); $level++) {
                    $pdfSettings['autobookmarks'][strtoupper($bookmark[$level])] = $level;
                }
            }
            //PDF settings
            return $pdfSettings;
        }
    }

    //mpdf read page for plugin PDFPage, introduced for advanced pdf creation
    public function getPDFPages($html, $pdfSettings)
    {
        //checking if pdf page tag exists
        $html = cleanHtml($html, null, 'utf8');
        $doc = loadHTMLContent($html);
        $xpath = new DOMXpath($doc);
        //Getting pdf page custom pages from content
        $pdfPages = $doc->getElementsByTagName('pdfpage');
        $pageData = [];
        $mainContent = $html;
        foreach ($pdfPages as $page) {
            $pages = [];
            $pageTag = "<pdfpage";
            if ($page->hasAttributes()) {
                foreach ($page->attributes as $attr) {
                    $pages[$attr->nodeName] = $attr->nodeValue;
                    $paramVal = str_replace("&quot;", '"', htmlentities($attr->nodeValue, ENT_COMPAT));
                    strchr($paramVal, '"') ? $enclosingChar = "'" : $enclosingChar = "\"";
                    $pageTag .= " " . $attr->nodeName . "=" . $enclosingChar . $paramVal . $enclosingChar;
                }
            }
            $pageTag .= ">";
        //mapping empty values with defaults
            foreach ($pdfSettings as $setting => $value) {
                if ($pages[$setting] == "") {
                    $pages[$setting] = $value;
                }
            }

            if (in_array($pages['pagesize'], ['Tabloid/Ledger', 'Tabloid-Ledger'])) {
                $pages['pagesize'] = 'Tabloid';
            }

            if ($pages['pagesize'] == "Tabloid") {
                $pages['pagesize'] = [279,432];
            } elseif ($pages['orientation'] == 'L') {
                $pages['pagesize'] = $pages['pagesize'] . '-' . $pages['orientation'];
            }
            //dividing content in segments
            $ppages = explode($pageTag, $mainContent, 2);
            $lpages = explode("</pdfpage>", $ppages[1], 2);

            //for prepage settings pdfsettings will be used
            if (preg_replace('~<(?:!DOCTYPE|/?(?:html|body))[^>]*>\s*~i', '', $ppages[0]) != "") {
                $prePage = $pdfSettings;
                $prePage['pageContent'] = $ppages[0];
                $pageData[] = $prePage;
            }
            $pages['pageContent'] = $doc->saveXML($page);
            $pageData[] = $pages;
            if (trim(strip_tags($lpages[1])) != "") {
                $mainContent = $lpages[1];
            }
        }
        //no pages found
        if (count($pageData) == 0) {
            $defaultPage = $pdfSettings;
            $defaultPage['pageContent'] = $html;
            $pageData[] = $defaultPage;
        } elseif (trim(strip_tags($lpages[1])) != '') { //adding and resetting options for last page if any
            $lastPage = $pdfSettings;
            $lastPage['pageContent'] = $lpages[1];
            $pageData[] = $lastPage;
        }
        return $pageData;
    }

    public function _getImages(&$html, &$tempImgArr)
    {
        $html = cleanHtml($html, null, 'utf8');
        $html = str_replace('&amp;#x', '&#x', $html);
        $doc = loadHTMLContent($html);

        $tags = $doc->getElementsByTagName('img');

        foreach ($tags as $tag) {
            $imgSrc = $tag->getAttribute('src');
            //bypassing base64 encoded images
            if (! strstr($imgSrc, ';base64')) {
                //replacing image with new temp image, all these images will be unlinked after pdf creation
                $newFile = $this->file_get_contents_by_fget($imgSrc);
                //replacing old protected image path with temp image
                if ($newFile != '') {
                    $tag->setAttribute('src', $newFile);
                }
                $tempImgArr[] = $newFile;
            }
        }

        $html = @$doc->saveHTML();
    }

    /**
     * Fetch the contents of a URL using file_get_contents and save it as a temporary image file.
     *
     * @param string $url The URL of the image to fetch.
     * @return string Return the path of the newly created temporary image file.
     */
    public function file_get_contents_by_fget($url): string
    {
        global $base_url;
        //check if image is internal with full path
        $internalImg = 0;
        if (substr($url, 0, strlen($base_url)) == $base_url) {
            $internalImg = 1;
        }
        //checking for external images
        $checkURL = parse_url($url);
        //not replacing in case of external image
        if ((isset($checkURL['scheme']) && ($checkURL['scheme'] == 'https' || $checkURL['scheme'] == 'http')) && ! $internalImg) {
            return '';
        }
        if (! $internalImg) {
            $url = $base_url . $url;
        }
        if (! file_exists('temp/pdfimg')) {
            mkdir('temp/pdfimg');
            chmod('temp/pdfimg', 0755);
        }
        $cookie = isset($_SERVER['HTTP_COOKIE']) ? $_SERVER['HTTP_COOKIE'] : '';
        $opts = [];
        if (! empty($cookie)) {
            $opts['http'] = ['header' => 'Cookie: ' . $cookie . "\r\n"];
        }
        $context = stream_context_create($opts);
        session_write_close();
        $data = @file_get_contents($url, false, $context);
        if (gettype($data) == 'boolean' && ! $data) {
            return '';
        }
        $newFile = 'temp/pdfimg/pdfimg' . mt_rand(9999, 999999) . '.png';
        file_put_contents($newFile, $data);
        chmod($newFile, 0755);
        return $newFile;
    }

    public function clearTempImg($tempImgArr)
    {
        foreach ($tempImgArr as $tempImg) {
            if (file_exists($tempImg)) {
                unlink($tempImg);
            }
        }
    }

    public function _parseHTML(&$html)
    {
        //Replace all word separators as this is already fixed with CSS
        $html = str_replace(["<wbr>", "<wbr/>"], "", $html);

        $html = cleanHtml($html, null, 'utf8');
        $doc = loadHTMLContent($html);

        $tables = $doc->getElementsByTagName('table');
        $tempValue = [];
        $sortedContent = [];
        foreach ($tables as $table) {
            $this->sortContent($table, $tempValue, $sortedContent, $table->tagName);
        }
        $xpath = new DOMXpath($doc);

        // remove d-print-none elements
        $elements = $xpath->query('//*[contains(@class, "d-print-none")]');
        foreach ($elements as $element) {
            $element->parentNode->removeChild($element);
        }

        //defining array of plugins to be sorted
        $pluginArr = [["class","customsearch_results","div"],["id","container_pivottable","div"],["class","dynavar","a"], ["class", "tiki_sheet", "div"]];
        $tagsArr = [["input","tablesorter-filter","class"],["select","tablesorter-filter","class"],["select","pvtRenderer","class"],["select","pvtAggregator","class"],["td","pvtCols","class"],["td","pvtUnused","class"],["td","pvtRows","class"],["div","plot-container","class",true],["a","heading-link","class"],["a","tablename","class","1"], ["div", "jSScroll", "class"], ["span", "jSTabContainer", "class"], ["a", "tiki_sheeteditbtn", "class"],["div","comment-footer","class"],["div","buttons comment-form","class"],["div","clearfix tabs","class"],["a","pvtRowOrder","class"],["a","pvtColOrder","class"],["select","pvtAttrDropdown","class"], ["div", "modebar-container", "class"], ["div", "gl-container", "class"]];

        foreach ($pluginArr as $pluginInfo) {
            $customdivs = $xpath->query('//*[contains(@' . $pluginInfo[0] . ', "' . $pluginInfo[1] . '")]');
            for ($i = 0; $i < $customdivs->length; $i++) {
                if ($pluginInfo[1] == "dynavar") {
                    $dynId = str_replace("display", "edit", $customdivs->item($i)->parentNode->getAttribute('id'));
                    $tagsArr[] = ["span",$dynId,"id"];
                } else {
                    $customdiv = $customdivs->item($i);
                    $this->sortContent($customdiv, $tempValue, $sortedContent, $pluginInfo[2]);
                }
            }
        }
        $html = @$doc->saveHTML();
        //replacing temp table with sorted content
        for ($i = 0; $i < count($sortedContent); $i++) {
            $html = str_replace($tempValue[$i], $sortedContent[$i], $html);
        }
        $html = cleanContent($html, $tagsArr);

        //making tablesorter and pivottable charts wrapper divs visible
        $doc = loadHTMLContent($html);
        $this->checkLargeTables($doc); //hack function for large data columns
        $xpath = new DOMXpath($doc);
        $wrapperDefs = [["class","ts-wrapperdiv","visibility:visible"],["id","png_container_pivottable","display:none"]];
        foreach ($wrapperDefs as $wrapperDef) {
            $wrapperdivs = $xpath->query('//*[contains(@' . $wrapperDef[0] . ', "' . $wrapperDef[1] . '")]');
            for ($i = 0; $i < $wrapperdivs->length; $i++) {
                $wrapperdiv = $wrapperdivs->item($i);
                $wrapperdiv->setAttribute("style", $wrapperDef[2]);
            }
        }
        $html = @$doc->saveHTML();
        //font awesome support call
        $this->fontawesome($html);
        //& sign added in fa unicodes for proper printing in pdf
        $html = str_replace('#x', "&#x", $html);
    }

    private function checkLargeTables(&$doc)
    {
        //new code to split table large cells
        foreach ($doc->getElementsByTagName('table') as $table) {
            // iterate over each row in the table
            $trs = $table->getElementsByTagName('tr');
            $cloneArr = [];
            foreach ($trs as $tr) {
                $cloned = 0;
                foreach ($tr->getElementsByTagName('td') as $td) { // get the columns in this row
                    if (strlen($td->textContent) > 2000) {
                        $longValue = $td->nodeValue;
                        $breaktill = strpos($td->nodeValue, '.', 1000);
                        if ($cloned == 0) {
                            $cloneNode = $tr->cloneNode(true);
                            $cloned = 1;
                            $cloneArr[] = ["node" => $cloneNode,'row' => $tr,'breaktill' => $breaktill];
                        }
                        $td->textContent = substr($longValue, 0, $breaktill) . '. (cont.)';
                        $td->setAttribute("style:", "white-space: nowrap");
                        $td->setAttribute("width", "20%");
                    }
                }
            }

            //here insert new nodes
            foreach ($cloneArr as $cloneData) {
                $this->insertNewNodes($cloneData, $table);  //this will be recursive function to split row multiple times if needed
            }
        }
        $html = @$doc->saveHTML();
    }

    private function insertNewNodes(&$cloneData, &$table, $start = 1000)
    {

        //processing cloneNodes
        $cloned = 0;
        foreach ($cloneData['node']->getElementsByTagName('td') as $td) {
                $longValue = $td->textContent;
            if (strlen($longValue) > $start) {
                $breaktill = strpos($longValue, '.', $start); //starting point after first fullstop
                if (strlen($longValue) > ($breaktill + 1000)) {
                    $endPoint = $breaktill + 1000;
                    $end = strpos($longValue, '.', $endPoint) - $breaktill; //end point till last sentence
                } else {
                    $end = 1000;
                }

                if (strlen($longValue) > $end + $breaktill && $cloned == 0) {
                    $cloned = 1;
                    $newNode = [];
                    $newNode['node'] = $cloneData['node']->cloneNode(true);
                    $newNode['row'] = $cloneData['node'];
                }
                $td->textContent = '(cont\'d)' . substr($longValue, $breaktill + 1, $end);
            } else {
                $td->textContent = '';
            }
        }

        try {
            $cloneData['row']->parentNode->insertBefore($cloneData['node'], $cloneData['row']->nextSibling);
        } catch (\Exception $e) {
            $table->appendChild($cloneData['node']);
        }

        if ($cloned == 1) {
            $this->insertNewNodes($newNode, $table, $start + 1000);
        }
    }

    public function fontawesome(&$html)
    {
        $html = cleanHtml($html, null, 'utf8');
        $doc = loadHTMLContent($html);
        $xpath = new DOMXpath($doc);
      //font awesome code insertion
        $fadivs = $xpath->query('//*[contains(@class, "fa")]');
       //loading json file if there is any font-awesome tag in html
        if ($fadivs->length) {
            //get the file containing the unicode of each fontawesome generated 'php console.php build:generateiconlist'
            $allFontawesomeIcons = TIKI_PATH . '/' . GENERATED_ICONSET_PATH . '/all_fontawesome_icons.json';
            $faCodes = file_get_contents($allFontawesomeIcons);
            if ($faCodes !== false) {
                $jfo = json_decode($faCodes, true);
                for ($i = 0; $i < $fadivs->length; $i++) {
                    $fadiv = $fadivs->item($i);
                    $faClass = explode(" ", str_replace(["fa-", "-"], ["","_"], $fadiv->getAttribute('class')));
                    foreach ($faClass as $class) {
                        if (! empty($jfo[$class]['codeValue'])) {
                            $faCode = $doc->createElement('span', " " . $jfo[$class]['codeValue']);
                            $faCode->setAttribute("style", "float:left;padding-left:5px;" . $fadiv->getAttribute('style'));
                            //span with fontawesome code inserted before fa div
                            $faCode->setAttribute("class", $fadiv->getAttribute('class'));
                            if ($fadiv->parentNode !== null) {
                                $fadiv->parentNode->insertBefore($faCode, $fadiv);
                                $fadiv->parentNode->removeChild($fadiv);
                            }
                        }
                    }
                }
            }
        }

        $html = @$doc->saveHTML();
    }

    public function bootstrapReplace()
    {
        return <<<TEXT
.col-xs-12 { width: 100%; float:left; }
.col-xs-11 { width: 81.66666667%; float:left; }
.col-xs-10 { width: 72%; float:left; }
.col-xs-9 { width: 64%; float:left; }
.col-xs-8 { width: 62%; float:left; }
.col-xs-7 { width: 49%; float:left; }
.col-xs-6 { width: 45.7%; float:left; }
.col-xs-5 { width: 35%; float:left; }
.col-xs-4 { width: 28%; float:left; }
.col-xs-3 { width: 20%; float:left; }
.col-xs-2 { width: 12.2%; float:left; }
.col-xs-1 { width: 3.92%; float:left; }
.table-striped { border: 0.75pt solid #ccc; }
.table-striped td { 
    padding: 6pt;
    line-height: 1.42857143;
    vertical-align: center;
    border-top: 0.75pt solid #ccc; 
}
.table-striped th { 
    padding: 7.5pt;
    line-height: 1.42857143;
    vertical-align: center;
}
.table-striped .odd { padding: 7.5pt; }
.table-striped .even { padding: 7.5pt; }
.trackerfilter form, .list_filter form { display: none; }
table.pvtTable tr td { border: 0.75pt solid; }
.wp-sign {
    position: relative;
    display: block;
    background-color: #fff;
    color: #666;
    font-size: 7.5pt;
}
.wp-sign a, .wp-sign a:visited { color: #999; }
.icon-link-external { margin-left: 7.5pt; font-size: 7.5pt; }
.ui-widget-content { width: 100% }
.ui-widget-content td { border: solid 0.75pt #ccc; padding: 3.75pt; }
.jSBarLeft { width: 22.5pt; }
.dl-horizontal dt {
    float: left;
    width: 120pt;
    clear: left;
    text-align: right;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.dl-horizontal dd { margin-left: 135pt; }
.media-left, .media-right, .media-body {
    border: none !important;
    float: left;
    display: inline-block;
    width: 41.25pt;
}
.media-body { width: 80%; }
.media comment { clear: both; }
TEXT;
    }

    public function sortContent(&$table, &$tempValue, &$sortedContent, $tag)
    {
        $content = '';
        $tid = $table->getAttribute("id");


        if (file_exists("temp/#" . $tid . "_" . session_id() . ".txt")) {
            $content = mb_convert_encoding(file_get_contents("temp/#" . $tid . "_" . session_id() . ".txt"), 'HTML-ENTITIES', 'UTF-8');
         //formating content
            $tableTag = "<" . $tag;
            if ($table->hasAttributes()) {
                foreach ($table->attributes as $attr) {
                     $tableTag .= " " . $attr->nodeName . "=\"" . $attr->nodeValue . "\"";
                }
            }
            $tableTag .= ">";
            $content = str_ireplace('<st<x>yle>', '<style>', $content);
            $content = $tableTag . $content . '</' . $tag . '>';
           //end of cleaning content
            $sortedContent[] = preg_replace('/<sc(<x>)?ript[^>]*>.*?<\/script>/s', '', $content);
            $tempValue[] = $tableTag;
            $table->nodeValue = "";
            chmod("temp/#" . $tid . "_" . session_id() . ".txt", 0755);
            //unlink tmp table file
            unlink("temp/#" . $tid . "_" . session_id() . ".txt");
        }
    }

    public function processHyperlinks($content, $hyperlinkSetting, $pageCounter)
    {
        global $base_url;

        $content = cleanHtml($content, null, 'utf8');
        $doc = loadHTMLContent($content);
        $anchors = $doc->getElementsByTagName('a');
        $len = $anchors->length;
        $hrefDiv = $doc->createElement('div');

        for ($i = 0,$linkCnt = 1; $i < $len; $i++) {
            $anchor = $anchors->item(0);
            if (! is_null($anchor)) {
                $link = $doc->createElement('span', $anchor->nodeValue);
                $link->setAttribute('class', $anchor->getAttribute('class'));
                if ($link->nodeValue == '') {
                    $link = $doc->createDocumentFragment();
                    while ($anchor->childNodes->length > 0) {
                        $link->appendChild($anchor->childNodes->item(0));
                    }
                }
                //checking if links to be added as footnote
                if ($hyperlinkSetting != "off") {
                    // Check if there is a url in the text
                    $linkSup = $doc->createElement("sup");

                    // If link as no host then it is an internal link
                    $urlParts = parse_url($anchor->getAttribute('href'));
                    if (empty($urlParts['host'])) {
                        $anchor->setAttribute('href', $base_url . $anchor->getAttribute('href'));
                    }

                    if (
                        preg_match(
                            "/(http|https)\:\/\/[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,3}(\/\S*)?/",
                            $anchor->getAttribute('href')
                        )
                    ) {
                        $linkAn = $doc->createElement(
                            "hyperanchor",
                            "[" . $linkCnt . "]"
                        );
                        $linkAn->setAttribute(
                            "href",
                            "#" . $pageCounter . "lnk" . $linkCnt
                        );
                        $linkSup->appendChild($linkAn);
                        $link->appendChild($linkSup);
                        $hrefData = $doc->createElement(
                            "a"
                        );
                        $hrefData->textContent = $anchor->getAttribute('href');
                        $hrefData->setAttribute(
                            "name",
                            $pageCounter . "lnk" . $linkCnt
                        );
                        $hrefDiv->setAttribute(
                            "style",
                            "border-top:1px solid #ccc;line-height:1.2em"
                        );
                        $hrefDiv->appendChild(
                            $doc->createElement(
                                "sup",
                                "&nbsp;[" . $linkCnt . "]&nbsp;"
                            )
                        );
                        $hrefDiv->appendChild($hrefData);
                        $hrefDiv->appendChild($doc->createElement("br"));
                        $linkCnt++;
                    }
                }
                $anchor->parentNode->replaceChild($link, $anchor);
            }
        }

        $hrefDiv->setAttribute('class', "footnotearea");
        $doc->getElementsByTagName('body')->item(0)->appendChild($hrefDiv);
        $content = $doc->saveHTML();
        return str_replace("hyperanchor", "a", $content);
    }

    /**
     * Returns the current error
     * @return string
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * Returns the current mode
     * @return string|bool
     */
    public function getMode()
    {
        return $this->mode;
    }

    public function processHeaderFooter($value = '', $page = '', $border = 'bottom', $withPagination = true)
    {
        //evaluating type
        if (strpos($value, '|') !== false) {
            //checking if legacy header/footer is used. Important since not all users are good to add HTML formatted values
            $valueText = explode("|", $value);
            //formatting in table
            $tdStyle = "padding-" . $border . ":5px;width:33%;font-weight:bold;border-" . $border . ":1px solid;font-size:12px;text-align:";

            if ($withPagination) {
                $value = "<table width='100%'><tr><td style='" . $tdStyle . "left;'>" . $valueText[0] . "</td><td style='" . $tdStyle . "center'>" . $valueText[1] . "</td><td style='" . $tdStyle . "right;'>" . $valueText[2] . "</td></tr></table>";
            } else {
                $value = "<table width='100%'><tr><td style='" . $tdStyle . "left;'>" . $valueText[0] . "</td><td style='" . $tdStyle . "right'>" . $valueText[1] . "</td></tr></table>";
            }
        }
        //process and return value
        return str_ireplace(["{PAGETITLE}","{NB}"], [$page,"{nb}"], TikiLib::lib('parser')->parse_data(html_entity_decode($value ?? ''), ['is_html' => true, 'parse_wiki' => true]));
    }
}


/**
 * @param string $html - The data to be parsed
 * @param mixed $config [optional] - array of configuration options applied to the data to be parsed
 * @param string $encoding [optional]
 * The encoding parameter sets the encoding for
 * input/output documents. The possible values for encoding are:
 * ascii, latin0, latin1,
 * raw, utf8, iso2022,
 * mac, win1252, ibm858,
 * utf16, utf16le, utf16be,
 * big5, and shiftjis.
 * @return string
 */
function cleanHtml($html, $config = null, $encoding = 'utf8')
{
    if (extension_loaded('tidy') == true) {
        $default = [
            'clean' => true,
            'output-xhtml' => true,
            'show-body-only' => false,
            'new-blocklevel-tags' => 'pdfsettings pdfpage pdfinclude article aside audio bdi canvas details dialog figcaption figure footer header hgroup main menu menuitem nav section source summary template track video',
            'new-empty-tags' => 'embed keygen source track wbr',
            'new-inline-tags' => 'svg audio command datalist embed mark menuitem meter output progress source time video wbr',
            'repeated-attributes' => 'keep-first',
            'drop-proprietary-attributes' => false,
            'wrap' => 0,
            'coerce-endtags' => true,
            'quote-ampersand' => true,
            'quote-marks' => false,
            'drop-empty-elements' => false,
        ];
        $config = (is_array($config) == true) ? array_merge($default, $config) : $default;
        $html_ = tidy_parse_string($html, $config, $encoding);
        $html_->cleanRepair();

        return (string) $html_;
    }

    return $html;
}

/**
 * Load HTML content into a DOMDocument, suppressing warnings during the operation.
 *
 * @param string $html The HTML content to load.
 * @return DOMDocument The DOMDocument instance containing the parsed HTML.
 */
function loadHTMLContent($html)
{
    $doc = new DOMDocument();
    $errorLevel = error_reporting();

    // Set error reporting to ignore warnings
    error_reporting($errorLevel & ~E_WARNING);

    $doc->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));

    // Restore the previous error reporting level
    error_reporting($errorLevel);

    return $doc;
}

function cleanContent($content, $tagArr)
{
    $content = cleanHtml($content, null, 'utf8');
    $doc = loadHTMLContent($content);
    $xpath = new DOMXpath($doc);

    foreach ($tagArr as $tag) {
        $list = $xpath->query('//' . $tag[0] . '[contains(concat(\' \', normalize-space(@' . $tag[2] . '), \' \'), "' . $tag[1] . '")]');
        for ($i = 0; $i < $list->length; $i++) {
            $p = $list->item($i);
            if (isset($tag[3]) && $tag[3] == 1) { //the parameter checks if content of tag has to be preserved
                $attributes = $p->attributes;
                while ($attributes->length) {
                //preserving href

                    if ($attributes->item(0)->name == "href") {
                        $hrefValue = $attributes->item(0)->value;
                    }
                    $p->removeAttribute($attributes->item(0)->name);
                }
                if ($hrefValue) {
                    $p->setAttribute("href", $hrefValue);
                }
            } else {
                $p->parentNode->removeChild($p);
            }
        }
    }
    return $doc->saveHTML();
}

function add_custom_font_to_mpdf(&$mpdf, $fonts_list)
{
    // Logic from line 1146 mpdf.pdf - $this->available_unifonts = array()...
    foreach ($fonts_list as $f => $fs) {
        // add to fontdata array
        $mpdf->fontdata[$f] = $fs;

        // add to available fonts array
        if (isset($fs['R']) && $fs['R']) {
            $mpdf->available_unifonts[] = $f;
        }
        if (isset($fs['B']) && $fs['B']) {
            $mpdf->available_unifonts[] = $f . 'B';
        }
        if (isset($fs['I']) && $fs['I']) {
            $mpdf->available_unifonts[] = $f . 'I';
        }
        if (isset($fs['BI']) && $fs['BI']) {
            $mpdf->available_unifonts[] = $f . 'BI';
        }
    }
    $mpdf->default_available_fonts = $mpdf->available_unifonts;
}
