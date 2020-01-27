<?php

namespace Lumturo\ContaoTF2Bundle\Model;

use \DateTime;

class EmailModel extends \Model
{
    /**
     * Table name
     * @var string
     */
    protected static $strTable = 'tl_email';

    public function findByBookingId($intId) {

        $arrOptions = [
            'column' => [
                'booking_id = ?'
            ],
            'value' => [$intId],
            'order' => 'tstamp DESC',
            'return' => 'Collection'
        ];
        return static::findAll($arrOptions);
    }

    public function getListDetails() {
        $arrAll = $this->row();
        $arrRet = array_intersect_key($arrAll, array_flip(['from_address', 'received_ts', 'subject', 'direction']));

        return $arrRet;
    }

} 
