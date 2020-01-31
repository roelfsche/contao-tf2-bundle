<?php

namespace Lumturo\ContaoTF2Bundle\Controller;

use Lumturo\ContaoTF2Bundle\Mailbox;
use Lumturo\ContaoTF2Bundle\Model\BookingModel;
use Lumturo\ContaoTF2Bundle\Model\DocumentModel;
use Lumturo\ContaoTF2Bundle\Model\EmailModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Lumturo\ContaoTF2Bundle\TF2Invoice;
use Symfony\Component\HttpFoundation\Response;

class BookingController extends LumturoController
{
    public function detailsAction($id)
    {
        // check Login
        if (($ret = $this->checkLogin()) instanceof Response) {
            return $ret;
        }

        $objBooking = BookingModel::findByPk($id);
        if (!$objBooking) {
            return $this->createErrorResponse('Buchung nicht in der Datenbank gefunden!');
        }

        $arrBookingDetails = $objBooking->getDetails();
        $arrEmailListDetails = $objBooking->getEmailListDetails();
        $arrInvoicesDetails = $objBooking->getInvoicesListDetails();

        $objResponse = new JsonResponse([
            'status' => 'ok',
            'booking' => [
                'details' =>  $arrBookingDetails,
                'emails' => $arrEmailListDetails,
                'invoices' => $arrInvoicesDetails
            ],
            'errors' => [
                'booking' => [
                    'details' => [
                        'booking_from' => 0,
                        'booking_to' => 0
                    ]
                ]
            ]
        ]);
        return $objResponse;
    }

    public function newAction(Request $objRequest)
    {
        // check Login
        if (($ret = $this->checkLogin()) instanceof Response) {
            return $ret;
        }

        $arrPost = $objRequest->request->all(); //getContent();
        $arrPost = $this->xss_clean($arrPost);

        $arrErrors = BookingModel::validatePostFromFrontend($arrPost);
        if (count($arrErrors)) {
            // Format von früher....
            return $this->createSuccessMessageResponse([
                'success' => 1,
                'status' => 421,
                'message' => 'ok',
                'type' => 'json',
                'data' => $arrErrors
            ]);
        }

        try {
            // lege die buchung an
            $objBooking = new BookingModel();
            $objBooking->setRow($arrPost);
            $objBooking->tstamp = time();
            $objBooking->create_ts = time();
            $objBooking->booking_status = BookingModel::STATUS_BOOKED;
            $objBooking->booking_type = 'B'; // Buchung
            $objBooking->new_email = 0; // Buchung
            $objBooking->calculatePrice(floatval($GLOBALS['TL_CONFIG']['default_price']), floatval($GLOBALS['TL_CONFIG']['default_cleaning_fee']));
            $objBooking->save();

            $objDocument = new DocumentModel();
            $objDocument->tstamp = time();

            $objInvoice = new TF2Invoice();

            $objDocument->setBooking($objBooking);
            $objDocument->createInvoice($objInvoice);
            $objDocument->save();

            // versende jetzt eine Email an den Bucher
            $objEmail = new EmailModel();
            $objEmail->setRow([
                'body_html' => $objBooking->fillTemplate($GLOBALS['TL_CONFIG']['email_booking_template_for_invoice']) . $objBooking->fillTemplate($GLOBALS['TL_CONFIG']['email_template_footer'], $GLOBALS['TL_CONFIG']['email_template_booking_ident']),
                // will ich in der email nicht haben, da als anhang angezeigt 
                // (müsste nochmal zeit investieren)
                // 'body_text' => strip_tags($objBooking->fillTemplate($GLOBALS['TL_CONFIG']['email_booking_template_for_invoice']) . $objBooking->fillTemplate($GLOBALS['TL_CONFIG']['email_template_footer'], $GLOBALS['TL_CONFIG']['email_template_booking_ident'])),
                'subject' => 'Buchungsbestätigung vom Turm für zwei',
                'to_address' => $objBooking->email,
                'bcc_address' => 'sms@turm-fuer-zwei.de',
                'from_address' => 'buchung@turm-fuer-zwei.de',
                'booking_id' => $objBooking->id,
                'received_ts' => time(), // naja... , wird aber im Frontend angezeigt..
            ]);

            $objMailbox = new Mailbox();
            $objMailbox->sendMail($objEmail, $objDocument);

            $objEmail->body_text = '';
            $objEmail->direction = 'O';
            $objEmail->save();

            $objBooking->new_email = 1;
            $objBooking->save();
        } catch (\Exception $objE) {
            // TEST!!!
            return $this->createSuccessMessageResponse([
                'success' => 0,
                'message' => $objE->getMessage(),
                'type' => 'json',
                'data' => $arrErrors
            ], 421);
        }
        // gebe thank-you-template zurück
        $strTemplate = $objBooking->fillTemplate($GLOBALS['TL_CONFIG']['thank_you_template']);

        return $this->createSuccessMessageResponse([
            'success' => 1,
            'type' => 'html',
            'data' => str_replace('"', '\"', '<div style="width: 464px; margin: 50px auto;">' . preg_replace('/(^|\n)\s+/', '', $strTemplate) . '</div>') 
        ], 200);
    }

