<?php
if (!defined('TL_ROOT'))
    die('You cannot access this file directly!');

/**
 * Contao Open Source CMS
 * Copyright (C) 2005-2011 Leo Feyer
 *
 * Formerly known as TYPOlight Open Source CMS.
 *
 * This program is free software: you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation, either
 * version 3 of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details.
 * 
 * You should have received a copy of the GNU Lesser General Public
 * License along with this program. If not, please visit the Free
 * Software Foundation website at <http://www.gnu.org/licenses/>.
 *
 * PHP version 5
 * @copyright  Rolf Staege 2011 
 * @author     Rolf Staege 
 * @package    lumturo.booking 
 * @license    GPL 
 * @filesource
 */

/**
 * Table tl_season 
 */
$GLOBALS['TL_CSS'][] = '/system/modules/xbooking/html/css/backend.css';
$GLOBALS['TL_DCA']['tl_season'] = array(
    
    // Config
    'config' => array(
        'dataContainer' => 'Table', 
        'enableVersioning' => true, 
        'ondelete_callback' => array(
            array(
                'tl_season', 
                'deleteSeason'
            )
        )
    )
    , 
    
    // List
    'list' => array(
        'sorting' => array(
            'mode' => 1, 
            'fields' => array(
                'season_from'
            ), 
            'flag' => 12 //absteigende Sortierung
        ), 
        'label' => array(
            'fields' => array('title', 'season_from', 'season_to', 'price'),
            'showColumns' => TRUE,
            'label_callback' => array(
                'tl_season', 'listView'
            )
            // 'fields' => array(
            //     'title', 
            //     'season_from', 
            //     'season_to'
            // ), 
            // 'label_callback' => array(
            //     'tl_season', 
            //     'formatLabel'
            // ),  //Wertformatierung
            // 'group_callback' => array(
            //     'tl_season', 
            //     'formatGroup'
            // ) //Gruppenformatierung
        ),  //            'format' => '%s: %s - %s'
        'global_operations' => array(
            'all' => array(
                'label' => &$GLOBALS['TL_LANG']['MSC']['all'], 
                'href' => 'act=select', 
                'class' => 'header_edit_all', 
                'attributes' => 'onclick="Backend.getScrollOffset();" accesskey="e"'
            )
        ), 
        'operations' => array(
            
            'edit' => array(
                'label' => &$GLOBALS['TL_LANG']['tl_season']['edit'], 
                'href' => 'act=edit', 
                'icon' => 'edit.gif'
            ), 
            'delete' => array(
                'label' => &$GLOBALS['TL_LANG']['tl_season']['delete'], 
                'href' => 'act=delete', 
                'icon' => 'delete.gif', 
                'attributes' => 'onclick="if (!confirm(\'' . $GLOBALS['TL_LANG']['MSC']['deleteConfirm'] . '\')) return false; Backend.getScrollOffset();"'
            )
        )
    ), 
    
    // Palettes
    'palettes' => array(
        'default' => 'title,season_from,season_to,price,cleaning_fee'
    ), 
    
    // Subpalettes
    'subpalettes' => array(
        '' => ''
    ), 
    
    // Fields
    'fields' => array(
        'title' => array(
            'label' => &$GLOBALS['TL_LANG']['tl_season']['title'], 
            'inputType' => 'text', 
            'eval' => array(
                'mandatory' => true, 
                'maxlength' => 100
            )
        ), 
        'season_from' => array(
            'label' => &$GLOBALS['TL_LANG']['tl_season']['season_from'], 
            'inputType' => 'text', 
            'eval' => array(
                'mandatory' => true, 
                'rgxp' => 'date', 
                'datepicker' => $this->getDatePickerString()
            ), 
            'save_callback' => array(
                array(
                    'tl_season', 
                    'checkFrom'
                )
            )
        ), 
        'season_to' => array(
            'label' => &$GLOBALS['TL_LANG']['tl_season']['season_to'], 
            'inputType' => 'text', 
            'eval' => array(
                'mandatory' => true, 
                'rgxp' => 'date', 
                'datepicker' => $this->getDatePickerString()
            ), 
            'save_callback' => array(
                array(
                    'tl_season', 
                    'checkTo'
                )
            )
        ), 
        'price' => array(
            'label' => &$GLOBALS['TL_LANG']['tl_season']['price'], 
            'inputType' => 'text', 
            'eval' => array(
                'mandatory' => true, 
                'rgxp' => 'digit'
            )
        ), 
//        'cleaning_fee' => array(
//            'label' => &$GLOBALS['TL_LANG']['tl_season']['cleaning_fee'], 
//            'inputType' => 'text', 
//            'eval' => array(
//                'mandatory' => true, 
//                'rgxp' => 'digit'
//            )
//        )
    )
);

