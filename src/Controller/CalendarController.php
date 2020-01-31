<?php

namespace Lumturo\ContaoTF2Bundle\Controller;

use Lumturo\ContaoTF2Bundle\Model\BookingModel;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;;

class CalendarController extends LumturoController
{
    public function listAction($year)
    {
        $arrItems = [];
        $this->container->get('contao.framework')->initialize();

        // default, wenn weggelassen: -1
        if ($year == -1) {
            $year = 'Y';
        }
        // $arrData = BookingModel::getBookingIntercectWithInterval(time(), strtotime('+2 weeks'));
        $intFromTs = strtotime(date("$year-01-01 00:00:00"));
        $intToTs = strtotime(date("$year-12-31 23:59:59"));
        $objCollection = BookingModel::getBookingsByInterval($intFromTs, $intToTs);
        if ($objCollection) {
            foreach ($objCollection as $objBooking) {
                $arrItems[] = [
                    'id' => $objBooking->id,
                    'startDate' => date('c', $objBooking->booking_from - 12 * 60 * 60),
                    'endDate' => date('c', $objBooking->booking_to - 12 * 60 * 60 + 1),
                    'create_ts' => date('c', $objBooking->create_ts),
                    'name' => $objBooking->getFullname(), //firstname . ' ' . $objBooking->name,
                    'details' => $objBooking->getCalendarDetailString(),
                    'status' => $objBooking->booking_status,
                    'type' => $objBooking->booking_type,

                ];
            }
        }
        $objResponse = new JsonResponse([
            'status' => 'ok',
            'items' => $arrItems
        ]);
        return $objResponse;
    }
}
