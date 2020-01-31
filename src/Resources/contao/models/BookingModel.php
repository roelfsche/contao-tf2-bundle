<?php

namespace Lumturo\ContaoTF2Bundle\Model;

use Contao\Database;
use \DateTime;

class BookingModel extends \Model
{
    const STATUS_CANCEL = 'C';

    const STATUS_BOOKED = 'B';

    const TYPE_LOCK = 'S';

    /**
     * Table name
     * @var string
     */
    protected static $strTable = 'tl_booking';

    public static function validatePostFromFrontend(&$arrPost)
    {
        $arrErrors = [];
        foreach (['booking_from', 'booking_to', 'firstname', 'name', 'email', 'address', 'zip', 'city', 'telephone'] as $strField) {
            if (!isset($arrPost[$strField])) {
                $arrErrors[$strField] = 'Bitte angeben.';
                continue;
            }

            switch ($strField) {
                case 'booking_from':
                    $arrPost['booking_from'] = strtotime($arrPost['booking_from']);
                    if (!$arrPost['booking_from']) {
                        $arrErrors[$strField] = 'Bitte korrektes Datum angeben.';
                        break;
                    }
                    $arrPost['booking_from'] = mktime(12, 0, 0, date('n', $arrPost['booking_from']), date('j', $arrPost['booking_from']), date('Y', $arrPost['booking_from']));
                    // check, ob es in einen anderen Aufenthalt reinreicht
                    $objCollection = self::getBookingIntercectWithInterval($arrPost['booking_from'], $arrPost['booking_from']);
                    if ($objCollection) {
                        $arrErrors['booking_from'] = 'Turm schon belegt'; // ragt in andere
                        break;
                    }
                    break;
                case 'booking_to':
                    $arrPost['booking_to'] = strtotime($arrPost['booking_to']);
                    if (!$arrPost['booking_to']) {
                        $arrErrors[$strField] = 'Bitte korrektes Datum angeben.';
                        break;
                    }
                    // normiere auf 11:59:59 --> Unix-TS
                    $arrPost['booking_to'] = mktime(11, 59, 59, date('n', $arrPost['booking_to']), date('j', $arrPost['booking_to']), date('Y', $arrPost['booking_to']));
                    // check, ob es in einen anderen Aufenthalt reinreicht
                    $objCollection = self::getBookingIntercectWithInterval($arrPost['booking_to'], $arrPost['booking_to'], $arrPost['id']);
                    if ($objCollection) {
                        $arrErrors['booking_to'] = 'Turm schon belegt'; // ragt in andere
                        break;
                    }
                    break;
                case 'email':
                    if (!filter_var($arrPost[$strField], FILTER_VALIDATE_EMAIL)) {
                        $arrErrors[$strField] = 'Bitte valide E-Mail angeben.';
                    }
                    break;
                default:
                    if (!strlen($arrPost[$strField])) {
                        $arrErrors[$strField] = 'Bitte angeben.';
                    }
            }
        }

        if (!isset($arrErrors['booking_from']) && !isset($arrErrors['booking_To'])) {
            // check interval
            $objCollection = self::getBookingIntercectWithInterval($arrPost['booking_from'], $arrPost['booking_to']);
            if ($objCollection) {
                $arrErrors['booking_from'] = 'Turm schon belegt'; // ragt in andere
                $arrErrors['booking_to'] = 'Turm schon belegt'; // ragt in andere
            }
        }
        return $arrErrors;
    }