class tl_season extends Backend
{

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * saison-Anzeige
     */
    public function listView($row, $label, $dc, $args) {
        $args[1] = date('d.m.Y', $args[1]);
        $args[2] = date('d.m.Y', $args[2]);
        $args[3] = number_format($args[3], 2, ',', '.');
        return $args;
    }

    /**
     * Testet, ob das From-Datum nicht in irgend einen Bereich fällt  (save_callback für 'season_from'-Feld)
     * Wenn Fehler, wird Exception geworfen. Deren String wird unter dem Feld im Backend ausgegeben.
     * 
     * @param integer $value - Wert (unix_ts)
     * @param DataContainer $dc
     * @throws Exception
     */
    public function checkFrom($value, DataContainer $dc)
    {
        //selektiere nun alle Saisons, deren Start kleiner und Ende grösser ist
        $seasons = $this->Database->prepare('select * from tl_season where season_from < ? and season_to > ? and id != ?')
            ->execute($value, $value, $dc->activeRecord->id);
        if ($seasons->numRows)
        {
            $other = $seasons->fetchAssoc();
            throw new Exception(sprintf($GLOBALS['TL_LANG']['ERR']['fromDate'], $other['title']));
        }
        return $value;
    }

    /**
     * Testet, das Ende-Datum  (save_callback für 'season_to'-Feld)
     * Wenn Fehler, wird Exception geworfen. Deren String wird unter dem Feld im Backend ausgegeben.
     * 
     * @param integer $value - Wert (unix_ts)
     * @param DataContainer $dc
     * @throws Exception
     */
    public function checkTo($value, DataContainer $dc)
    {
        //der ts zeigt auf 00:00:00; mache daraus 23:59:59
        $value = strtotime(date('d.m.Y', $value) . ' 23:59:59');
        #checke, ob das Endedatum nicht kleiner als das Anfangsdatum ist
        if ((int) $dc->activeRecord->season_from > $value)
        {
            throw new Exception($GLOBALS['TL_LANG']['ERR']['toDateToSmall']);
        }
        #checke, ob das Endedatum innerhalb einer andere Saison liegt
        //selektiere nun alle Saisons, deren Start kleiner als dieses Ende ist
        $seasons = $this->Database->prepare('select * from tl_season where season_from < ? and season_to > ? and id != ?')
            ->execute($value, $value, $dc->activeRecord->id);
        if ($seasons->numRows)
        {
            $other = $seasons->fetchAssoc();
            throw new Exception(sprintf($GLOBALS['TL_LANG']['ERR']['toDateInSeason'], $other['title']));
        }
        return $value;
    }

    /**
     * checkt, ob es nicht schon eine Buchung zu dieser Saison gibt
     * @param DC_Table $dc
     * @todo: Implementieren, Exception geht nicht :-(
     */
    public function deleteSeason($dc)
    {
        
//        throw new Exception('delete ist nicht...');
    }

    /**
     * Formatiert den Wert für diese Zeile in der Listenansicht im backend
     * 
     * @param array $row - row der Tabelle
     * @param string $label - akt. Label
     * @param DC_Table $dc 
     * @param unknown_type $folderAttribute
     */
    public function formatLabel($row, $label, $dc, $folderAttribute)
    {
        $old_lc = setlocale(LC_MONETARY, 'de_DE');
//        $ret = '<div class="clearfix"><div class="c20l">' . $this->parseDate($GLOBALS['TL_CONFIG']['dateFormat'], $row['season_from']) . ' - ' . $this->parseDate($GLOBALS['TL_CONFIG']['dateFormat'], $row['season_to']) . '</div><div class="c20l">Tagespreis: ' . money_format($GLOBALS['TL_LANG']['MSC']['money_format'], $row['price']) . ' €</div></div>';
        $ret = '<div class="clearfix"><div class="c20l">' . $this->parseDate($GLOBALS['TL_CONFIG']['dateFormat'], $row['season_from']) . ' - ' . $this->parseDate($GLOBALS['TL_CONFIG']['dateFormat'], $row['season_to']) . '</div><div class="c20l">Tagespreis: ' . money_format('%!n', $row['price']) . ' €</div></div>';
        setlocale(LC_MONETARY, $old_lc);
        return $ret;
    }

    /**
     * Formatiert das Gruppenlabel in der Listenansicht im backend
     * @param string $group - der bis dahin gefundenen Wert für das Gruppenlabel
     * @param integeger $mode - sortmode
     * @param string $field - Feldname
     * @param array $row - row der Tabelle
     * @param DC_Table $dc
     */
    public function formatGroup($group, $mode, $field, $row, $dc)
    {
        return $row['title'];
    }
}
?>