<?php

namespace Lumturo\ContaoTF2Bundle;

use Contao\Database;
use Laminas\Mail\Message;
use Laminas\Mail\Storage\Imap;
use Laminas\Mail\Transport\Smtp;
use Laminas\Mail\Transport\SmtpOptions;
use Laminas\Mime\Message as MimeMessage;
use Laminas\Mime\Mime;
use Laminas\Mime\Part;
use Lumturo\ContaoTF2Bundle\Model\BookingModel;
use Lumturo\ContaoTF2Bundle\Model\EmailModel;

class Mailbox
{
    private $arrConfig = array(
        'imap' => array(
            'name' => 'sslin.df.eu',
            'host' => 'sslin.df.eu',
            'port' => 993,
            'user' => 'buchungsinformation@turm-fuer-zwei.de',  // 'rolf.staege@lumturo.net', 
            'password' => '', // kommt nun aus Contao-Config'cFh>Zu/5hmLc',
            'ssl' => 'SSL'
        ),
        'smtp' => array(
            'name' => 'sslout.df.eu',
            'host' => 'sslout.df.eu',
            'port' => 465,
            'connection_class' => 'login',
            'connection_config' => [
                'username' => 'buchungsinformation@turm-fuer-zwei.de',  // 'rolf.staege@lumturo.net', 
                'password' => '', // kommt nun aus contao-config'cFh>Zu/5hmLc',
                'ssl' => 'SSL'
            ],
        )
        // 'imap' => array(
        //     'name' => 'sslmailpool.ispgateway.de',
        //     'host' => 'sslmailpool.ispgateway.de',
        //     'port' => 993,
        //     'user' => 'buchung@turm-fuer-zwei.de',  // 'rolf.staege@lumturo.net', 
        //     'password' => ':#EDY3rHLp?Ass[',
        //     'ssl' => 'SSL'
        // ),
        // 'smtp' => array(
        //     'name' => 'smtprelaypool.ispgateway.de',
        //     'host' => 'smtprelaypool.ispgateway.de',
        //     'port' => 465,
        //     'connection_class' => 'login',
        //     'connection_config' => [
        //         'username' => 'buchung@turm-fuer-zwei.de',  // 'rolf.staege@lumturo.net', 
        //         'password' => ':#EDY3rHLp?Ass[',
        //         'ssl' => 'SSL'
        //     ],
        // )
    );

    private $objSmtpOptions = NULL;

    private $objDatabase = NULL;

    private $objMailbox = NULL;

    private $objTransport = NULL;

    /**
     * pw darf nicht im code stehen --> gab sofort Spam; vermute github ist gehackt...
     */
    public function __construct()
    {
        //pw darf nicht im code stehen --> gab sofort Spam; vermute github ist gehackt...
        $strPassword = html_entity_decode($GLOBALS['TL_CONFIG']['email_password']);
        $this->arrConfig['smtp']['connection_config']['password'] = $strPassword;
        $this->arrConfig['imap']['password'] = $strPassword;

        $this->objSmtpOptions = new SmtpOptions($this->arrConfig['smtp']);
        $this->objDatabase = Database::getInstance(['dbCharset' => 'utf8mb4']);
    }

    /**
     * Sendet eine Mail per FTP
     * 
     * @param Lumturo\ContaoTF2Bundle\ModelEmailModel $objDbMail
     * 
     * @return Laminas\Mail\Message
     */
    public function sendMail(&$objDbMail, $objInvoice = NULL)
    {

        if (!$this->objTransport) {
            $this->objTransport = new Smtp($this->objSmtpOptions);
        }
        $objMessage = new Message();
        $objMessage->addTo($objDbMail->getToAddress());
        $objMessage->addFrom($objDbMail->getFromAddress());
        $objMessage->setSubject($objDbMail->subject);

        $html = new Part($objDbMail->body_html);
        $html->type = Mime::TYPE_HTML; //Mime::TYPE_HTML;
        $html->charset = 'utf-8';
        $html->encoding = Mime::ENCODING_QUOTEDPRINTABLE;

        if ($objDbMail->body_text) {
            $text = new Part($objDbMail->body_text);
            $text->type = Mime::TYPE_TEXT;
            $text->charset = 'utf-8';
            $text->encoding = Mime::ENCODING_QUOTEDPRINTABLE;
            $arrParts = [$html, $text];
        } else {
            $arrParts = [$html];
        }

        if ($objInvoice) {
            $objInvoicePart = new Part($objInvoice->doc);
            $objInvoicePart->type = 'application/pdf'; //Mime::TYPE_OCTETSTREAM;
            $objInvoicePart->charset = 'utf-8';
            $objInvoicePart->disposition = Mime::DISPOSITION_ATTACHMENT;
            $objInvoicePart->filename = 'Rechnung.pdf';
            $objInvoicePart->encoding = Mime::ENCODING_BASE64;

            $arrParts[] = $objInvoicePart;
        }
        $objBody = new MimeMessage();
        $objBody->setParts($arrParts);

        $objMessage->setBody($objBody);
        // $objMessage->setBody($html);

        $contentTypeHeader = $objMessage->getHeaders()->get('Content-Type');
        if (count($arrParts) == 1)
            $contentTypeHeader->setType('text/html');
        else
            $contentTypeHeader->setType('multipart/related');

        $this->objTransport->send($objMessage);
        // $objDbMail->
    }

