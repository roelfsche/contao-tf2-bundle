<?php

namespace Lumturo\ContaoTF2Bundle\Controller;

use Lumturo\ContaoTF2Bundle\Model\BookingModel;
use Lumturo\ContaoTF2Bundle\Model\DocumentModel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class AnalysisController extends LumturoController
{
    public function bookingplanAction($from, $to)
    {
        // check Login
        if (($ret = $this->checkLogin()) instanceof Response) {
            return $ret;
        }

        $arrBookings = BookingModel::getBookingsByInterval($from, $to);
        $spreadsheet = new Spreadsheet();
        /* @var $sheet \PhpOffice\PhpSpreadsheet\Writer\Xlsx\Worksheet */
        $sheet = $spreadsheet->getActiveSheet();
        // $sheet->setCellValue('A1', 'Hello World !');
        $sheet->setTitle("Belegungsplan");

        $arrHeadlines = ['A' => 'Anreise', 'B' => 'Abreise', 'C' => 'Anzahl Nächte', 'D' => 'Name Gast', 'E' => 'Telefon', 'F' => 'Frühstück', 'G' => 'Bemerkung'];
        foreach ($arrHeadlines as $strColumn => $strLabel) {
            $sheet->setCellValue($strColumn . '1', $strLabel);
        }

        if ($arrBookings) {
            $intIndex = 1;
            foreach ($arrBookings as $objBooking) {
                ++$intIndex;
                $sheet->setCellValue('A' . $intIndex, date('d.m.Y', $objBooking->booking_from));
                $sheet->setCellValue('B' . $intIndex, date('d.m.Y', $objBooking->booking_to));
                $sheet->setCellValue('C' . $intIndex, round(($objBooking->booking_to - $objBooking->booking_from) / 86400));
                $sheet->setCellValue('D' . $intIndex, $objBooking->name . ', ' . $objBooking->firstname);
                $sheet->setCellValue('E' . $intIndex, $objBooking->telephone);
            }}

        // Create your Office 2007 Excel (XLSX Format)
        $writer = new Xlsx($spreadsheet);

        // Create a Temporary file in the system
        $fileName = 'Belegungsliste_' . date('d.m.Y', $from) . '-' . date('d.m.Y', $to) . '.xlsx';
        $temp_file = tempnam(sys_get_temp_dir(), $fileName);

        // Create the excel file in the tmp directory of the system
        $writer->save($temp_file);

        // Return the excel file as an attachment
        return $this->file($temp_file, $fileName, ResponseHeaderBag::DISPOSITION_INLINE);
    }

    public function invoiceAction($from, $to)
    {
        // check Login
        if (($ret = $this->checkLogin()) instanceof Response) {
            return $ret;
        }

        $arrDocuments = DocumentModel::findBy(['tstamp>?', 'tstamp<?'], [$from, $to]);
        // $arrBookings = BookingModel::getBookingsByInterval($from, $to);
        $spreadsheet = new Spreadsheet();
        /* @var $sheet \PhpOffice\PhpSpreadsheet\Writer\Xlsx\Worksheet */
        $sheet = $spreadsheet->getActiveSheet();
        // $sheet->setCellValue('A1', 'Hello World !');
        $sheet->setTitle("Belegungsplan");

        $arrHeadlines = ['A' => 'Rech.-Nr.', 'B' => 'Typ', 'C' => 'Nachname', 'D' => 'Anreise', 'E' => 'Betrag', 'F' => 'Rechn.-Datum'];
        foreach ($arrHeadlines as $strColumn => $strLabel) {
            $sheet->setCellValue($strColumn . '1', $strLabel);
        }

        if ($arrDocuments) {
            $intIndex = 1;
            foreach ($arrDocuments as $objDocument) {
                ++$intIndex;
                // nicht opti 
                $objBooking = BookingModel::findByPk($objDocument->pid);
                $sheet->setCellValue('A' . $intIndex, $objDocument->id);
                $sheet->setCellValue('B' . $intIndex, (($objDocument->type=='INVOICE')?'Rechnung':'Gutschein'));
                $sheet->setCellValue('C' . $intIndex, $objBooking->name);
                $sheet->setCellValue('D' . $intIndex, (($objDocument->type=='INVOICE')?date('d.m.Y', $objBooking->booking_from):''));
                $sheet->setCellValue('E' . $intIndex, number_format((float)($objDocument->price + $objDocument->cleaning_fee), 2, ',', '.'));
                $sheet->setCellValue('F' . $intIndex, date('d.m.Y', $objDocument->tstamp));
            }}

        // Create your Office 2007 Excel (XLSX Format)
        $writer = new Xlsx($spreadsheet);

        // Create a Temporary file in the system
        $fileName = 'Rechnungsliste_' . date('d.m.Y', $from) . '-' . date('d.m.Y', $to) . '.xlsx';
        $temp_file = tempnam(sys_get_temp_dir(), $fileName);

        // Create the excel file in the tmp directory of the system
        $writer->save($temp_file);

        // Return the excel file as an attachment
        return $this->file($temp_file, $fileName, ResponseHeaderBag::DISPOSITION_INLINE);
    }
}