    public static function validatePost(&$arrPost)
    {
        $arrErrors = ['booking' => ['details' => []]];
        $arrPost['id'] = ((isset($arrPost['id'])) ? (int) $arrPost['id'] : 0);

        // booking_from
        if (!isset($arrPost['booking_from']) || !(int) $arrPost['booking_from']) {
            $arrErrors['booking']['details']['booking_from'] = 1; //fehlt, oder ragt in einen anderen Aufenthalt
        } else {
            $arrPost['booking_from'] = strtotime($arrPost['booking_from']);
            // normiere auf 12:00:00 --> Unix-TS
            $arrPost['booking_from'] = mktime(12, 0, 0, date('n', $arrPost['booking_from']), date('j', $arrPost['booking_from']), date('Y', $arrPost['booking_from']));
            // check, ob es in einen anderen Aufenthalt reinreicht
            $objCollection = self::getBookingIntercectWithInterval($arrPost['booking_from'], $arrPost['booking_from'], $arrPost['id']);
            if ($objCollection) {
                $arrErrors['booking']['details']['booking_from'] = 2; // ragt in andere
            }
        }

        // booking_to
        if (!isset($arrPost['booking_to']) || !(int) $arrPost['booking_to']) {
            $arrErrors['booking']['details']['booking_to'] = 1; //fehlt
        } else {
            $arrPost['booking_to'] = strtotime($arrPost['booking_to']);
            // normiere auf 11:59:59 --> Unix-TS
            $arrPost['booking_to'] = mktime(11, 59, 59, date('n', $arrPost['booking_to']), date('j', $arrPost['booking_to']), date('Y', $arrPost['booking_to']));
            // check, ob es in einen anderen Aufenthalt reinreicht
            $objCollection = self::getBookingIntercectWithInterval($arrPost['booking_to'], $arrPost['booking_to'], $arrPost['id']);
            if ($objCollection) {
                $arrErrors['booking']['details']['booking_to'] = 2; //
            }
        }

        // check, ob es keinen Aufenthalt zw. booking_from und booking_to gibt
        if (!count($arrErrors['booking']['details'])) {
            $objCollection = self::getBookingIntercectWithInterval($arrPost['booking_from'], $arrPost['booking_to'], $arrPost['id']);
            if ($objCollection) {
                $arrErrors['booking']['details']['booking_from'] = 2;
                $arrErrors['booking']['details']['booking_to'] = 2;
            }
        }

        return $arrErrors;
    }
    /**
     * Diese Methode liefert Buchungen zurück, die irgendwie in diesen Zeitraum hereinragen/überlappen.
     * D.h. 
     * 1.) deren Ende in dieses Intervall fällt
     * 2.) deren Anfang in dieses Intervall fällt
     * 3.) beides
     * 4.) deren Anfang kleiner und das Ende größer ist
     * 
     * @param integer $from_ts
     * @param integer $to_ts
     * @return Collection
     */
    public static function getBookingIntercectWithInterval($intFromTs, $intToTs, $intId = 0)
    {
        $arrOptions = [
            'order' => 'booking_from ASC',
            'return' => 'Collection'
        ];

        if ($intId) {
            $arrOptions['column'] = [
                'id != ?',
                '((booking_from <= ? and booking_to > ?) OR (booking_from >= ? AND booking_from < ?) OR (booking_from >= ? and booking_to <= ?))',
                'booking_status != ?'
            ];
            $arrOptions['value'] = [$intId, $intFromTs, $intFromTs, $intFromTs, $intToTs, $intFromTs, $intToTs, self::STATUS_CANCEL];
        } else {
            $arrOptions['column'] = [
                '((booking_from <= ? and booking_to > ?) OR (booking_from >= ? AND booking_from < ?) OR (booking_from >= ? and booking_to <= ?))',
                'booking_status != ?'
            ];
            $arrOptions['value'] = [$intFromTs, $intFromTs, $intFromTs, $intToTs, $intFromTs, $intToTs, self::STATUS_CANCEL];
        }
        return static::findAll($arrOptions);
    }

    /**
     * Liefert Buchungen zu einem Intervall.
     * Dazu werden die Anreisedaten mit den Intervall verglichen
     * @param integer $intFromTs
     * @param integer $intToTs
     * @return Collection
     */
    public function getBookingsByInterval($intFromTs, $intToTs)
    {
        $arrOptions = [
            'column' => [
                'booking_from >= ? AND booking_from < ? AND booking_status != ?'
            ],
            'value' => [$intFromTs, $intToTs, self::STATUS_CANCEL],
            'order' => 'booking_from ASC',
            'return' => 'Collection'
        ];
        return static::findAll($arrOptions);
    }