    /**
     * Diese Methode sendet eine Email anhand der Daten im Array
     * 
     */
    public function sendNotifyMail($arrMail)
    {
        if (!$this->objTransport) {
            $this->objTransport = new Smtp($this->objSmtpOptions);
        }
        $objMessage = new Message();
        $objMessage->addTo($arrMail['to']);
        $objMessage->addFrom($arrMail['from']);
        $objMessage->setSubject($arrMail['subject']);

        // $html = new Part($objDbMail->body_html);
        // $html->type = Mime::TYPE_HTML; //Mime::TYPE_HTML;
        // $html->charset = 'utf-8';
        // $html->encoding = Mime::ENCODING_QUOTEDPRINTABLE;

        // if ($objDbMail->body_text) {
        $text = new Part($arrMail['text']);
        $text->type = Mime::TYPE_TEXT;
        $text->charset = 'utf-8';
        $text->encoding = Mime::ENCODING_QUOTEDPRINTABLE;
        $arrParts = [$text];
        // } else {
        //     $arrParts = [$html];
        // }

        $objBody = new MimeMessage();
        $objBody->setParts($arrParts);

        $objMessage->setBody($objBody);

        $contentTypeHeader = $objMessage->getHeaders()->get('Content-Type');
        $contentTypeHeader->setType('text/plain');

        $this->objTransport->send($objMessage);
    }

    /**
     * Diese Methode holt neue Emails von der Mailbox, speichert sie in der DB und liefert alle Mails aus der DB zurück
     * @param array $options - options, die an {@link readMailbox()} weitergegeben werden
     * aktuell:
     * $options['remove'] = TRUE/FALSE (default: FALSE)
     * $options['copyTo'] = string - Name des Verzeichnisses (default: '')-> kein kopieren
     * 
     * @return array - alle neu geladenen Emails
     */
    public function loadAndSaveNewEmails($options = null)
    {
        $_defaults = array(
            'remove' => FALSE,
            'copyTo' => NULL
        );
        if ($options == null) {
            $options = array();
        }
        $_options = array_merge($_defaults, $options);
        $arrNewMails = $this->readMailbox($_options);

        if (count($arrNewMails)) {
            $this->saveMailToDatabase($arrNewMails);
            //     //lösche nun alle mails vom imap-server
            // $this->removeAllMailsFromServer();
            return $arrNewMails;
        }
        return array();
    }

