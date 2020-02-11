<?php

namespace Lumturo\ContaoTF2Bundle\Controller;

use Lumturo\ContaoTF2Bundle\Model\BookingModel;
use Lumturo\ContaoTF2Bundle\Model\DocumentModel;
use Lumturo\ContaoTF2Bundle\TF2Invoice;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class InvoiceController extends LumturoController
{
    public function listAction($id)
    {
        // check Login
        if (($ret = $this->checkLogin()) instanceof Response) {
            return $ret;
        }

        $objBooking = BookingModel::findByPk($id);
        if (!$objBooking) {
            return $this->createErrorResponse('Buchung nicht in der Datenbank gefunden!');
        }
        $arrInvoicesListDetails = $objBooking->getInvoicesListDetails();
        $objJsonResponse = $this->createSuccessMessageResponse(['invoices' => $arrInvoicesListDetails]);
        return $objJsonResponse;
    }

    public function createAction($id)
    {
        // check Login
        if (($ret = $this->checkLogin()) instanceof Response) {
            return $ret;
        }

        $objBooking = BookingModel::findByPk($id);
        if (!$objBooking) {
            return $this->createErrorResponse('Buchung nicht in der Datenbank gefunden!');
        }

        $objDocument = new DocumentModel();
        $objDocument->tstamp = time();

        $objInvoice = new TF2Invoice();

        $objDocument->setBooking($objBooking);
        $objDocument->createInvoice($objInvoice);
        $objDocument->save();

        $arrInvoicesListDetails = $objBooking->getInvoicesListDetails();
        $objJsonResponse = $this->createSuccessMessageResponse(['invoices' => $arrInvoicesListDetails]);
        return $objJsonResponse;
    }

    public function showAction($id)
    {
        // check Login
        if (($ret = $this->checkLogin()) instanceof Response) {
            return $ret;
        }

        // $this->container->get('contao.framework')->initialize();

        $objDocument = DocumentModel::findByPk($id);
        if (!$objDocument) {
            return $this->createErrorResponse('Dokument nicht gefunden!');
        }
        $tempname = tempnam('', 'report_');
        rename($tempname, $tempname .= '.pdf');
        file_put_contents($tempname, $objDocument->doc);

        return new BinaryFileResponse($tempname, 200, ['Content-type' => 'application/pdf'], TRUE, ResponseHeaderBag::DISPOSITION_INLINE);
    }

    public function editAction(Request $objRequest, $id)
    {
        // check Login
        if (($ret = $this->checkLogin()) instanceof Response) {
            return $ret;
        }

        // $this->container->get('contao.framework')->initialize();

        $objDocument = DocumentModel::findByPk($id);
        if (!$objDocument) {
            return $this->createErrorResponse('Dokument nicht gefunden!');
        }
        $objBooking = BookingModel::findByPk($objDocument->pid);
        if (!$objBooking) {
            return $this->createErrorResponse('Buchung nicht gefunden!');
        }

        $strPost = $objRequest->getContent();
        /* @var $arrPost */
        $arrPost = @json_decode($strPost, true);
        if (!$arrPost) {
            $this->createErrorResponse('Keine Daten übermittelt!');
        }
        $arrPost = $this->xss_clean($arrPost);

        foreach (['tstamp', 'price'] as $strKey) {
            if (isset($arrPost[$strKey]) && strlen($arrPost[$strKey])) {
                switch ($strKey) {
                    case 'tstamp':
                        $varValue = strtotime($arrPost[$strKey]);
                        if ($varValue) {
                            $objDocument->tstamp = $varValue;
                            $objBooking->tstamp = $varValue;
                        }
                        break;
                    case 'price':
                        // setze am Booking-Objekt, da unten in ->createInvoice()
                        // dann in Document kopiert wird
                        if ((int) $objBooking->price != (int) $arrPost['price']) {
                            $objBooking->cleaning_fee = 0;
                        }
                        $objBooking->price = (int) $arrPost['price'];
                        break;
                }
            }
        }
        $objInvoice = new TF2Invoice();
        $objDocument->setBooking($objBooking);
        $objDocument->createInvoice($objInvoice);
        $objDocument->save();


        $arrInvoicesListDetails = $objBooking->getInvoicesListDetails();
        $objJsonResponse = $this->createSuccessMessageResponse(['invoices' => $arrInvoicesListDetails]);
        return $objJsonResponse;
    }
}