    public function calculatePrice($floatDefaultPrice, $floatCleaningFee)
    {
        $intSecondsPerDay = 86400;

        # initialisiere das tage-array mit dem default-wert
        //anzahl der Tage: ende_ts + 1 entspricht abreisetag 12:00:00
        //da die anreise auch technisch auf 12:00:00 terminiert ist, erhalte ich hier die Anzahl der Tage
        //ACHTUNG: runde, weil am tag der zeitumstellung (27.10.2012) auf einmal 1.024 tage rauskamen und in der schleife unten
        //dann 2x der preis addiert wurde (weil 1 < 1.024)
        $intDayCount = (int) round(($this->booking_to + 1 - $this->booking_from) / $intSecondsPerDay, 0);
        //baue ein tage-array derart auf: array($ts => $default_price)
        //$ts für den jeweiligen tag 12:00:00
        $arrDays = array();
        for ($i = 0; $i < $intDayCount; $i++) {
            $arrDays[$this->booking_from + ($i * $intSecondsPerDay)] = $floatDefaultPrice;
        }

        //selektiere alle saisons, deren anfang kleiner oder gleich dem start_ts ist und deren ende grösser als der start_ts ist
        //und derern anfang grösser als der start_ts und kleiner als der end_ts ist 
        $objSeasonsCollection = SeasonModel::findByInterval($this->booking_from, $this->booking->to);
        #gehe nun die einzelnen Tage durch und schaue, in welche Saison dieser passt; merke mir dafür den Preis und schonmal die cleaning_fee

        if ($objSeasonsCollection) {
            $objSeasonIterator = $objSeasonsCollection->getIterator();
            foreach ($arrDays as $intTs => $floatPrice) {
                foreach ($objSeasonIterator as $objSeason) {
                    if ($objSeason->season_from <= $intTs && $intTs < $objSeasonsCollection->season_to) {
                        $arrDays[$intTs] = (float) $objSeason->price;
                        $objSeasonIterator->rewind();
                        break;
                    }
                }
            }
            // foreach ($tage_arr as $ts => $value) {
            //     foreach ($seasons as $season) {
            //         if ($season->getSeasonFrom() <= $ts && $ts < $season->getSeasonTo()) {
            //             $tage_arr[$ts] = $season->getPrice();
            //             $seasons->rewind(); //wäre nicht nötig, müsste wahrsch. nur einen eintrag zurück, weil foreach dann wieder auf diesem landet (ist ja logisch, dass der nächste tag mind. auchzu dieser saison gehört)
            //             break;
            //         }
            //     }
            // }
        }

        # errechne nun den GesamtPreis
        $floatPrice = 0.00;
        foreach ($arrDays as $floatDayPrice) {
            $floatPrice += $floatDayPrice;
        }
        $this->price = $floatPrice;
        $this->cleaning_fee = $floatCleaningFee;
        // $price = 0.00;
        // foreach ($tage_arr as $tag_preis) {
        //     $price += $tag_preis;
        // }

        // $booking->setCleaningFee($cleaning_fee);
        // $booking->setPrice($price);
        // $booking->setPriceDetails($seasons);
    }

    /**
     * Diese Methode erstellt ein Array für jeden Tag, den diese Buchung belegt und füllt es mit Daten, die passend für den Kalender sind:
     * -text = 'Vorname, Nachname, von, bis'
     * -date = JSON-Date ('20110101T000000')
     * 
     * Dem Kalender muss man nämlich jeden Tag einzeln geben.
     * 
     */
    // public function getDataForCalendar()
    // {
    //     $ret = array();
    //     //kreiere zwei DateTime-Objekte und erstelle die Differenz ->  die gibt mir die Tage zurück ;-)
    //     $tmp = getdate($this->booking_from);
    //     $from = new DateTime();
    //     $from->setDate($tmp['year'], $tmp['mon'], $tmp['mday']);

