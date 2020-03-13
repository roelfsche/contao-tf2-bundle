<?php

namespace Lumturo\ContaoTF2Bundle\Model;

use \DateTime;
use Laminas\Mail\Transport\Smtp;
use Lumturo\ContaoTF2Bundle\Mailbox;

class EmailModel extends \Model
{
    /**
     * Table name
     * @var string
     */
    protected static $strTable = 'tl_email';


    public static function validatePost(&$arrPost)
    {
        foreach ([/*'id' => 0,*/'to_address' => 255, 'subject' => 255, 'footer' => 0, 'body' => 0] as $strIndex => $intMaxLength) {
            if (!isset($arrPost[$strIndex])) {
                throw new \Exception($strIndex . ' nicht gesetzt');
            }
            if ($intMaxLength) {
                $arrPost[$strIndex] = substr($arrPost[$strIndex], 0, $intMaxLength);
            }
        }

        if ($arrPost['id']) {
            $objMail = self::findByPk($arrPost['id']);
            if (!$objMail) {
                throw new \Exception('Referenzierte Mail nicht in der DB gefunden.');
            }
        }
        return $arrPost;
    }

    public static function createAndSend($arrPost)
    {
        $objRefMail = self::findByPk($arrPost['id']);

        $arrPost['body_html'] = $arrPost['body'] . "\r\n" . $arrPost['footer'];
        $arrPost['body_text'] = strip_tags($arrPost['body_html']);
        $arrPost['from_address'] = 'buchungsinformation@turm-fuer-zwei.de';
        $arrPost['tstamp'] = time();
        $arrPost['received_ts'] = time();
        $arrPost['direction'] = 'O';
        if ($objRefMail) {
            $arrPost['booking_id'] = $objRefMail->booking_id;
        }
        unset($arrPost['id']);

        $objMail = new self();
        foreach (array_keys($arrPost) as $strKey) {
            $objMail->{$strKey} = $arrPost[$strKey];
        }

        $objMailbox = new Mailbox();
        $objLaminasMessage = $objMailbox->sendMail($objMail, DocumentModel::findByPk($arrPost['invoice']));

        $objMail->direction = 'O';
        // $objMail->message_id = $objLaminasMessage->getHe
        $objMail->save();
    }

    public static function findByBookingId($intId)
    {
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

    public static function findNewMails($intBookingId = 0)
    {
        $arrOptions = [
            'column' => [
                'flag_new = ?'
            ],
            'value' => ['1'],
            'order' => 'tstamp DESC',
            'return' => 'Collection'
        ];

        if ($intBookingId) {
            $arrOptions['column'][] = 'booking_id = ?';
            $arrOptions['value'][] = $intBookingId;
        }
        return static::findAll($arrOptions);
    }

    public static function findForFrontend($arrConfig = [])
    {
        $arrRet = [];
        $arrConfig = array_merge($arrConfig, [
            'order' => 'tstamp DESC'
        ]);
        $objCollection = self::findAll($arrConfig);
        if ($objCollection) {
            foreach ($objCollection->getIterator() as $objEmail) {
                $arrRet[] = $objEmail->getListDetails();
            }
        }
        return $arrRet;
    }

    public function getListDetails()
    {
        $arrAll = $this->row();
        $arrRet = array_intersect_key($arrAll, array_flip(['id', 'from_address', 'received_ts', 'subject', 'direction']));
        $arrRet['received_ts'] = date('c', $arrRet['received_ts']);

        return $arrRet;
    }
    public function getDetails($boolForReply = TRUE)
    {
        $arrAll = $this->row();
        $arrRet = array_intersect_key($arrAll, array_flip([
            'id',
            'booking_id',
            'direction',
            'from_address',
            'to_address',
            'subject',
            'body_html'
        ]));

        //wenn im body_html nichts drinsteht, weil in der Mail nicht gefunden, dann nehme ich body_text
        if (!strlen(trim($arrRet['body_html']))) {
            $arrRet['body_html'] = nl2br($this->body_text);
        }

        //hänge die Dokumente an
        $arrRet['invoices'] = array(); //default
        //schaue, ob die buchung bereits rechnungs-dokumente enthält
        if ($arrRet['booking_id']) {
            $objDocumentCollection = DocumentModel::findByBookingId($arrRet['booking_id']);
            if ($objDocumentCollection) {
                foreach ($objDocumentCollection->getIterator() as $objDocument) {
                    $arrRet['invoices'][] = [
                        'id' => $objDocument->id,
                        'tstamp' => date('c', $objDocument->tstamp),
                        'sum' => $objDocument->price + $objDocument->cleaning_fee
                    ];
                }
            }
        }

        if ($boolForReply) {
            //quote jetzt noch den Inhalt, so dass jede Zeile mit einem '>' vorangestellt wird.
            if (strlen($arrRet['body_html'])) {
                // von Mac-Mail geklaut:
                // div mit border drum herum
                $arrRet['body_html'] = '<div style="margin: 10px 5px 5px 10px; padding: 10px 0px 10px 10px; border-left-color: rgb(195, 217, 229); border-left-width: 2px; border-left-style: solid;">' .
                    $arrRet['body_html'] .
                    '</div>';
                // $arrRet['body_html'] = str_replace(array(
                //     "\\r\\n",
                //     "\\r",
                //     "\\n",
                // ), "<br/>> ", $arrRet['body_html']);
                // $arrRet['body_html'] = str_replace('<p>', '<p>>', $arrRet['body_html']);
                //erste zeile auch noch
                // $arrRet['body_html'] = "> " . $arrRet['body_html'];
            }
        }

        return $arrRet;
    }

    /**
     * Diese Methode versucht, eine Zuordnung zu einer Buchung vorzunehmen
     * 
     * @param string $mail_text - der kompl. Mailtext
     * @param string $ident - der IdentString, in dem die Buchungs-Nr. drin ist
     * @return boolean
     */
    public function guessBookingId($mail_text, $ident)
    {
        $ident = addcslashes($ident, '\^$[]{}()<>|.+-?*');

        //ersetze %%booking_id%% durch ([0-9]+)
        $ident = str_replace('%%booking_id%%', '[0-9]+', $ident);
        $ident = '/' . $ident . '/';

        $result_arr = array();
        preg_match($ident, $mail_text, $result_arr);

        if (count($result_arr)) {
            $booking_id_arr = array();
            preg_match('/[0-9]+/', $result_arr[0], $booking_id_arr);
            if (count($booking_id_arr)) {
                $this->booking_id = $booking_id_arr[0];
                return TRUE;
            }
        }

        # nicht gefunden...
        return FALSE;
    }

    public function getFromAddress($only_address = TRUE)
    {
        if ($only_address) {
            return $this->stripAddress($this->from_address);
        }
        return $this->from_address;
    }

    public function getToAddress($only_address = TRUE)
    {
        if ($only_address) {
            return $this->stripAddress($this->to_address);
        }
        return $this->to_address;
    }

    /**
     * Diese Methode extrahiert (im Bedarfsfall) die reine Emailadresse.
     * gibt ja "Klausi Müller <klaus@gmx.de>"
     * brauche dann "klaus@gmx.de"
     * 
     * @param string $address
     * @return string
     */
    private function stripAddress($address)
    {
        $expression = '/[^\s<>]+@[^\s<>]+/';

        $address_arr = array();
        $ret = preg_match($expression, $address, $address_arr);
        return $address_arr[0];
    }
}