    /**
     * Diese Methode speichert eine oder mehrere Mails in der DB.
     * Sie überprüft, ob die Mail bereits in der DB vorhanden ist. Wenn ja und wenn sie älter als 1 Jahr ist
     * wird sie gelöscht.
     * Das Mailobjekt selbst wird nicht mitgespeichert, da es zu gross sein kann (passt damit dann nicht mehr in ein DB-Feld).
     * Ausserdem werden Mails, die grösser als 16MB sind ignoriert (sonst läuft php schnell gegen das Speicherlimit).
     * Es wird vom body nur der erste part in die DB übernommen.
     * 
     * @param Zend_Mail|array $mail - mail object or array of mail object
     */
    protected function saveMailToDatabase($mails)
    {

        if ($mails instanceof \Laminas\Mail\Storage\Message) {
            $mails = array(
                $mails
            );
        }

        if (!is_array($mails)) {
            return;
        }
        //ok start iterating
        foreach ($mails as $intMailboxMailId => $mail) {

            //Subject fehlte auch mal
            try {
                $subject = substr($mail->getHeaderField('subject'), 0, 254);
            } catch (\Exception $e) {
                $subject = '';
                log_message("Mail ohne  Subject gefunden :-(\n" . $mail);
            }
            //to-Feld fehlte auch mal
            try {
                $to = substr(utf8_encode($mail->to), 0, 254);
            } catch (\Exception $e) {
                $to = '';
            }

            //from fehlte bis dahin noch nicht, aber wer weiss....
            try {
                $from = substr(utf8_encode($mail->from), 0, 254);
            } catch (\Exception $e) {
                $from = '';
            }

            //hatte eine mail von 'GULP' dabei, die hatte keine message-id (ist sowas überhaupt erlaubt)?
            try {
                $message_id = substr($mail->getHeaderField('message-id'), 0, 254);
            } catch (\Exception $e) {
                $message_id = md5($subject . $to . $from);
                log_message("Mail ohne message-id gefunden :-(\n" . $mail);
            }

            try {
                $received_ts = strtotime($mail->date);
                if (!$received_ts) {
                    //NULL kommt bei strtotime schonmal vor
                    $received_ts = time();
                }
            } catch (\Exception $e) {
                //mässig sinnvoller default...
                $received = time();
            }

            //            file_put_contents('/tmp/mail.obj', serialize($mail));
            //            exit();
            //check first, if this mail is already in DB
            $found = $this->objDatabase->prepare("SELECT * FROM tl_email WHERE message_id=?")
                ->limit(1)
                ->execute($message_id);

            //checke Mailgrösse; wenn zu gross, wird ignoriert, da sonst Speicherprobleme
            if ($mail->getSize() > 1024 * 1024 * 16) {
                log_message('kann Mail nicht verarbeiten, da zu groß. Limit = 16MB, Mail hat ' . $mail->getSize());
                continue;
            }

            if ($found && $found->numRows == 1) {
                // lösche sie vom server..
                if ($this->objMailbox) {
                    $arrDbMail = $found->fetchAllAssoc();
                    $arrDbMail = $arrDbMail[0];
                    if ($arrDbMail->tstamp < strtotime('-1 year')) {
                        $this->objMailbox->removeMessage($intMailboxMailId);
                    }
                }
                continue;
            }

            # hole htmlbody, textbody, encoding, content-type
            $body_html = '';
            $body_text = '';
            $charset = '';

            if ($mail->isMultipart()) {
                $found_html = FALSE;
                $found_text = FALSE;
                $body_html = '';
                $body_text = '';
                //                log_message('multipart', 'body.log');
                try {
                    foreach ($mail as $part) {
                        if ($found_html && $found_text) {
                            break; //hab schon alles gefunden
                        }

                        //wenn multipart, dann den ersten body, der gefunden wird
                        while ($part->isMultipart()) {
                            $part = $part->getPart(1);
                        }

                        try {
                            $encoding = $part->getHeaderField('Content-Transfer-Encoding'); //   7/8bit/quoted-printable/base64 
                        } catch (\Exception $e) {
                            $encoding = "quoted-printable"; //default
                        }
                        try {
                            $content_type = $part->getHeaderField('Content-Type');
                            $charset = $part->getHeaderField('Content-Type', 'charset');
                        } catch (\Exception $e) {
                            //default
                            $content_type = 'text/plain';
                            $charset = '';
                        }

                        $strContent = $part->__toString();

                        switch (strtolower($encoding)) {
                            case 'quoted-printable':
                                $strContent = quoted_printable_decode($strContent);
                                break;
                            case 'base64':
                                $strContent = base64_decode($strContent);
                                break;
                        }
                        if ($charset == 'ISO-8859-1') {
                            $strContent = utf8_encode($strContent);
                        }

                        if ($content_type == 'text/plain') {
                            $body_text = $strContent; //$part->__toString();
                            $found_text = TRUE;
                        } elseif ($content_type == 'text/html') {
                            $body_html = $strContent; //$part->__toString();
                            $found_html = TRUE;
                        }
                    }
                } catch (\Exception $e) {
                    //habe eine mail gefunden, die keinen body hatte...
                    //somit schmeisst getPart() eine \Exception 'partNotFound'
                    //will sie aber trotzdem verarbeiten
                    $body_text = 'keinen Inhalt in der Mail gefunden.';
                    $content_type = "text/plain";
                    $encoding = "quoted-printable";
                }
            } else {
                //nicht multipart
                try {
                    $body_text = $mail->getContent();

                    //                    log_message('kein multipart, $mail->getContent() = ' . $body, 'body.log');
                } catch (\Exception $e) {
                    log_message("Mail ohne body gefunden :-(\n" . $e);
                    $body_text = '';
                }
                try {
                    $encoding = $mail->getHeaderField('Content-Transfer-Encoding');
                    $charset = $mail->getHeaderField('Content-Type', 'charset');
                } catch (\Exception $e) {
                    $encoding = "quoted-printable"; //default
                }
                try {
                    $content_type = $mail->getHeaderField('Content-Type');
                } catch (\Exception $e) {
                    //default
                    $content_type = 'text/plain';
                }
            }


            //encode das subject, weil imap_mime_header_decode sonst bei umlauten aufhört
            $subject = utf8_encode($subject);
            //encode subject if neccessary
            $subject_array = imap_mime_header_decode($subject);
            $subject = '';
            foreach ($subject_array as $part) {
                $subject .= $part->text;
            }
            //wozu eigentlich das?
            $subject = utf8_decode_entities($subject);

            //insert into db
            //            $this->Database->prepare("INSERT INTO tl_email (message_id, from_address, to_address, received_ts, subject, body_text, body_html, direction) VALUES (?, ?, ?, ?, ?, ?, ?, ?)")
            //                ->execute(mysql_real_escape_string($message_id), mysql_real_escape_string($from), mysql_real_escape_string($to), mysql_real_escape_string($received_ts), mysql_real_escape_string($subject), mysql_real_escape_string(utf8_encode($body_text)), mysql_real_escape_string(utf8_encode($body_html)), 'I');


            $email = new EmailModel();
            $email->setRow(array(
                'message_id' => $message_id,
                'from_address' => $from,
                'tstamp' => time(),
                'to_address' => $to,
                'received_ts' => $received_ts,
                'subject' => $subject,
                'body_text' => $body_text,
                'body_html' => utf8_encode($body_html),
                'direction' => 'I'
            ));
            // echo "<head><meta charset=\"utf-8\"></head><pre>$body_html \n $body_text";
            // exit();
            $ident = $GLOBALS['TL_CONFIG']['email_template_booking_ident'];
            //            $email->guessBookingId($mail->__toString(), $ident);
            $email->guessBookingId($email->body_text . $email->body_html, $ident);
            $email->save();

            //setze bei Bedarf das 'new_mail' - flag der buchung
            if ($email->booking_id) {
                $objBooking = BookingModel::findByPk($email->booking_id);
                if ($objBooking) {
                    $objBooking->new_email = TRUE;
                    $objBooking->save();
                }
            }

            unset($mail);
            unset($subject);
            unset($body_text);
            unset($body_html);
            unset($encoding);
            unset($content_type);
            unset($strContent);
            log_message("Speichernutzung nach speichern in DB = " . memory_get_usage(TRUE), 'memory.log');
        }
    }