    //     $tmp = getdate($this->booking_to);
    //     $to = new DateTime();
    //     $to->setDate($tmp['year'], $tmp['mon'], $tmp['mday']);

    //     $diff = $to->diff($from);

    //     //$tage = 1, wenn vom 15.11. zum 16.11, dann brauch ich einen eintrag für den 15. und einen für den 16.
    //     $tage = $diff->format('%a');

    //     # für jeden Tag wird ein Array derarte gebildet
    //     # $ts => ('date' => JSONDate, 'text' => 'Heinz Klausmann, ...', 'cls' => 'x-booking-...')
    //     # $ts zeigt immer auf den tag 00:00:00 Uhr


    //     $start_ts = $this->booking_from;

    //     //css-Klasse berechnen
    //     $cls = array();
    //     if ($this->booking_status() == 'P') {
    //         array_push($cls, 'x-booking-payed');
    //     } else {
    //         //schaue, ob schon ausserhalb der Zahlfrist
    //         //$GLOBALS['TL_CONFIG']['default_money_interval']
    //         $until_pay = strtotime('+7days', $this->create_ts);
    //         if ($until_pay < time()) {
    //             array_push($cls, 'x-booking-not-payed');
    //         } else {
    //             array_push($cls, 'x-booking-booked');
    //         }
    //     }
    //     //Sperrzeit
    //     if ($this->booking_type == 'S') {
    //         array_push($cls, 'x-booking-lock');
    //     }
    //     //neue Email?
    //     if ($this->new_email == TRUE) {
    //         array_push($cls, 'x-booking-new-email');
    //     }

    //     //label
    //     $text = $this->booking_type == self::TYPE_LOCK ? 'Sperrzeit' : $this->first_name . ' ' . $this->name;

    //     //lasse den letzten tag weg (deshalb < $tage)
    //     //weil das jetzt so ist
    //     for ($i = 0; $i < $tage; $i++) {
    //         $ts = $start_ts + $i * 60 * 60 * 24; //für den akt. tag...
    //         $ret[$ts] = array(
    //             'date' => date('Y-m-d', $ts) . 'T00:00:00',
    //             'text' => $text,
    //             'cls' => implode(' ', $cls)
    //         );
    //     }

    //     return $ret;
    // }

    /**
     * Diese Methode liefert ein Array zurück, welches pro Tag einen Eintrag enthält.
     * Der letzte Tag wird weggelassen.
     * D.h., wenn eine Buchung vom 01.01.2012 - 03.01.2012 geht, wird ein Eintrag für den 01.01. 
     * und einer für den 02.01.2012 zurück geliefert.
     * Als Index wird der unix_ts für {datum} 16:00:00 verwendet 
     *
     * @return array
     */
    public function getDateForFrontendCalendar()
    {
        $ret = array();
        //kreiere zwei DateTime-Objekte und erstelle die Differenz ->  die gibt mir die Tage zurück ;-)
        $tmp = getdate($this->booking_from);
        $from = new DateTime();
        $from->setDate($tmp['year'], $tmp['mon'], $tmp['mday']);

        $tmp = getdate($this->booking_to);
        $to = new DateTime();
        $to->setDate($tmp['year'], $tmp['mon'], $tmp['mday']);

        $diff = $to->diff($from);

        //$tage = 1, wenn vom 15.11. zum 16.11, dann brauch ich einen eintrag für den 15. und einen für den 16.
        $tage = $diff->format('%a');

        # für jeden Tag wird ein Array derarte gebildet
        # $ts => ('date' => JSONDate, 'text' => 'Heinz Klausmann, ...', 'cls' => 'x-booking-...')
        # $ts zeigt immer auf den tag 00:00:00 Uhr


        $start_ts = $this->booking_from;
        for ($i = 0; $i < $tage; $i++) {
            if (!$i) {
                //der erste geht zum anklicken für die abreise
                $arr = array(
                    'css' => 'ui-state-booked-start'
                );
            } else {
                //sonst -> mittendrin
                $arr = array(
                    'css' => 'ui-state-booked'
                );
            }
            $ts = $start_ts + $i * 60 * 60 * 24; //für den akt. tag...
            //j.n.Y = 1.1.2012 und 12.10.2012
            $ret[date('j.n.Y', $ts)] = $arr;
        }
        return $ret;
    }

