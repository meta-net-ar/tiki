<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
use Tiki\File\FileHelper;
use Tiki\WikiPlugin\DBReport\Base;
use Tiki\WikiPlugin\DBReport\Token;
use Tiki\WikiPlugin\DBReport\Cell;
use Tiki\WikiPlugin\DBReport\Content;
use Tiki\WikiPlugin\DBReport\Fail;
use Tiki\WikiPlugin\DBReport\Field;
use Tiki\WikiPlugin\DBReport\Group;
use Tiki\WikiPlugin\DBReport\Line;
use Tiki\WikiPlugin\DBReport\Link;
use Tiki\WikiPlugin\DBReport\Parameter;
use Tiki\WikiPlugin\DBReport\Style;
use Tiki\WikiPlugin\DBReport\Table;
use Tiki\WikiPlugin\DBReport\Text;

$wikiplugin_dbreport_errors;
$wikiplugin_dbreport_fields;
$wikiplugin_dbreport_fields_allowed;
$wikiplugin_dbreport_record;

function wikiplugin_dbreport_parse_error(&$token, $msg)
{
    global $wikiplugin_dbreport_errors;
    // find the line relating to the token in the code
    $pos = 0;
    $line = 0;
    $code =& $token->code;
    $len = strlen($code);
    while ($pos < $len) {
        // find the next line break
        $line++;
        $break = strpos($code, "\n", $pos);
        if ($break === false) {
            $break = $len;
        }
        // was the token in this line?
        if ($token->start < $break) {
            // format the line with the token highlighted
            $err_line = '<i>line ' . $line . '</i>: ';
            $err_line .= substr($code, $pos, $token->start - $pos);
            $err_line .= '<span style="font-weight:bold;color:DarkRed;">' . substr($code, $token->start, $token->after - $token->start) . '</span>';
            if ($token->after < $break) {
                $err_line .= substr($code, $token->after, $break - $token->after);
            }
            $wikiplugin_dbreport_errors[] = $err_line;
            $pos = $len;
        } else {
            // update position and loop
            $pos = $break + 1;
        }
    }
    // add the message to the errors
    $wikiplugin_dbreport_errors[] = $msg;
}

