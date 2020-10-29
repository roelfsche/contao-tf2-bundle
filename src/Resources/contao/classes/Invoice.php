<?php

namespace Lumturo\ContaoTF2Bundle;

/**
 * (c) 2010 - 2011 rolf.staege@lumturo.net
 *
 * @copyright   Copyright (c) 2011 rolf.staege@lumturo.net
 * @version      $Id$
 */
class Invoice extends \TCPDF
{

    /**
     * 
     * 
     * @var BookingModel
     */
    protected $booking = NULL;

    /**
     * muss ein gespeichertes Objekt sein, um die id und das REchnungsdatum auszulesen
     * @var DocumentModel
     */
    protected $document = NULL;

    //alles in pt für 72dpi
    public $margin_left = 70;

    public $margin_top = 36;

    public $margin_right = 70;

    public $default_font_size = 10;

    public function setBooking($booking)
    {
        $this->booking = $booking;
    }

    /**
     * Diese Methode erstellt die PDF-Rechnung. (Template-Pattern)
     * Sie legt eine neue Seite an und druckt die Daten auf.
     * Dadurch kann sie auch mehrmals aufgerufen werden, um bspw. alle Rechnungen eines Jahres
     * zu erstellen. 
     * 
     * @throws Exception - wenn kein Buchungsdatensatz vorhanden
     */
    public function create()
    {
        if ($this->booking == NULL) {
            throw new \Exception('Rechnungserstellung ohne Buchungsdatensatz angefordert');
        }
        $this->AddPage();
        $this->printHeader();
        $this->printAddress();
        $this->printBody();
        $this->printFooter();
    }

    /* -------------------------------- Template Pattern --------------------------------
     *
     * Diese Methode überschreiben, damit die Rechnung individuell angepasst werden kann.
     */
    /**
     * Diese Methode erstellt den Header.
     * 
     */
    function printHeader()
    {
        // $file = '/header.png';
        $strImagePath = TL_ROOT . '/vendor/lumturo/contao-tf2-bundle/src/Resources/contao/images/header.png';
        $this->Image($strImagePath, $this->margin_left, $this->margin_top, 427.276);
        // $this->Image(realpath(dirname(__FILE__) . '/pdf/') . $file, $this->margin_left, $this->margin_top, 427.276);
    }

    function printAddress()
    {
    }

    function printBody()
    {
    }

    function printFooter()
    {
        $y = 778; //ganze seite hat 842x595
        $this->setY($y);
        //ACHTUNG: Musste die grossen 'I' durch die kleinen 'l' ersetzen, weil die grossen 'i' nicht angezeigt wurden.
        $text = array(
            119 => 'Falko Weise-Schmidt · Kastanienstraße 18 · 18292 Ahrenshagen · Steuer-Nr.: 086/286/01810',
            141 => 'E-Mail: kontakt@turm-fuer-zwei.de · lnternetadresse: www.turm-fuer-zwei.de',
            // I wird durch den Zeichensatz nicht dargestellt --> l (kleines L) bei BIC und IBAN
            50 => 'Volks- und Raiffeisenbank · Konto-Nr.: 100 58 629 · BLZ: 140 613 08 · BlC: GENODEF1GUE · lBAN: DE62 1406 1308 0010 0586 29'
        );
        $file = '/trenner.png';
        $this->Image(realpath(dirname(__FILE__) . '/pdf/') . $file, $this->margin_left, $y, 427.276);
        $this->setFont('OpenSans', '', 8);
        $this->SetTextColor(94, 85, 65);
        $this->setCellHeightRatio(1.4);
        $this->setY($y + 12);
        foreach ($text as $x => $val) {
            $this->setX($x);
            $this->Write(0, $val . "\n");
        }
    }

    /**
     * Schreibt eine Überschrift.
     * 
     * @param string $text - anzuzeigender Text
     * @param boolean $top_margin - wenn TRUE, wird der default-Abstand der H1-Überschrift gesetzt
     * @param boolean $enter - wenn TRUE, wird ein \n an den String angehangen (Auswirkung auf y-Koordinate)
     * @param integer $x - wenn gesetzt, wird die Überschrift um $x eingerückt (ausgehend vom Seitenrand)
     */
    protected function H1($text, $top_margin = TRUE, $enter = TRUE, $x = FALSE)
    {
        if ($x) {
            $this->setX($x);
        }

        if ($top_margin) {
            $this->setY($this->getY() + $this->h1_margin_top, FALSE); //Abstand nach oben
        }
        $this->setFont(PDF_FONT_NAME_MAIN, 'B', $this->h1_font_size);
        $this->Write(0, $text . (($enter) ? "\n" : ''), '', 0, 'L');

        //sicherheitshalber zurücksetzen
        $this->setX($this->margin_left);
    }

    protected function H2($text, $margin_top = 0)
    { {
            $this->setY($this->getY() + $margin_top, FALSE); //Abstand nach oben
        }
        $this->setFont(PDF_FONT_NAME_MAIN, 'B', $this->h2_font_size);
        $this->Write(0, $text . "\n", '', 0, 'L');
    }

    protected function H3($text, $margin_top = 12)
    {
        {
            $this->setY($this->getY() + $margin_top, FALSE); //Abstand nach oben
        }
        $this->setFont(PDF_FONT_NAME_MAIN, 'B', $this->h3_font_size);
        $this->Write(0, $text . "\n", '', 0, 'L');
        $this->setY($this->getY() + $this->h3_margin_bottom);
    }

    /**
     * Diese methode schreibt einen Absatz
     *
     * @param string $text
     * @return integer - anzahl der zeilen, die geschrieben wurden
     */
    protected function P($text)
    {
        $this->setY($this->getY() + $this->p_margin_top, FALSE); //Abstand nach oben
        $this->setFont(PDF_FONT_NAME_MAIN, '', $this->default_font_size);
        return $this->Write(0, $text . "\n", '', 0, 'L');
    }
    /**
     * @return the $document
     */
    public function getDocument()
    {
        return $this->document;
    }

    /**
     * @param DocumentModel $document
     */
    public function setDocument($document)
    {
        $this->document = $document;
    }
}