    public function editAction(Request $objRequest, $id)
    {
        // check Login
        if (($ret = $this->checkLogin()) instanceof Response) {
            return $ret;
        }
        $strPost = $objRequest->getContent();
        /* @var $arrPost */
        $arrPost = @json_decode($strPost, true);
        if (!$arrPost) {
            $this->createErrorResponse('Keine Daten übermittelt!');
        }
        $arrPost = $this->xss_clean($arrPost);

        $this->container->get('contao.framework')->initialize();

        // validation + Werte-Korrektur
        $objBooking = NULL;
        $arrErrors = BookingModel::validatePost($arrPost);
        if ($arrPost['id']) {
            $objBooking = BookingModel::findByPk($arrPost['id']);
            if (!$objBooking) {
                return $this->createErrorResponse('Buchung nicht gefunden!');
            }
        } else {
            $objBooking = new BookingModel();
            $objBooking->tstamp = time();
        }


        if (count($arrErrors['booking']['details'])) {
            $objResponse = new JsonResponse([
                'status' => 'error',
                'booking' => [
                    'details' => $this->detailsForFrontend($arrPost),
                    'emails' => (($objBooking) ? $objBooking->getEmailListDetails() : []),
                    'invoices' => (($objBooking) ? $objBooking->getInvoicesListDetails() : [])
                ],
                'errors' => $arrErrors
            ]);
            return $objResponse;
        }

        // abspeichern
        foreach (['firstname', 'name', 'email', 'address', 'zip', 'city', 'telephone', 'notice', 'booking_from', 'booking_to', 'booking_status', 'booking_type'] as $strKey) {
            $objBooking->{$strKey} = $arrPost[$strKey];
        }

        $objBooking->calculatePrice(floatval($GLOBALS['TL_CONFIG']['default_price']), floatval($GLOBALS['TL_CONFIG']['default_cleaning_fee']));

        try {
            $objBooking->save();
            $objResponse = new JsonResponse([
                'status' => 'ok',
                'booking' => [
                    'details' => $objBooking->getDetails(), //$this->detailsForFrontend($arrPost),
                    'emails' => (($objBooking) ? $objBooking->getEmailListDetails() : []),
                    'invoices' => (($objBooking) ? $objBooking->getInvoicesListDetails() : [])
                ],
                'errors' => $arrErrors
            ]);
        } catch (\RuntimeException $objRe) {
            $objResponse = $this->createErrorResponse('Fehler beim Speichern');
        }
        return $objResponse;
    }


    public function removeAction($id)
    {
        // check Login
        if (($ret = $this->checkLogin()) instanceof Response) {
            return $ret;
        }
        $objBooking = BookingModel::findByPk($id);
        if (!$objBooking) {
            return $this->createErrorResponse('Buchung nicht in der Datenbank gefunden!');
        }

        $objBooking->booking_status = BookingModel::STATUS_CANCEL;
        $objBooking->save();
        return $this->createSuccessMessageResponse([]);
    }

    private function detailsForFrontend($arrDetails)
    {
        $arrDetails['booking_from'] = $this->tsToCalendarDate($arrDetails['booking_from']);
        $arrDetails['booking_to'] = $this->tsToCalendarDate($arrDetails['booking_to'], FALSE);
        return $arrDetails;
    }
    /**
     * In der DB stehen folgende Daten (als unixTs):
     * 
     * from_ts: YYYY-MM-DD 12:00:00
     * to_ts:   YYYY-MM-DD 11:59:59
     * 
     * Historisch gewachsen, damit ich nie Probleme mit Sommerzeit etc. habe.
     * Der neue Kalender benötigt nun aber genau 00:00:00.
     * Kann aber auch ISO-Format, also gebe ich das zurück
     * 
     * 
     * @param int   $intTs - UnixTs
     * @return string 
     */
    private function tsToCalendarDate($intTs, $boolFrom = TRUE)
    {
        if ($boolFrom) {
            return date('c', $intTs -  43200);
        } else {
            return date('c', $intTs -  43199);
        }
    }
}