function wikiplugin_dbreport_next_token(&$code, $len, $pos)
{
    $whitespace = " \n\r\t\v\f";
    $tokenstop = " :<>[$\"\n\r\t\v\f";
    // create a token object to return
    unset($token);
    $token = new Token();
    $token->code =& $code;
    // find the next non-whitespace character in the code
    while (($pos < $len) && (strpos($whitespace, $code[$pos]) !== false)) {
        $pos++;
    }
    if ($pos >= $len) {
        $token->type = 'eof';
        $token->start = $len - 1;
        $token->after = $pos;
        return $token;
    }
    // what did we find?
    switch ($code[$pos]) {
        case '[':
            // field token
            $token->start = $pos;
            // parse to closing ']'
            while (($pos < $len) && ($code[$pos] != ']')) {
                if ($code[$pos] == '\\') {
                    $pos++;
                }
                $pos++;
            }
            if ($pos < $len) {
                $token->after = ++$pos;
                $token->type = 'fld';
                $token->content = substr($code, $token->start + 1, $pos - $token->start - 2);
                return $token;
            } else {
                $token->after = ++$pos;
                $token->type = 'eof';
                wikiplugin_dbreport_parse_error($token, "Unclosed Field. ] expected.");
                return $token;
            }
            break;
        case '{':
            // brackets token
            $token->type = 'bra';
            $token->start = $pos;
            // parse until we find the closing bracket.
            $pos++;
            $state = 0;
            while (($pos < $len) && ($state < 4)) {
                $c = $code[$pos++];
                switch ($state) {
                    case 0: // normal content
                        switch ($c) {
                            case '}':
                                $state = 4;
                                break;
                            case '`':
                                $state = 1;
                                $token->content .= $c;
                                break;
                            case "'":
                                $state = 2;
                                $token->content .= $c;
                                break;
                            case '"':
                                $state = 3;
                                $token->content .= $c;
                                break;
                            default:
                                $token->content .= $c;
                        }
                        break;
                    case 1: // backtick-quoted
                        switch ($c) {
                            case '`':
                                $state = 0;
                                $token->content .= $c;
                                break;
                            default:
                                $token->content .= $c;
                        }
                        break;
                    case 2: // single-quoted
                        switch ($c) {
                            case '\\':
                                $token->content .= $c . $code[$pos++];
                                break;
                            case '\'':
                                $state = 0;
                                $token->content .= $c;
                                break;
                            default:
                                $token->content .= $c;
                        }
                        break;
                    case 3: // double-quoted
                        switch ($c) {
                            case '\\':
                                $token->content .= $c . $code[$pos++];
                                break;
                            case '"':
                                $state = 0;
                                $token->content .= $c;
                                break;
                            default:
                                $token->content .= $c;
                        }
                        break;
                }
            }
            $token->after = $pos;
            switch ($state) {
                case 0: // unclosed brackets
                    wikiplugin_dbreport_parse_error($token, "Unclosed brackets. } expected");
                    $token->type = 'eof';
                    break;
                case 1: // unclosed backtick-quoted content
                    wikiplugin_dbreport_parse_error($token, "Unclosed backtick-quoted content in brackets. ` then } expected");
                    $token->type = 'eof';
                    break;
                case 2: // unclosed single-quoted content
                    wikiplugin_dbreport_parse_error($token, "Unclosed single-quoted content in brackets. ' then } expected");
                    $token->type = 'eof';
                    break;
                case 3: // unclosed double-quoted content
                    wikiplugin_dbreport_parse_error($token, "Unclosed double-quoted content in brackets. \" then } expected");
                    $token->type = 'eof';
                    break;
            }
            return $token;
            break;
        case ':':
            // style token
            $token->type = 'sty';
            $token->start = $pos;
            $token->content = [];
            // create content sub-tokens
            unset($class);
            $class = new Token();
            $class->code =& $code;
            $class->type = 'txt';
            $class->start = $pos;
            unset($style);
            $style = new Token();
            $style->code =& $code;
            $style->type = 'txt';
            // parse until we find the closing space.
            $pos++;
            $state = 0;
            while (($pos < $len) && ($state < 6)) {
                $c = $code[$pos++];
                if ($c == '\\') {
                    $c .= $code[$pos++];
                    $tc = $c;
                } else {
                    $tc = strpos($whitespace, $c) !== false ? ' ' : $c;
                }
                switch ($state) {
                    case 0: // class content
                        switch ($tc) {
                            case '<':
                            case '>':
                            case '"':
                            case ' ':
                                $state = 6;
                                $class->after = $pos;
                                break;
                            case '{':
                                $state = 1;
                                $class->after = $pos;
                                $style->start = $pos - 1;
                                break;
                            case '[':
                                $state = 2;
                                $class->content .= $c;
                                break;
                            default:
                                $class->content .= $c;
                        }
                        break;
                    case 1: // inline style content
                        switch ($tc) {
                            case '}':
                                $state = 6;
                                $style->after = $pos;
                                break;
                            case '[':
                                $state = 3;
                                $style->content .= $c;
                                break;
                            case "'":
                                $state = 4;
                                $style->content .= $c;
                                break;
                            case '"':
                                $state = 5;
                                $style->content .= $c;
                                break;
                            default:
                                $style->content .= $c;
                        }
                        break;
                    case 2: // class field
                        switch ($tc) {
                            case ']':
                                $state = 0;
                                $class->content .= $c;
                                break;
                            default:
                                $class->content .= $c;
                        }
                        break;
                    case 3: // inline style field
                        switch ($tc) {
                            case ']':
                                $state = 1;
                                $style->content .= $c;
                                break;
                            default:
                                $style->content .= $c;
                        }
                        break;
                    case 4: // single-quoted inline style
                        switch ($tc) {
                            case '\'':
                                $state = 1;
                                $style->content .= $c;
                                break;
                            default:
                                $style->content .= $c;
                        }
                        break;
                    case 5: // double-quoted inline style
                        switch ($tc) {
                            case '"':
                                $state = 1;
                                $style->content .= $c;
                                break;
                            default:
                                $style->content .= $c;
                        }
                        break;
                }
            }
            switch ($state) {
                case 0: // end of file
                    $class->after = $pos;
                    break;
                case 1: // inline style content
                    $style->after = $pos;
                    wikiplugin_dbreport_parse_error($style, "Unclosed style CSS. } expected");
                    $token->type = 'eof';
                    break;
                case 2: // class field
                    $class->after = $pos;
                    wikiplugin_dbreport_parse_error($class, "Unclosed field in style class. ] expected");
                    $token->type = 'eof';
                    break;
                case 3: // inline style field
                    $style->after = $pos;
                    wikiplugin_dbreport_parse_error($style, "Unclosed field in style CSS. ] then } expected");
                    $token->type = 'eof';
                    break;
                case 4: // single-quoted inline style
                    $style->after = $pos;
                    wikiplugin_dbreport_parse_error($style, "Unclosed single-quoted content in style CSS. ' then } expected");
                    $token->type = 'eof';
                    break;
                case 5: // double-quoted inline style
                    $style->after = $pos;
                    wikiplugin_dbreport_parse_error($style, "Unclosed double-quoted content in style CSS. \" then } expected");
                    $token->type = 'eof';
                    break;
            }
            if ($class->content) {
                $token->content['class'] = $class;
            }
            if ($style->content) {
                $token->content['style'] = $style;
            }
            $token->after = $pos;
            return $token;
            break;
        case '$':
            // variable token
            $token->type = 'var';
            $token->start = $pos;
            $pos++;
            // parse to end of token
            while (($pos < $len) && (strpos($tokenstop, $code[$pos]) === false)) {
                if ($code[$pos] == '\\') {
                    $pos++;
                }
                $pos++;
            }
            $token->content = substr($code, $token->start + 1, $pos - $token->start - 1);
            $token->after = $pos;
            return $token;
            break;
        case '"':
            // string token
            $token->type = 'txt';
            $token->start = $pos;
            $token->content = '';
            // parse until we find the closing quote.
            $pos++;
            while ($pos < $len) {
                // what is it?
                $c = $code[$pos++];
                switch ($c) {
                    case '"':
                        $token->after = $pos;
                        return $token;
                        break;
                    case '\\':
                        $token->content .= $c;
                        if (($pos < $len)) {
                            $c = $code[$pos++];
                            $token->content .= $c;
                        } else {
                            wikiplugin_dbreport_parse_error($token, "Unclosed escaped string. \" expected.");
                            $token->type = 'eof';
                            return $token;
                        }
                        break;
                    default:
                        $token->content .= $c;
                        break;
                }
            }
            // didn't find closing quotes
            $token->type = 'txt';
            wikiplugin_dbreport_parse_error($token, "Unterminated string. \" expected.");
            $token->type = 'eof';
            return $token;
            break;
        case '<':
        case '>':
            // link keywords
            $token->type = 'key';
            $token->content = $code[$pos];
            $token->start = $pos;
            $token->after = ++$pos;
            return $token;
            break;
        default:
            // keyword token
            $token->start = $pos;
            // parse to end of token
            while (($pos < $len) && (strpos($tokenstop, $code[$pos]) === false)) {
                $pos++;
            }
            $token->type = 'key';
            $token->content = substr($code, $token->start, $pos - $token->start);
            $token->after = $pos;
            return $token;
    }
}

