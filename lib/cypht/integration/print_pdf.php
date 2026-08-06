<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

require_once 'lib/pdflib.php';

function printPDFFromMessage()
{
    $generator = new PdfGenerator(PdfGenerator::MPDF);
    if (! empty($generator->error)) {
        Feedback::error($generator->error);
    } else {
        if (isset($_POST['uid'])) {
            $uid = $_POST['uid'];
        }
        if (isset($_REQUEST['list_path'])) {
            $list_path = $_REQUEST['list_path'];
        }
        if (isset($_POST['header_subject'])) {
            $header_subject = $_POST['header_subject'];
        }
        if (isset($_POST['header_date'])) {
            $header_date = $_POST['header_date'];
        }
        if (isset($_POST['header_from'])) {
            $header_from = $_POST['header_from'];
        }
        if (isset($_POST['header_to'])) {
            $header_to = $_POST['header_to'];
        }
        if (isset($_POST['msg_text'])) {
            $msg_text = $_POST['msg_text'];
            // Remove common email preheader techniques not well handled by PDF generators
            $patterns = [
              '/<span[^>]*mcnPreviewText[^>]*>.*?<\/span>/is',
              '/<div[^>]*max-height:\s*0[^>]*>.*?<\/div>/is',
              '/<div[^>]*style="[^"]*max-height:\s*0[^"]*"[^>]*>.*?<\/div>/is',
              '/(?:͏|‌|\x{034F}|\x{200C}|\x{200B}|\x{FEFF})/u', // Remove invisible Unicode chars
            ];
            $msg_text = preg_replace($patterns, '', $msg_text);
        }
        if (isset($_POST['header_cc'])) {
            $header_cc = $_POST['header_cc'];
        } else {
            $header_cc = '';
        }

        $contentpage = createWebPage($header_subject, $header_date, $header_from, $header_to, $msg_text, $header_cc);
        $filename = $header_from . '_' . $header_subject;
        $params = [
        'page' => 'messsage',
        'uid' => $uid,
        'list_path' => $list_path
        ];
        $pdata = '<pdfsettings pagetitle="n" printfriendly="n"></pdfsettings>' . $contentpage;

        $pdf = $generator->getPdf('tiki-webmail.php', $params, preg_replace('/%u([a-fA-F0-9]{4})/', '&#x\\1;', $pdata));
        $length = strlen($pdf);
        header('Cache-Control: private, must-revalidate');
        header('Pragma: private');
        header("Content-Description: File Transfer");
        $filename  = preg_replace('/\W+/u', '_', $filename); // Replace non words with underscores for valid file names
        $filename = \TikiLib::lib('tiki')->remove_non_word_characters_and_accents($filename);
        header('Content-disposition: attachment; filename="' . $filename . '.pdf"');
        header("Content-Type: application/pdf");
        header("Content-Transfer-Encoding: binary");
        header('Content-Length: ' . $length);
        echo $pdf;
    }
}

/**
 * creates the HTML page to be print.
 */
function createWebPage($header_subject, $header_date, $header_from, $header_to, $msg_text, $header_cc)
{
    if (empty($header_cc)) {
        $headerCcRow = '';
    } else {
        $headerCcRow = <<<END
        <tr class="header_to">
          <th>Cc</th>
          <td>$header_cc</td>
        </tr>
        END;
    }

    return <<<END
    <!DOCTYPE html>
<html>
<head>
  <meta name="robots" content="noindex, nofollow">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <link type="text/css" rel="stylesheet" href="lib/cypht/site.css" />
  <title></title>
  <style type="text/css">
    body, td {font-size:13px}
    body{background: #fff !important;}
    a:link, a:active {color:#1155CC; text-decoration:none}
    a:hover {text-decoration:underline; cursor: pointer}
    a:visited{color:##6611CC} img{border:0px}
    pre { white-space: pre; white-space: -moz-pre-wrap; white-space: -o-pre-wrap; white-space: pre-wrap; word-wrap: break-word; max-width: 800px; overflow: auto;}
    .logo { left: -7px; position: relative; }
  </style>
</head>
<body class="tiki tiki-webmail tiki-cypht" >
  <main class="content_cell" style="display: table-cell; padding: 35px 35px 0px 35px; margin-bottom: 35px;">
    <div class="msg_text">
      <hr>
      <table>
        <colgroup>
          <col class="header_name_col">
          <col class="header_val_col">
        </colgroup>
        <tbody>
          <tr >
            <td style="padding-left: 35px;">
              <font size="+1"><b>$header_subject</b></font><br>
              <font size="-1" color="#777"></font>
            </td>
          </tr>
        </tbody>
      </table>
      <hr>
      <table class="msg_headers">
        <colgroup>
          <col class="header_name_col">
          <col class="header_val_col">
        </colgroup>
        <tbody>
          <tr class="header_date">
            <th>Date</th>
            <td>$header_date</td>
          </tr>
          <tr class="header_from">
            <th>From</th>
            <td>$header_from</td>
          </tr>
          <tr class="header_to">
            <th>To</th>
            <td>$header_to</td>
          </tr>
          $headerCcRow
          <tr>
            <td class="header_space" colspan="2"></td>
          </tr>
        </tbody>
      </table>
      <div class="msg_text_inner">
        <font size="-1">
          $msg_text
        </font>
      </div>
    </div>
  </main>
</body>
</html>
END;
}
