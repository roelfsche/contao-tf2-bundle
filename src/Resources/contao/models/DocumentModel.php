<?php

namespace Lumturo\ContaoTF2Bundle\Model;

use \DateTime;
use Exception;
use Lumturo\ContaoTF2Bundle\TF2Invoice;

class DocumentModel extends \Model
{
    /**
     * Table name
     * @var string
     */
    protected static $strTable = 'tl_document';

    /**
     * @var BookingModel
     */
    protected $objBooking = NULL;

    public function findByBookingId($intId)
    {

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

    public function getListDetails()
    {
        $arrAll = $this->row();
        $arrRet = array_intersect_key($arrAll, array_flip(['id', 'tstamp', 'price', 'cleaning_fee']));
        $arrRet['tstamp'] = date('c', $arrRet['tstamp']);
        $arrRet['price'] = (int)$arrRet['price'];
        $arrRet['cleaning_fee'] = (int)$arrRet['cleaning_fee'];

        return $arrRet;
    }

    public function setBooking(BookingModel $objBooking)
    {
        $this->objBooking = $objBooking;
        $this->pid = $objBooking->id;
    }

    public function createInvoice(TF2Invoice $objInvoice)
    {
        if (!$this->objBooking) {
            throw new Exception('Booking-Objekt nicht gesetzt!');
        }
        $this->price = $this->objBooking->price;
        $this->cleaning_fee = $this->objBooking->cleaning_fee;

        $objInvoice->setBooking($this->objBooking);
        $objInvoice->setDocument($this);
        $objInvoice->create();

        $this->doc = $objInvoice->output('', 'S');
    }
}