function wikiplugin_dbreport_parse(&$code)
{
    global $wikiplugin_dbreport_fields_allowed;
    // code properties
    $len = strlen($code);
    $pos = 0;
        // FSM state
    $parse_state = 0;
    $parse_link_return = 0;
    $parse_line_return = 0;
    $parse_cell_return = 0;
    $parse_object;
    $parse_text;
    $parse_line;
    $parse_cell;
    $span_mode;
    unset($parse_report);
    $parse_report = new Base();
    // parse the code
    while (true) {
        // get the next token
        $token = wikiplugin_dbreport_next_token($code, $len, $pos);
        $pos = $token->after;
        // repeat while we have an unconsumed token
        while (isset($token)) {
            $next_token = $token;
            switch ($parse_state) {
                case 0: // next keyword
                    switch ($token->type) {
                        case 'eof':
                            if (! isset($parse_report->sql)) {
                                return wikiplugin_dbreport_parse_error($token, "Unexpected End.");
                            }
                            return $parse_report;
                            break;
                        case 'key':
                            switch (TikiLib::strtoupper($token->content)) {
                                case 'SQL':
                                    $parse_state = 1;   // switch state
                                    unset($next_token); // consume the token
                                    $wikiplugin_dbreport_fields_allowed = false; // no fields in sql
                                    break;
                                case 'PARAM':
                                    // create the parameter object
                                    unset($parse_object);
                                    $parse_object = new Parameter($token);
                                    $parse_report->params[] =& $parse_object;
                                    $parse_state = 2;   // switch state
                                    unset($next_token); // consume the token
                                    $wikiplugin_dbreport_fields_allowed = false; // no fields in sql params
                                    break;
                                case 'GROUP':
                                    // create the group object
                                    unset($parse_object);
                                    $parse_object = new Group();
                                    $parse_report->groups[] =& $parse_object;
                                    $parse_state = 3;   // switch state
                                    unset($next_token); // consume the token
                                    $wikiplugin_dbreport_fields_allowed = true; // we can now parse fields
                                    break;
                                case 'TABLE':
                                    // create the table object
                                    unset($parse_object);
                                    $parse_object = new Table();
                                    $parse_report->table =& $parse_object;
                                    $parse_state = 4;   // switch state
                                    unset($next_token); // consume the token
                                    $wikiplugin_dbreport_fields_allowed = true; // we can now parse fields
                                    break;
                                case 'FAIL':
                                    // create the fail object
                                    unset($parse_object);
                                    $parse_object = new Fail();
                                    $parse_report->fail =& $parse_object;
                                    $parse_state = 10;  // switch state
                                    unset($next_token); // consume the token
                                    $wikiplugin_dbreport_fields_allowed = false; // no fields in fail message
                                    break;
                                default:
                                    return wikiplugin_dbreport_parse_error($token, "Invalid keyword '$token->content'");
                            }
                            break;
                        default:
                            return wikiplugin_dbreport_parse_error($token, "Unexpected " . $token->type . " '" . $token->content . "' at " . $token->start);
                    }
                    break;
                case 1: // SQL content
                    switch ($token->type) {
                        case 'eof':
                            $parse_state = 0;   // switch state and reparse the token
                            break;
                        case 'bra':
                            $parse_report->sql .= $token->content;
                            unset($next_token); // consume the token
                            break;
                        case 'txt':
                            $parse_report->sql .= stripcslashes($token->content);
                            unset($next_token); // consume the token
                            break;
                        case 'key':
                            $parse_state = 0;   // switch state and reparse the token
                            break;
                        default:
                            return wikiplugin_dbreport_parse_error($token, "Unexpected " . $token->type_name() . " '$token->content' after 'SQL'. String expected.");
                    }
                    break;
                case 2: // PARAM content
                    switch ($token->type) {
                        case 'eof':
                            $parse_state = 0; // switch parse state
                            break;
                        /* case 'sty':
                            $parse_object->name = $token->content;
                            unset($next_token); // consume the token
                            break; */
                        case 'fld':
                            $parse_object->append_field($token->content);
                            unset($next_token); // consume the token
                            break;
                        case 'var':
                            $parse_object->append_variable($token->content);
                            unset($next_token); // consume the token
                            break;
                        case 'txt':
                            $parse_object->elements[] = new Text($token);
                            unset($next_token); // consume the token
                            break;
                        case 'key':
                            unset($parse_object);
                            $parse_state = 0;   // switch state and reparse the token
                            break;
                        default:
                            return wikiplugin_dbreport_parse_error($token, "Unexpected " . $token->type_name() . " '$token->content' after 'PARAM'. Name, Field, String or Variable expected.");
                    }
                    break;
                case 3: // GROUP content
                    switch ($token->type) {
                        case 'eof':
                            $parse_state = 0;   // switch state and reparse the token
                            break;
                        case 'fld':
                            unset($parse_object->fields);
                            $parse_object->fields[] = new Field($token->content);
                            $parse_object->field_count++;
                            unset($next_token);     // consume the token
                            break;
                        case 'txt':
                        case 'var':
                            unset($parse_text);
                            $parse_text = new Text($token);
                            $parse_object->contents[] =& $parse_text;
                            $parse_text_return = $parse_state; // return to this state
                            $parse_state = 9;   // switch state
                            unset($next_token);     // consume the token
                            break;
                        case 'sty':
                            unset($parse_object->style);
                            $parse_object->style = new Style($token);
                            unset($next_token);     // consume the token
                            break;
                        case 'key':
                            switch (TikiLib::strtoupper($token->content)) {
                                case '<':
                                    unset($parse_link);
                                    $parse_link = new Link();   // create the link object
                                    $parse_object->link =& $parse_link;
                                    $parse_link_return = $parse_state; // return to this state
                                    $parse_state = 5;   // switch state
                                    unset($next_token);     // consume the token
                                    break;
                                default:
                                    unset($parse_object); // we are finished parsing the group
                                    $wikiplugin_dbreport_fields_allowed = false; // we cannot parse fields anymore
                                    $parse_state = 0;   // switch state and reparse the token
                                    break;
                            }
                            break;
                        default:
                            return wikiplugin_dbreport_parse_error($token, "Unexpected " . $token->type_name() . " '$token->content' after '<'. Field, String or Style expected.");
                    }
                    break;
                case 4: // TABLE content
                    switch ($token->type) {
                        case 'eof':
                            $parse_state = 0;   // switch state and reparse the token
                            break;
                        case 'sty':
                            unset($parse_object->style);
                            $parse_object->style = new Style($token);
                             unset($next_token);        // consume the token
                            break;
                        case 'key':
                            switch (TikiLib::strtoupper($token->content)) {
                                case 'HEADER':
                                    unset($parse_line);
                                    $parse_line = new Line();
                                    $parse_object->headers[] =& $parse_line;
                                    $parse_line_return = $parse_state; // return to this state
                                    $parse_state = 6;   // switch state
                                    unset($next_token);     // consume the token
                                    break;
                                case 'FOOTER':
                                    unset($parse_line);
                                    $parse_line = new Line();
                                    $parse_object->footers[] =& $parse_line;
                                    $parse_line_return = $parse_state; // return to this state
                                    $parse_state = 6;   // switch state
                                      unset($next_token);       // consume the token
                                    break;
                                case 'ROW':
                                case 'ROWS':
                                    unset($parse_line);
                                    $parse_line = new Line();
                                    $parse_object->rows[] =& $parse_line;
                                    $parse_line_return = $parse_state; // return to this state
                                    $parse_state = 6;   // switch state
                                    unset($next_token);     // consume the token
                                    break;
                                default:
                                    unset($parse_object);   // we are finished parsing the table
                                    $wikiplugin_dbreport_fields_allowed = false; // we cannot parse fields anymore
                                    $parse_state = 0;       // switch state and reparse the token
                            }
                            break;
                        default:
                            return wikiplugin_dbreport_parse_error($token, "Unexpected " . $token->type_name() . " '$token->content' after 'TABLE'. HEADER, FOOTER, ROWS, <, or Style expected.");
                    }
                    break;
                case 5: // Link content
                    switch ($token->type) {
                        case 'eof':
                            return wikiplugin_dbreport_parse_error($token, "Unexpected EOF in Tiki\WikiPlugin\DBReport\Link. '>' expected.");
                            break;
                        case 'var':
                        case 'fld':
                            unset($parse_link->contents);
                            $parse_link->contents[] = new Field($token->content);
                            unset($next_token);     // consume the token
                            break;
                        case 'txt':
                            unset($parse_link->contents);
                            $parse_link->contents[] = new Content($token);
                            unset($next_token);     // consume the token
                            break;
                        /*
                        case 'txt':
                            $parse_link->append($token->content);
                            unset($next_token);     // consume the token
                            break;
                        case 'var':
                            $parse_link->append_variable($token->content);
                            unset($next_token);     // consume the token
                            break;
                        case 'fld':
                            $parse_link->append_field($token->content);
                            unset($next_token);     // consume the token
                            break;
                        */
                        case 'sty':
                            unset($parse_link->style);
                            $parse_link->style = new Style($token);
                            unset($next_token);     // consume the token
                            break;
                        case 'key':
                            switch (TikiLib::strtoupper($token->content)) {
                                case '<':
                                    return wikiplugin_dbreport_parse_error($token, "Unexpected '<' in Link. '>' expected.");
                                    break;
                                case '>':
                                    unset($next_token);     // consume the token
                                    $parse_state = $parse_link_return;  // return to previous state
                                    break;
                                default:
                                    return wikiplugin_dbreport_parse_error($token, "Unexpected Keyword '$token->content' in Link. '>' expected.");
                            }
                            break;
                        default:
                            $parse_state = $parse_link_return;  // switch state and reparse the token
                    }
                    break;
                case 6: // HEADER, FOOTER, ROW content
                    switch ($token->type) {
                        case 'eof':
                            $parse_state = $parse_line_return;  // switch state and reparse the token
                            break;
                        case 'sty':
                            unset($parse_link->styles);
                            $parse_line->styles[] = new Style($token);
                            unset($next_token);     // consume the token
                            break;
                        case 'key':
                            switch (TikiLib::strtoupper($token->content)) {
                                case 'CELL':
                                    unset($parse_cell);
                                    $parse_cell = new Cell();
                                    $parse_line->cells[] =& $parse_cell;
                                    $parse_cell_return = $parse_state; // return to this state
                                    $parse_state = 7;   // switch state
                                    unset($next_token);     // consume the token
                                    break;
                                case '<':
                                    unset($parse_link);
                                    $parse_link = new Link();   // create the link object
                                    $parse_line->link =& $parse_link;
                                    $parse_link_return = $parse_state; // return to this state
                                    $parse_state = 5;   // switch state
                                    unset($next_token);     // consume the token
                                    break;
                                case 'HEADER':
                                case 'ROW':
                                case 'FOOTER':
                                case 'FAIL':
                                    $parse_state = $parse_line_return;
                                    break;
                                default:
                                    return wikiplugin_dbreport_parse_error($token, "Invalid keyword '$token->content' after row. CELL or Link expected.");
                            }
                            break;
                        default:
                            return wikiplugin_dbreport_parse_error($token, "Unexpected " . $token->type_name() . " '$token->content' in row.");
                    }
                    break;
                case 7: // CELL content
                    switch ($token->type) {
                        case 'eof':
                            $parse_state = $parse_cell_return;  // switch state and reparse the token
                            break;
                        case 'fld':
                        case 'var':
                        case 'txt':
                            unset($parse_text);
                            $parse_text = new Text($token);
                            $parse_cell->contents[] =& $parse_text;
                            $parse_text_return = $parse_state; // return to this state
                            $parse_state = 9;   // switch state
                            unset($next_token);     // consume the token
                            break;
                        case 'sty':
                            unset($parse_cell->style);
                            $parse_cell->style = new Style($token);
                            unset($next_token);     // consume the token
                            break;
                        case 'key':
                            switch (TikiLib::strtoupper($token->content)) {
                                case '<':
                                    unset($parse_link);
                                    $parse_link = new Link();   // create the link object
                                    $parse_cell->link =& $parse_link;
                                    $parse_link_return = $parse_state; // return to this state
                                    $parse_state = 5;   // switch state
                                    unset($next_token);     // consume the token
                                    break;
                                case 'SPAN':
                                    $span_mode = 'COL';
                                    $parse_state = 8;   // switch state
                                    unset($next_token);     // consume the token
                                    break;
                                case 'COLSPAN':
                                    $span_mode = 'COL';
                                    $parse_state = 8;   // switch state
                                    unset($next_token);     // consume the token
                                    break;
                                case 'ROWSPAN':
                                    $span_mode = 'ROW';
                                    $parse_state = 8;   // switch state
                                    unset($next_token);     // consume the token
                                    break;
                                case 'CELL':
                                case 'HEADER':
                                case 'ROW':
                                case 'COLUMN':
                                case 'FOOTER':
                                case 'FAIL':
                                    $parse_state = $parse_cell_return;  // switch state and reparse the token
                                    break;
                                default:
                                    return wikiplugin_dbreport_parse_error($token, "Invalid keyword '$token->content' in 'CELL'. Field, String, Style or Link expected.");
                            }
                            break;
                        default:
                            return wikiplugin_dbreport_parse_error($token, "Unexpected " . $token->type_name() . " '$token->content' after 'CELL'.");
                    }
                    break;
                case 8: // SPAN content
                    switch ($token->type) {
                        case 'key':
                            // try to parse the keyword as as number
                            $span = (int) $token->content;
                            if ((string) $span == $token->content) {
                                if ($span_mode == 'ROW') {
                                    $parse_cell->rowspan = $span;
                                } else {
                                    $parse_cell->colspan = $span;
                                }
                                unset($next_token);     // consume the token
                            }
                            $parse_state = 7;   // switch state (and possibly reparse the token)
                            break;
                        default:
                            $parse_state = 7;   // switch state and reparse the token
                    }
                    break;
                case 9: // Text content
                    switch ($token->type) {
                        case 'sty':
                            unset($parse_text->style);
                            $parse_text->style = new Style($token);
                             unset($next_token);        // consume the token
                            break;
                        case 'key':
                            switch (TikiLib::strtoupper($token->content)) {
                                case '<':
                                    unset($parse_link);
                                    $parse_link = new Link();   // create the link object
                                    $parse_text->link =& $parse_link;
                                    $parse_link_return = $parse_state; // return to this state
                                    $parse_state = 5;   // switch state
                                    unset($next_token);     // consume the token
                                    break;
                                default:
                                    $parse_state = $parse_text_return;  // return to the previous state
                                    break;
                            }
                            break;
                        default:
                            $parse_state = $parse_text_return;  // return to the previous state
                            break;
                    }
                    break;
                case 10: // Fail content
                    switch ($token->type) {
                        case 'eof':
                            $parse_state = 0;   // switch state and reparse the token
                            break;
                        case 'var':
                        case 'txt':
                            unset($parse_text);
                            $parse_text = new Text($token);
                            $parse_object->contents[] =& $parse_text;
                            $parse_text_return = $parse_state; // return to this state
                            $parse_state = 9;       // switch state
                             unset($next_token);        // consume the token
                            break;
                        case 'sty':
                            unset($parse_object->style);
                            $parse_object->style = new Style($token);
                            unset($next_token);     // consume the token
                            break;
                        case 'key':
                            switch (TikiLib::strtoupper($token->content)) {
                                case '<':
                                    unset($parse_link);
                                    $parse_link = new Link();   // create the link object
                                    $parse_object->link =& $parse_link;
                                    $parse_link_return = $parse_state; // return to this state
                                    $parse_state = 5;   // switch state
                                    unset($next_token);     // consume the token
                                    break;
                                default:
                                    unset($parse_object); // we are finished parsing the fail
                                    $parse_state = 0;   // switch state and reparse the token
                                    break;
                            }
                            break;
                        default:
                            return wikiplugin_dbreport_parse_error($token, "Unexpected " . $token->type_name() . " '$token->content' after 'FAIL'.");
                    }
                    break;
                default:
                    $parse_state = 0;
            }
            if (isset($next_token)) {
                $token = $next_token;
            } else {
                unset($token);
            }
        }
    }
}