    /**
     * Diese Methode liest Emails von der Mailbox.
     * Bei Bedarf werden diese aus der Inbox gelöscht und/oder in einen Ordner kopiert (um Sicherheit zu haben, mal im Postfach nachschauen zu können)
     * 
     * @param array $options
     * $options['remove'] == TRUE/FALSE -> TRUE = default
     * $options['copyTo'] = string (IMAP - Ordername, wohin die Mail kopiert werden soll)
     * @return array - index ist die message-id
     */
    protected function readMailbox($options = null)
    {
        $defaults = array(
            'remove' => TRUE,
            'copyTo' => NULL
        );

        if ($options == NULL) {
            $options = $defaults;
        } else {
            $options = array_merge($defaults, $options);
        }

        $ret = array();

        try {
            $mailbox = new Imap($this->arrConfig['imap']);
            $this->objMailbox = $mailbox;
            // $mailbox = new Zend_Mail_Storage_Imap($this->config['imap']);

            foreach ($mailbox as $id => $message) {
                $ret[$id] = $message;
                if (($options['copyTo'] != NULL) && ($options['remove'] == TRUE)) {
                    $mailbox->moveMessage($id, $options['copyTo']);
                } else {
                    //jede option einzeln abtesten
                    if ($options['copyTo'] != NULL) {
                        $mailbox->copyMessage($id, $options['copyTo']);
                    }
                    if ($options['remove'] == TRUE) {
                        $mailbox->removeMessage($id);
                    }
                }
            }
        } catch (\Exception $e) {
            //@todo: mail an mich verschicken
            log_message("Fehler während des Abfragens des Email-Accounts: " . $e, 'mail.log');
        }
        return $ret;
    }

    // public function getFromAddress() {
    //     return $this->arrConfig['imap']['user'];
    // }
}
