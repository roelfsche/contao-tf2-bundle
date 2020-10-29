<?php

namespace Lumturo\ContaoTF2Bundle\Model;

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
    protected $objBooking = null;

    public function findByBookingId($intId)
    {

        $arrOptions = [
            'column' => [
                'pid = ?',
            ],
            'value' => [$intId],
            'order' => 'id DESC',
            'return' => 'Collection',
        ];
        return static::findAll($arrOptions);
    }

    public function getListDetails()
    {
        $arrAll = $this->row();
        $arrRet = array_intersect_key($arrAll, array_flip(['id', 'tstamp', 'price', 'cleaning_fee']));
        $arrRet['tstamp'] = date('c', $arrRet['tstamp']);
        $arrRet['price'] = (int) $arrRet['price'];
        $arrRet['cleaning_fee'] = (int) $arrRet['cleaning_fee'];
        $arrRet['sum'] = $arrRet['price'] + $arrRet['cleaning_fee'];

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

    public function getVoucherListDetails()
    {
        $arrAll = $this->row();
        $arrRet = array_intersect_key($arrAll, array_flip(['id', 'tstamp', 'voucher_id', 'price', 'cleaning_fee']));
        $arrRet['voucher_id'] = (int) $arrRet['voucher_id'];
        $arrRet['tstamp'] = date('c', $arrRet['tstamp']);
        $arrRet['sum'] = $arrRet['price'] + $arrRet['cleaning_fee'];
        unset($arrRet['price']);
        unset($arrRet['cleaning_fee']);

        return $arrRet;
    }

    public static function getVouchers()
    {
        $arrOptions = [
            'column' => [
                'type = ?',
            ],
            'value' => ['VOUCHER'],
            'order' => 'id DESC',
            'return' => 'Collection',
        ];
        return static::findAll($arrOptions);
    }
    public static function getVouchersListDetails()
    {
        $objCollection = self::getVouchers();
        if (!$objCollection) {
            return [];
        }
        $arrRet = [];
        foreach ($objCollection as $objVoucher) {
            $arrRet[] = $objVoucher->getVoucherListDetails();
        }
        return $arrRet;

    }
}