function wikiplugin_dbreport_error_box($error)
{
    $return = '~np~<table style="border-width:1px; border-style:dashed; border-color:red; background:#FFE0E0;"><tr><td>';
    switch (gettype($error)) {
        case 'array':
            foreach ($error as $entry) {
                $return .= $entry . '<br/>';
            }
            break;
        case 'string':
        case 'object':
            $return .= (string) $error;
            break;
        default:
            $return .= gettype($error) . ' ERROR!';
    }
    $return .= '</td></tr></table>~/np~';
    return $return;
}

function wikiplugin_dbreport_message_box($msg)
{
    $return = '<table style="border-width:1px; border-style:dashed; border-color:silver; background:#E0E0FF;"><tr><td>';
    switch (gettype($error)) {
        case 'array':
            foreach ($msg as $entry) {
                $return .= $entry . '<br/>';
            }
            break;
        default:
            $return .= (string) $msg;
    }
    $return .= '</td></tr></table>';
    return $return;
}

function wikiplugin_dbreport_help()
{
    return tra("Run a database report") . ":<br />~np~{DBREPORT(dsn=>dsnname | db=>dbname, wiki=0|1, debug=>0|1)}" . tra("report definition") . "{DBREPORT}~/np~";
}


function wikiplugin_dbreport_info()
{
    return [
        'name' => tra('DB Report'),
        'documentation' => 'PluginDBReport',
        'description' => tra('Query a database and display results'),
        'prefs' => ['wikiplugin_dbreport'],
        'body' => tra('report definition'),
        'validate' => 'all',
        'iconname' => 'table',
        'introduced' => 3,
        'params' => [
            'dsn' => [
                'required' => false,
                'name' => tra('Full DSN'),
                'description' => tr('A full DSN (Data Source Name) connection string. Example: ')
                    . '<code>mysql://user:pass@server/database</code>',
                'since' => '3.0',
                'filter' => 'url',
            ],
            'db' => [
                'required' => false,
                'name' => tra('Wiki DSN Name'),
                'description' => tra('The name of a DSN connection defined by the Wiki administrator.'),
                'since' => '3.0',
                'filter' => 'text',
            ],
            'wiki' => [
                'required' => false,
                'name' => tra('Wiki Syntax'),
                'description' => tra('Parse wiki syntax within the report (not parsed by default)'),
                'since' => '3.0',
                'default' => '',
                'filter' => 'digits',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 1],
                    ['text' => tra('No'), 'value' => 0]
                ],
            ],
            'debug' => [
                'required' => false,
                'name' => tra('Debug'),
                'description' => tra('Display the parsed report definition (not displayed by default)'),
                'since' => '3.0',
                'default' => '',
                'filter' => 'digits',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 1],
                    ['text' => tra('No'), 'value' => 0]
                ],
            ],
            'audit' => [
                'required' => false,
                'name' => tra('Audit'),
                'description' => tr('Create a log entry containing information about the SQL call.'),
                'since' => '21.2',
                'default' => '0',
            ],
            'audit_csv' => [
                'required' => false,
                'name' => tra('Audit CSV path'),
                'description' => tr('If set, a CSV file will be created or appended with information about the SQL call performed.'),
                'since' => '21.2',
                'filter' => 'text',
            ],
        ],
    ];
}


