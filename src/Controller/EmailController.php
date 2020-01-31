<?php

namespace Lumturo\ContaoTF2Bundle\Controller;

use Exception;
use Lumturo\ContaoTF2Bundle\Mailbox;
use Lumturo\ContaoTF2Bundle\Model\BookingModel;
use Lumturo\ContaoTF2Bundle\Model\EmailModel;
use Symfony\Component\HttpFoundation\Request;

class EmailController extends LumturoController
{
    /**
     * Abruf vom Email-Server
     */
    public function readAction()
    {

        $objMailbox = new Mailbox();
        $objMailbox->loadAndSaveNewEmails();
        return $this->listAction();
    }

    /**
     * List der Mails aus der DB
     */
    public function listAction()
    {
        $arrEmails = EmailModel::findForFrontend([
            'column' => ['tstamp > ?'],
            'value' => strtotime('-1 year')
        ]);
        return $this->createSuccessMessageResponse(['emails' => $arrEmails]);
    }

    /**
     * liefert die Email-Vorlagen
     */
    public function templatesAction()
    {
        //Email-Templates
        $arrTemplates = [
            'reply' => $GLOBALS['TL_CONFIG']['email_template_reply'],
            'reminder' => $GLOBALS['TL_CONFIG']['email_template_remind'],
            'transaction_received' => $GLOBALS['TL_CONFIG']['email_booking_template_transaction_received'],
            'invoice' => $GLOBALS['TL_CONFIG']['email_booking_template_for_invoice'],
            'cash' => $GLOBALS['TL_CONFIG']['email_booking_template_for_money'],
            'footer' => $GLOBALS['TL_CONFIG']['email_template_footer']

        ];
        // $strBookingIdIdentTemplate = $GLOBALS['TL_CONFIG']['email_template_booking_ident'];

        return $this->createSuccessMessageResponse([
            'email' => [
                'details' => ['invoices' => []], //$arrDetails,
                'templates' => $arrTemplates
            ]
        ]);
    }

    /**
     * liefert die Details der Email-zur Anzeige an.
     * 
     * Der Orginal-Body wird - eingerückt - an alle Email-Templates angehangen
     */
    public function detailsAction($id)
    {
        $objEmail = EmailModel::findByPk($id);
        if (!$objEmail) {
            return $this->createErrorResponse('Email nicht in der Datenbank gefunden!');
        }

        $arrDetails = $objEmail->getDetails(TRUE);

        //Email-Templates
        $arrTemplates = [
            'reply' => $GLOBALS['TL_CONFIG']['email_template_reply'],
            'reminder' => $GLOBALS['TL_CONFIG']['email_template_remind'],
            'transaction_received' => $GLOBALS['TL_CONFIG']['email_booking_template_transaction_received'],
            'invoice' => $GLOBALS['TL_CONFIG']['email_booking_template_for_invoice'],
            'cash' => $GLOBALS['TL_CONFIG']['email_booking_template_for_money'],
            'footer' => $GLOBALS['TL_CONFIG']['email_template_footer']

        ];
        $strBookingIdIdentTemplate = $GLOBALS['TL_CONFIG']['email_template_booking_ident'];

        $objBooking = BookingModel::findByPk($objEmail->booking_id);
        if ($objBooking) {
            foreach (['reply', 'reminder', 'transaction_received', 'invoice', 'cash', 'footer'] as $strIndex) {
                if ($strIndex == 'footer') {
                    $arrTemplates[$strIndex] = $objBooking->fillTemplate($arrTemplates[$strIndex], $strBookingIdIdentTemplate);
                } else {
                    // hänge den vorherigen Body noch mit an
                    $arrTemplates[$strIndex] = $objBooking->fillTemplate($arrTemplates[$strIndex]) . $arrDetails['body_html'];
                }
            }
        }

        // "Neu"-Flag weg
        if ((int) $objEmail->flag_new) {
            $objEmail->flag_new = '0';
            $objEmail->save();
        }

        if ($objBooking) {
            $objMailCollection = EmailModel::findNewMails($objBooking->id);
            if ($objMailCollection) {
                $objBooking->new_email = '1';
            } else {
                $objBooking->new_email = '0';
            }
            $objBooking->save();
        }

        return $this->createSuccessMessageResponse([
            'email' => [
                'details' => $arrDetails,
                'templates' => $arrTemplates
            ]
        ]);
    }

    /**
     * versende eine Email.
     * 
     * Achtung: $_POST['id'] muss gesetzt sein.
     * = id der referenzierten Mail
     */
    public function sendAction(Request $objRequest)
    {
        $strPost = $objRequest->getContent();
        /* @var $arrPost */
        $arrPost = @json_decode($strPost, true);
        if (!$arrPost) {
            $this->createErrorResponse('Keine Daten übermittelt!');
        }
        $arrPost = $this->xss_clean($arrPost);

        try {
            $arrPost = EmailModel::validatePost($arrPost);
            $objEmail = EmailModel::createAndSend($arrPost);
            if ($arrPost['id']) {
                $objRefEmail = EmailModel::findByPk(($arrPost['id']));
                if ($objRefEmail) {
                    $objBooking = BookingModel::findByPk($objRefEmail->booking_id);
                    if ($objBooking) {
                        $arrEmails = $objBooking->getEmailListDetails();
                        return $this->createSuccessMessageResponse(['emails' => $arrEmails]);
                    }
                }
            } else {
            }
        } catch (Exception $e) {
            return $this->createErrorResponse($e->getMessage());
        }

        return $this->createSuccessMessageResponse([]);
    }

    public function removeAction($id)
    {
        $objEmail = EmailModel::findByPk($id);
        if (!$objEmail) {
            return $this->createErrorResponse('Email nicht in der Datenbank gefunden!');
        }

        $objEmail->delete();
        return $this->listAction();
    }
}