    /**
     * Vorname ' ' Nachname
     * 
     * @return string
     */
    public function getFullname()
    {
        return $this->firstname . ' ' . $this->name;
    }
    /**
     * Detail-String für Kalender
     * 
     * @return string
     */
    public function getCalendarDetailString()
    {
        $strRet = date('d.m.Y', $this->booking_from) . ' - ' . date('d.m.Y', $this->booking_to);
        return $strRet;
    }

    public function getDetails()
    {
        $arrAll = $this->row();
        $arrRet = array_intersect_key($arrAll, array_flip(['id', 'salutation', 'firstname', 'name', 'address', 'city', 'zip', 'email', 'telephone', 'booking_from', 'booking_to', 'booking_status', 'booking_type', 'price', 'cleaning_fee', 'notice', 'my_notice', 'create_ts']));
        $arrRet['booking_from'] = date('c', $arrAll['booking_from']);
        $arrRet['booking_to'] = date('c', $arrAll['booking_to']);
        $arrRet['create_ts'] = date('c', $arrAll['create_ts']);
        $arrRet['price'] = number_format((int) $arrAll['price'], 2, ',', '.');
        $arrRet['cleaning_fee'] = (int) $arrAll['cleaning_fee'];
        if ($arrRet['my_notice'] == NULL) {
            $arrRet['my_notice'] = '';
        }

        return $arrRet;
    }

    public function getEmails()
    {
        return EmailModel::findByBookingId($this->id);
    }

    /**
     * Liefert die Details für die Listen-Ansicht im Booking-Tab zurück
     * @return array
     */
    public function getEmailListDetails()
    {
        $objCollection = $this->getEmails();
        if (!$objCollection) {
            return [];
        }
        $arrRet = [];
        foreach ($objCollection as $objEmail) {
            $arrRet[] = $objEmail->getListDetails();
        }
        return $arrRet;
    }

    public function getInvoices()
    {
        return DocumentModel::findByBookingId($this->id);
    }

    /**
     * Liefert die Details für die Listen-Ansicht im Booking-Tab zurück
     * @return array
     */
    public function getInvoicesListDetails()
    {
        $objCollection = $this->getInvoices();
        if (!$objCollection) {
            return [];
        }
        $arrRet = [];
        foreach ($objCollection as $objInvoice) {
            $arrRet[] = $objInvoice->getListDetails();
        }
        return $arrRet;
    }

    public function fillTemplate($template, $booking_ident = '')
    {

        # mache die timestamps "schön"
        $values = $this->row();
        $values['salutation'] = ($values['salutation'] == 'M') ? 'geehrter Herr' : 'geehrte Frau';
        $values['booking_from'] = date('d.m.Y', $values['booking_from']);
        $values['booking_to'] = date('d.m.Y', $values['booking_to']);
        $values['whole_price'] = number_format($this->price + $this->cleaning_fee, 2);

        # erstelle den eindeutigen Buchungs-Id-Terminus
        if (strlen($booking_ident)) {
            $values['booking_id'] = str_replace('%%booking_id%%', $values['id'], $booking_ident); // '[BNR-TF2-' . $values['id'] . ']';
        }

        //key-array
        $search = array_map('Lumturo\ContaoTF2Bundle\Model\BookingModel::makeTemplateKey', array_keys($values));

        return str_replace($search, $values, $template);
    }

    /**
     * Formatter für array_map
     * @param string $key
     * @return string
     */
    public static function makeTemplateKey($key)
    {
        return '%%' . $key . '%%';
    }
}
