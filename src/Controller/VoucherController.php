<?php

namespace Lumturo\ContaoTF2Bundle\Controller;

use Lumturo\ContaoTF2Bundle\Model\BookingModel;
use Lumturo\ContaoTF2Bundle\Model\DocumentModel;
use Lumturo\ContaoTF2Bundle\TF2Invoice;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class VoucherController extends LumturoController
{
    public function listAction()
    {
        // check Login
        if (($ret = $this->checkLogin()) instanceof Response) {
            return $ret;
        }

        $arrVouchersListDetails = DocumentModel::getVouchersListDetails();
        $objJsonResponse = $this->createSuccessMessageResponse(['vouchers' => $arrVouchersListDetails]);
        return $objJsonResponse;
    }

    public function createAction(Request $objRequest)
    {
        // check Login
        if (($ret = $this->checkLogin()) instanceof Response) {
            return $ret;
        }

        $strPost = $objRequest->getContent();
        /* @var $arrPost */
        $arrPost = @json_decode($strPost, true);
        // $arrPost = $objRequest->request->all(); //getContent();
        $arrPost = $this->xss_clean($arrPost);

        $objDocument = new DocumentModel();
        $objDocument->price = $arrPost['price'];
        $objDocument->voucher_id = $arrPost['voucher_id'];
        $objDocument->tstamp = time();
        $objDocument->type = 'VOUCHER';
        $objDocument->save();
        
        $objJsonResponse = $this->createSuccessMessageResponse(['vouchers' => DocumentModel::getVouchersListDetails()]);
        return $objJsonResponse;
    }
}
