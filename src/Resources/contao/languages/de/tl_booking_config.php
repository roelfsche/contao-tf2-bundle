<?php
if (!defined('TL_ROOT'))
    die('You cannot access this file directly!');

/**
 * Contao Open Source CMS
 * Copyright (C) 2005-2011 Leo Feyer
 *
 * Formerly known as TYPOlight Open Source CMS.
 *
 * This program is free software: you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation, either
 * version 3 of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details.
 * 
 * You should have received a copy of the GNU Lesser General Public
 * License along with this program. If not, please visit the Free
 * Software Foundation website at <http://www.gnu.org/licenses/>.
 *
 * PHP version 5
 * @copyright  Rolf Staege 2011 
 * @author     Rolf Staege 
 * @package    lumturo.booking 
 * @license    GPL 
 * @filesource
 */

/**
 * Fields
 */
$GLOBALS['TL_LANG']['tl_booking_config'] = array(
    'price_legend' => 'Standardpreise', 
    'default_price' => array(
        'Standardpreis pro Nacht', 
        'Preis, der außerhalb aller definierten Saisons gilt'
    ), 
    'default_cleaning_fee' => array(
        'Mehrtagesrabatt', 
        'Dieser Wert wird auf den ersten Tag draufgeschlagen.'
//        'Standardendreinigung', 
//        'Endreinigung, der außerhalb aller definierten Saisons gilt'
    ), 
    'default_money_interval' => array(
        'Anzahl Tage, innerhalb derer bar gezahlt werden muss', 
        'Wenn Buchungsdatum und Anreisedatum weniger Tage auseinanderliegen, muss bar bezahlt werden'
    ), 
    
    'email_legend' => 'Emaileinstellungen', 
    'email_server' => array(
        'Emailserver', 
        'Name des IMAP-Email-Servers'
    ), 
    'email_account' => array(
        'Account', 
        'Der Accountname'
    ), 
    'email_password' => array(
        'Passwort'
    ), 
    'email_ssl' => array(
        'SSL verwenden ', 
        '(Port 994)'
    ), 
    'email_copy_path' => array(
        'Ordnername', 
        'Name des Emailordners, in dem die Emails verschoben werden, wenn sie abgerufen wurden. Wenn leer, dann wird keine Sicherheitskopie angelegt.'
    ), 
    'template_legend' => 'Emailtexte',
    'email_booking_template_for_invoice' => array('Buchungsemailtext wenn Rechnung', 'Emailtext, der dem Bucher nach der Buchung zugesendet wird'),
    'email_booking_template_for_money' => array('Buchungsemailtext wenn bar zu zahlen', 'Emailtext, der dem Bucher nach der Buchung per Email zugesendet wird'),
    'email_booking_template_transaction_received' => array('Buchungsemailtext wenn Überweisung erhalten', 'Emailtext, der versendet werden kann, wenn der Rechnungsbetrag überwiesen wurde'),
    'email_booking_template_reply' => array('Emailtext für eine Antwort', ''),
    'email_booking_template_remind' => array('Emailtext für eine Zahlungserinnerung', ''),
    'email_booking_template_footer' => array('Emailtext für die Email-Signatur', 'Hier können Adresse usw. stehen. Auf jeden Fall sollte hier das Buchungs-Id-Template drin stehen!'),
    'email_template_booking_ident' => array('Eindeutiges Kürzel für die Buchungsnummer innherlab der Email', 'Dieses Kürzel wird durch das System mit der Buchungs-Id gefüllt und in die Email-Signatur eingefügt. Sie sollte ein eindeutiges Muster enthalten, so dass das System eintreffende Mails automatisch der richtigen Buchung zuordnen kann. Ein Beispiel wäre: [BUCH-NR-%%booking_id%%].<br/>%%booking_id%% wird durch die eigentliche Buchungsnummer ersetzt. WICHTIG: DAS KÜRZEL DARF KEINE ZIFFERN ENTHALTEN!!'),
    
    'html_template_legend' => 'Html-Vorlagen',
    'thank_you_template' => array('Vorlage für die Anzeige nach der Buchung', 'Dieser Text wird angezeigt, nachdem der Kunde eine Buchung abgeschickt hat.'),
    
    'edit' => 'Konfigurationseinstellungen'
);

?>