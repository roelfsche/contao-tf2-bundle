<?php
namespace Lumturo\ContaoTF2Bundle;

/**
 * (c) 2010 - 2011 rolf.staege@lumturo.net
 *
 * @copyright   Copyright (c) 2011 rolf.staege@lumturo.net
 * @version      $Id$
 */
class TF2Invoice extends Invoice
{

    public function __construct($orientation = 'P', $unit = 'pt', $format = 'A4', $unicode = true, $encoding = 'UTF-8', $diskcache = false)
    {
        parent::__construct($orientation, $unit, $format, $unicode, $encoding, $diskcache);
        

        // set document information
        $this->SetCreator(PDF_CREATOR);
        $this->SetAuthor('http://www.turm-fuer-zwei.de');
        //        $this->SetTitle('Wiro-Exposé zum Objekt ' . $kontierung);
        $this->SetSubject('Rechnung');
        $this->SetKeywords('turm-fuer-zwei, Rechnung');
        
        // remove default header/footer
        $this->setPrintHeader(false);
        $this->setPrintFooter(false);
        
        //set margins
        $this->SetMargins($this->margin_left, $this->margin_top, $this->margin_right);
        
        //set auto page breaks
        $this->SetAutoPageBreak(TRUE, 0);
        
        //Zeilenhöhe für Multicell (wird von HTML-Cell usw. verwendet)
        //        $this->setCellHeightRatio(1.3);
        

        //set image scale factor
        $this->setImageScale(PDF_IMAGE_SCALE_RATIO);
    }

    function printAddress()
    {
        $y = 127;
        $this->setY($y);
        
        $strFontPath = TL_ROOT . '/vendor/lumturo/contao-tf2-bundle/src/Resources/contao/font/opensans';
        $this->setFont('OpenSans', '', 7.5, $strFontPath);
        $this->Write(0, 'Falko Weise-Schmidt · Kastanienstraße 18 · 18292 Ahrenshagen' . "\n");
        
        $this->setY($this->getY() + 15);
        $this->setFont('helvetica', '', 11);
        $this->Write(0, $this->booking->firstname . ' ' . $this->booking->name . "\n");
        $this->Write(0, $this->booking->address . "\n");
        $this->Write(0, $this->booking->zip . ' ' . $this->booking->city . "\n");
        
        //Rechnungsnummer und -datum
        $x1 = 305;
        $x2 = 400;
        $this->setY($this->getY() + 40);
        $this->setX($x1);
        $this->Write(0, 'Rechnungsnummer:');
        $this->setX($x2);
        $this->Write(0, $this->document->id . "\n", '', false, 'R');
        $this->setX($x1);
        $this->Write(0, 'Rechnungsdatum:');
        $this->setX($x2);
        $this->Write(0, date('d.m.Y', $this->document->tstamp) . "\n", '', false, 'R');
    }

    function printBody()
    {
        $this->setY($this->getY() + 20);
        $this->setFont('helvetica', 'b', 13);
        $this->SetTextColor(94, 85, 65);
        $this->Write(0, "RECHNUNG\n");
        
        $this->setY($this->getY() + 20);
        $this->SetTextColor(0, 0, 0);
        $this->setFont('helvetica', '', 11);
        $this->Write(0, 'Sehr ' . (($this->booking->salutation == 'M') ? 'geehrter Herr ' : 'geehrte Frau ') . $this->booking->name . ",\n\n");
        $this->Write(0, 'für den von Ihnen gebuchten Zeitraum,' . "\n\n");
        $this->Write(0, 'vom: ');
        $this->setFont('helvetica', 'b', 11);
        $this->Write(0, date('d.m.Y', $this->booking->booking_from));
        $this->setFont('helvetica', '', 11);
        $this->Write(0, ' bis ');
        $this->setFont('helvetica', 'b', 11);
        $this->Write(0, date('d.m.Y', $this->booking->booking_to));
        $this->setFont('helvetica', '', 11);
        $this->Write(0, ",\n\n");
        $this->Write(0, 'stellen wir Ihnen vorab eine Übernachtung zum Preis von ');
        $this->setFont('helvetica', 'b', 11);
        
        
//        $this->Write(0, money_format($GLOBALS['TL_LANG']['MSC']['money_format'], $this->booking->price + $this->booking->cleaning_fee) . ' EUR');
// Moneyformat ist nur in de/ definiert
// @todo: entweder auch in lang en oder anders zentralisieren        
        $this->Write(0, money_format('%!n', $this->booking->price + $this->booking->cleaning_fee) . ' EUR');
        $this->setFont('helvetica', '', 11);
        $this->Write(0, "\n(inklusive 5% Mwst = " . number_format((($this->booking->price + $this->booking->cleaning_fee) - ($this->booking->price + $this->booking->cleaning_fee) / 1.05), 2) . ") EUR in Rechnung.\n\n");
        
        $this->setFont('helvetica', '', 9);
        $ratio = $this->getCellHeightRatio();
        $this->setCellHeightRatio(1.5);
//        $this->Write(0, '· Bitte überweisen Sie den Rechnungsbetrag von ' . money_format($GLOBALS['TL_LANG']['MSC']['money_format'], $this->booking->price + $this->booking->cleaning_fee) . " EUR innerhalb von 7 Tagen unter Angabe der\n");
        $this->Write(0, '· Bitte überweisen Sie den Rechnungsbetrag von ' . money_format('%!n', $this->booking->price + $this->booking->cleaning_fee) . " EUR innerhalb von 7 Tagen unter Angabe der\n");
        $this->setX($this->margin_left + 5);
        $this->Write(0, "Rechnungsnummer auf folgendes Konto:\n");
        $this->setX($this->margin_left + 5);
//        $this->Write(0, "Kontoinhaber: Falko Weise-Schmidt, Konto-Nr.: 100 586 29, BLZ: 140 613 08, VR-Bank Güstrow\n");
        $this->Write(0, "Kontoinhaber: Falko Weise-Schmidt, IBAN: DE62 1406 1308 0010 0586 29, BIC: GENODEF1GUE, VR-Bank\n");
        $this->setX($this->margin_left + 5);
        $this->Write(0, "Güstrow\n");

        $this->Write(0, '· Bis zum ' . date('d.m.Y', strtotime('+7days', $this->document->tstamp)) . " reservieren wir den von Ihnen gewünschten Zeitraum.\n");
        $this->Write(0, "· Nach Zahlungseingang erhalten Sie per E-Mail eine Bestätigung, sowie weitere Informationen zur Anreise und\n");
        $this->setX($this->margin_left + 5);
        $this->Write(0, "Turmübergabe.\n\n");
        $this->setCellHeightRatio($ratio);
        
        $this->setFont('helvetica', '', 11);
        $this->Write(0, 'Wir freuen uns, Sie als Gäste zu begrüßen und wünschen Ihnen eine schöne Zeit im Turm für Zwei.' . "\n\n");
        $this->Write(0, "Vielen Dank für Ihre Buchung\n\n\n\n");
        $this->Write(0, 'Falko Weise-Schmidt');
    }

}
