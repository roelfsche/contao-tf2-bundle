<?php

namespace Lumturo\ContaoTF2Bundle\Model;

use \DateTime;

class DocumentModel extends \Model
{
    /**
     * Table name
     * @var string
     */
    protected static $strTable = 'tl_document';

    public function findByBookingId($intId) {

        $arrOptions = [
            'column' => [
                'pid = ?'
            ],
            'value' => [$intId],
            'order' => 'id DESC',
            'return' => 'Collection'
        ];
        return static::findAll($arrOptions);
    }

    public function getListDetails() {
        $arrAll = $this->row();
        $arrRet = array_intersect_key($arrAll, array_flip(['invoice_id', 'tstamp', 'price', 'cleaning_fee']));

        return $arrRet;
    }

} 
