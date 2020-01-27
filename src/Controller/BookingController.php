<?php

namespace Lumturo\ContaoTF2Bundle\Controller;

use Lumturo\ContaoTF2Bundle\Model\BookingModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;;

class BookingController extends LumturoController
{
    public function detailsAction($id)
    {

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

    public function editAction(Request $objRequest, $id)
    {
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
        } else {
            $objBooking = new BookingModel();
            $objBooking->create_ts = time();
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
        foreach (['firstname', 'name', 'email', 'address', 'zip', 'city', 'telephone', 'notice', 'booking_from', 'booking_to', 'booking_status', 'booking_type', 'price', 'cleaning_fee'] as $strKey) {
            $objBooking->{$strKey} = $arrPost[$strKey];
        }
        // $objBooking->mergeRow($arrPost);
        try {
            $objBooking->save();
            $objResponse = new JsonResponse([
                'status' => 'ok',
                'booking' => [
                    'details' => $this->detailsForFrontend($arrPost),
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

    /**
     * 
     */
    private function createErrorResponse($strMessage, $strStatus = 'error')
    {
        return new JsonResponse([
            'status' => $strStatus,
            'message' => $strMessage
        ]);
    }
}