function wikiplugin_dbreport($data, $params)
{
    // TikiWiki globals
    global $tikilib, $user, $group, $prefs;
    // wikiplugin_dbreport globals
    global $wikiplugin_dbreport_errors;
    global $wikiplugin_dbreport_fields;
    global $wikiplugin_dbreport_fields_allowed;
    global $wikiplugin_dbreport_record;
    // initialize globals
    $wikiplugin_dbreport_errors = [];
    $wikiplugin_dbreport_fields = [];
    $wikiplugin_dbreport_fields_allowed = false;
    $wikiplugin_dbreport_record = null;
    // extract parameters
    extract($params, EXTR_SKIP);
    // we need a dsn or db parameter
    if (is_null($dsn) && is_null($db)) {
        return WikiParser_PluginOutput::argumentError(['dsn', 'db']);
    }
    // parse the report definition
    $parse_fix = (! empty($_REQUEST['preview'])) && ($prefs['tiki_release'] == '2.2');
    if ($parse_fix) {
        $report = wikiplugin_dbreport_parse($data);
    } else {
        $data = html_entity_decode($data);
        $report = wikiplugin_dbreport_parse($data);
    }
    // were there errors?
    if ($wikiplugin_dbreport_errors) {
        $ret = wikiplugin_dbreport_error_box($wikiplugin_dbreport_errors);
        return $ret;
    }
    // create the bind variables array
    $bindvars = [];
    if (isset($report->params)) {
        foreach ($report->params as $param) {
            if (isset($param->name)) {
                $bindvars[$param->name] = $param->text();
            } else {
                $bindvars[] = $param->text();
            }
        }
    }
    // translate db name into dsn
    if (! is_null($db)) {
        $perms = Perms::get([ 'type' => 'dsn', 'object' => $db ]);
        if (! $perms->dsn_query) {
            return tra('You do not have the permission that is needed to use this feature');
        }
    }
    // Open the database
    if (! is_null($dsn)) {
        $db = $tikilib->get_db_by_name($db);
        $query = $db->query($report->sql, $bindvars);
        // Convert result set to FETCH_BOTH manually
        $query->result = array_map(function ($row) {
            return array_merge($row, array_values($row));
        }, $query->result);

        // Fetch the first row to get field names
        $first_row = $query->fetchRow();

        if (! $first_row) {
            return wikiplugin_dbreport_error_box('No data returned by the query.');
        }
    } else {
        return tra('No DSN connection string found!');
    }

    // Get field names from the first row
    $field_names = array_keys($first_row);
    $field_index = [];

    foreach ($field_names as $index => $field_name) {
        $field_index[$field_name] = $index;
    }

    // Go through the parsed fields and assign indexes
    foreach ($wikiplugin_dbreport_fields as $key => $value) {
        $parse_field =& $wikiplugin_dbreport_fields[$key];

        if (array_key_exists($parse_field->name, $field_index)) {
            $parse_field->index = $field_index[$parse_field->name];
        } else {
            // Not a valid field. Log the message.
            $ret .= wikiplugin_dbreport_error_box("The Field '{$parse_field->name}' was not returned by the SQL query.");
            return $ret;
        }
    }

    // does the report have a table definition?
    if (! isset($report->table)) {
        // create a default definition from the data
        $report->table = new Table();
        $style = 'sortable';
        $report->table->style = new Style($style);
        $header = new Line();
        $style = 'heading';
        $header->styles[] = new Style($style);
        $report->table->headers[] =& $header;
        $row = new Line();
        $style = 'even';
        $row->styles[] = new Style($style);
        $style = 'odd';
        $row->styles[] = new Style($style);
        $report->table->rows[] =& $row;
        // fill in the cells
        $field_count = count($query->result);
        for ($index = 0; $index < $field_count; $index++) {
            // get the query field
            $column =& $query->fetchRow($index);
            // create the header cell
            unset($text);
            $text = new Text(new Token());
            $text->append_string($column->name);
            unset($cell);
            $cell = new Cell();
            // $style = 'heading';
            // $cell->style = new Style($style);
            $cell->contents[] =& $text;
            $header->cells[] =& $cell;
            // create the rows cell
            unset($cell);
            $cell = new Cell();
            unset($field);
            $field = new Field($column->name);
            $field->index = $index;
            $cell->contents[] =& $field;
            $row->cells[] =& $cell;
        }
    }
    // are we debugging?
    if ($debug) {
        $ret .= wikiplugin_dbreport_message_box("~np~<pre>" . htmlspecialchars($report->code()) . "</pre>~/np~");
    }
    // generate the report
    if (! $wiki) {
        $ret .= '~np~';
    }
    // Check if the query returned results
    if (! empty($query->result)) {
        // Get all rows into an array
        $rows = $query->result;
        $rowCount = count($rows);

        // Initialize row pointer
        $rowIndex = 0;
        $current_row = $rows[$rowIndex];

        // Start the group breaks
        if (isset($report->groups)) {
            foreach ($report->groups as $group) {
                $group->check_break($current_row);
                $ret .= $group->start_html($current_row);
            }
        }

        // First row is always considered 'after a break'
        $breaking = true;

        // Iterate through rows
        while ($rowIndex < $rowCount) {
            // Do we generate a table header?
            if ($breaking) {
                $ret .= $report->table->header_row_html($current_row);
            }

            // Write the table row
            $ret .= $report->table->record_row_html($current_row);

            // Move to the next row
            $rowIndex++;
            if ($rowIndex >= $rowCount) {
                $next_row = null;
                $breaking = true;
            } else {
                $next_row = $rows[$rowIndex];
                $breaking = false;
            }

            // Check group breaks
            if (isset($report->groups)) {
                $break_end = '';
                $break_start = '';
                foreach ($report->groups as $group) {
                    if (isset($next_row)) {
                        $breaking = ($group->check_break($next_row) || $breaking);
                    }
                    if ($breaking) {
                        $break_end = $group->end_html($current_row) . $break_end;
                        if (isset($next_row)) {
                            $break_start .= $group->start_html($next_row);
                        }
                    }
                }
            }

            if ($breaking) {
                $ret .= $report->table->footer_row_html($current_row);
                $ret .= $break_end;
                $ret .= $break_start;
            }

            // Move to the next row
            $current_row = $next_row;
        }
    } else {
        // No records returned, output the fail message
        if ($report->fail) {
            $ret .= $report->fail->html();
        }
    }

    if (! $wiki) {
        $ret .= '~/np~';
    }

    if (! empty($params['audit'])) {
        TikiLib::lib('logs')->add_log('wikiplugin_dbreport', "Page - " . $_GET['page'] . "\nParameters - " . print_r($bindvars, true));
    }

    if (! is_null($params['audit_csv'])) {
        $headers = ['date', 'user', 'page', 'vars'];
        $contentRow[] = [
            $tikilib->date_format($prefs['short_date_format'] . ' ' . $prefs['long_time_format'], $tikilib->now),
            $user,
            $_GET['page'] ?? '',
            $bindvars
        ];

        if (! FileHelper::appendCSV($params['audit_csv'], $headers, $contentRow)) {
            Feedback::error(tr('Unable to create or open the file "%0" to log the SQL operation,', $params['audit_csv']));
        }
    }

    // return the result
    return $ret;
}
