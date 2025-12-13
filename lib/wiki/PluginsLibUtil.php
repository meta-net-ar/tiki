<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/**
 * Class with utilities for Plugins
 */

namespace Tiki\Lib\Wiki;

use Search_Formatter_Builder;

class PluginsLibUtil
{
    /**
     * Create a table with information from pages
     * @param array key ["data"] from one of the functions that retrieve informaci�n about pages
     * @param array list of keys to show.
     * @param array definition of the principal field. By default:
     *              array("field"=>"pageName","name"=>"Page")
     * @return string
     */
    public static function createTable($aData, $aInfo = false, $aPrincipalField = false)
    {
        // contract
        if (! $aPrincipalField or ! is_array($aPrincipalField)) {
            $aPrincipalField = ['field' => 'pageName', 'name' => 'Page'];
        }
        if (! is_array($aInfo)) {
            $aInfo = false;
        }
        if (! is_array($aData)) {
            $aData = (array) $aData;
        }
        // ~contract
        $sOutput = '';
        if ($aInfo) {
            $iNumCol = count($aInfo) + 1;
            $sStyle = '';

            if (in_array('parameters', $aInfo)) {
                $sOutput .= '<em>' . tra('Required parameters are in</em> <b>bold</b>') . '<br />';
            }
            // Header for info
            $sOutput .= '<table class="table table-striped table-hover">' . "\n\t" . '<tr>' . "\n\t\t"
                . '<td class="heading"' . $sStyle . '>' . tra($aPrincipalField['name']) . '</td>';
            foreach ($aInfo as $iInfo => $sHeader) {
                if ($sHeader == 'paraminfo') {
                    $sHeader = tra('Parameter Info');
                } elseif ($sHeader == 'sourcecode') {
                    $sHeader = tra('Source Code');
                }
                $sOutput .= "\n\t\t" . '<th class="heading"' . $sStyle . '>' . ucfirst(tra($sHeader)) . '</th>';
            }
            $sOutput .= "\n\t" . '</tr>';
        }
        $iCounter = 1;
        //Primary row
        foreach ($aData as $aPage) {
            $rowspan = '';
            if ($aPrincipalField['field'] == 'plugin') {
                $openlink = '';
                $closelink = '';
            } else {
                $openlink = '((';
                            $closelink = '))';
            }
            if (! $aInfo) {
                $sOutput .= '* ' . $openlink . $aPage[$aPrincipalField['field']] . $closelink . "\n";
                //First column
            } elseif (isset($aPage[$aPrincipalField['field']])) {
                if (is_array($aPage[$aPrincipalField['field']])) {
                    $fieldval = $aPage[$aPrincipalField['field']][$aPrincipalField['field']];
                    if (isset($aPage[$aPrincipalField['field']]['rowspan']) && $aPage[$aPrincipalField['field']]['rowspan'] != 0) {
                        $rowspan = ' rowspan="' . $aPage[$aPrincipalField['field']]['rowspan'] . '" ';
                    } else {
                        $rowspan = '';
                    }
                } else {
                    $fieldval = $aPage[$aPrincipalField['field']];
                    $rowspan = '';
                }
                $sClass = ($iCounter % 2) ? 'odd' : 'even';

                $sOutput .= "\n\t" . '<tr>' . "\n\t\t" . '<td class="' . $sClass . '"' . $rowspan . '>'
                    . $openlink . $fieldval . $closelink . '</td>';
                $colcounter = 2;
                //Subsequent columns
                foreach ($aInfo as $sInfo) {
                    if (isset($aPage[$sInfo])) {
                        if (is_array($aPage[$sInfo])) {
                            $rowspan2 = '';
                            if (isset($aPage[$sInfo]['rowspan']) && $aPage[$sInfo]['rowspan'] > 0) {
                                $rowspan2 = ' rowspan="' . $aPage[$sInfo]['rowspan'] . '" ';
                                $pcount = count($aPage[$sInfo]) - 1;
                            } else {
                                $pcount = count($aPage[$sInfo]);
                            }
                            $i = $pcount;
                            foreach ($aPage[$sInfo] as $sInfokey => $sInfoitem) {
                                //Potential sub-rows
                                if ($i < $pcount && ! str_contains($sInfokey, 'rowspan')) {
                                    $begrow = "\n\t" . '<tr>';
                                    $endrow = "\n\t" . '</tr>';
                                } else {
                                    $begrow = '';
                                    if ($colcounter == $iNumCol && ! str_contains($sInfokey, 'rowspan')) {
                                        $endrow = "\n\t" . '</tr>';
                                    } else {
                                        $endrow = '';
                                    }
                                }
                                //Ignore field added to hold rowspan
                                if (str_contains($sInfokey, 'rowspan')) {
                                    $sOutput .= '';
                                } else {
                                    $sOutput .= $begrow . "\n\t\t" . '<td class="' . $sClass . '"' . $rowspan2 . '>';
                                    if (str_contains($sInfokey, 'onekey')) {
                                        $sOutput .= $sInfoitem;
                                    } else {
                                        $sOutput .= $sInfokey;
                                    }
                                    $sOutput .= '</td>';
                                    if (in_array('paraminfo', $aInfo) && $sInfo == 'parameters') {
                                        $sOutput .= "\n\t\t" . '<td class="' . $sClass . '">';
                                        if (count($aPage['parameters']) > 0) {
                                            $sOutput .= $sInfoitem;
                                        }
                                        $sOutput .= '</td>';
                                    }
                                }
                                $sOutput .= $endrow;
                                $i--;
                            }
                            $colcounter++;
                        } else {
                            $sOutput .= "\n\t\t" . '<td class="' . $sClass . '">' . $aPage[$sInfo] . '</td>';
                            if ($colcounter == $iNumCol) {
                                $sOutput .= "\n\t" . '</tr>';
                            }
                            $colcounter++;
                        }
                    }
                }
            }
            $iCounter++;
        }
        if ($aInfo) {
            $sOutput .= '</table>';
        }
        return $sOutput;
    }

    public static function createList($aData)
    {
        $aPrincipalField = ['field' => 'pageName', 'name' => 'Pages'];

        // Header for info
        $sOutput = '<table class="table table-striped table-hover"><tr><th class="heading">'
            . tra($aPrincipalField['name']) . '</th></tr><tr><td>';
        $iCounter = 0;
        // create a comma-separated list of entries
        foreach ($aData as $aPage) {
            if ($iCounter > 0) {
                $sOutput .= ', ';
            }
            $sOutput .= '((' . $aPage[$aPrincipalField['field']] . '))';
            $iCounter++;
        }
        $sOutput .= '</td></tr></table>';
        return $sOutput;
    }

    public static function handleDownload($query, $index, $matches, $request = null)
    {
        if (empty($request)) {
            $request = $_REQUEST;
        }
        if (empty($request['download'])) {
            return;
        }

        $builder = new Search_Formatter_Builder();
        $builder->setDownload(true);
        $builder->apply($matches);

        $filename = $builder->getDownloadName();
        if (! $filename) {
            $filename = 'report.csv';
        }

        $formatter = $builder->getFormatter();

        $offset = 0;
        $output = '';
        do {
            $query->setRange($offset, 100);
            $result = $query->search($index);
            $chunk = $formatter->format($result);
            if ($offset > 0) {
                $chunk = substr($chunk, strpos($chunk, "\n") + 1);
            }
            $output .= $chunk;
            $offset += 100;
        } while ($offset < $result->count());

        header('Content-Type: text/csv; charset=utf8');
        header("Content-Disposition: attachment; filename=$filename");
        echo $output;
        exit();
    }
}
