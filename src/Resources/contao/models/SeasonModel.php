<?php

namespace Lumturo\ContaoTF2Bundle\Model;

use Contao\Database;
use \DateTime;

class SeasonModel extends \Model
{
    /**
     * Table name
     * @var string
     */
    protected static $strTable = 'tl_season';

    public static function findByInterval($intFromTs, $intToTs) {
        $arrOptions = [
            'column' => [
                '(season_from <= ? AND season_to > ?) OR (season_from > ? AND season_from < ?)',
            ],
            'value' => [$intFromTs, $intFromTs, $intFromTs, $intToTs],
            'order' => 'season_from ASC',
            'return' => 'Collection'
        ];
        return static::findAll($arrOptions);
    }
}
