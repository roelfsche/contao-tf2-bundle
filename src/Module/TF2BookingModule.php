<?php

namespace Lumturo\ContaoTF2Bundle\Module;

class TF2BookingModule extends \Module
{
    /**
     * @var string
     */
    protected $strTemplate = 'mod_tf2_bookingform';

    /**
     * Displays a wildcard in the back end.
     *
     * @return string
     */
    public function generate()
    {
        if (TL_MODE == 'BE') {
            $template = new \BackendTemplate('be_wildcard');

            $template->wildcard = '### ' . utf8_strtoupper($GLOBALS['TL_LANG']['FMD']['tf2bookingmodule'][0]) . ' ###';
            $template->title = $this->headline;
            $template->id = $this->id;
            $template->link = $this->name;
            $template->href = 'contao/main.php?do=themes&amp;table=tl_module&amp;act=edit&amp;id=' . $this->id;

            return $template->parse();
        }

        return parent::generate();
    }

    /**
     * Generates the module.
     */
    protected function compile()
    {
        // die Daten für den Kalender kommen schon aus dem Tf2CalendarModule.php

        // $objBookingCollection = BookingModel::getBookingIntercectWithInterval(mktime(0, 0, 0, date('m', $now), date('d', $now), date('Y', $now)), mktime(0, 0, 0, 12, 31, 2037));

        // $date_arr = array(); //wird ein json-array => kann jQuery.inArray() verwenden
        // $css_arr = array(); //wird ein json-objekt
        // foreach ($objBookingCollection as $booking) {
        //     $booking_arr = $booking->getDateForFrontendCalendar();
        //     $date_arr = array_merge($date_arr, array_keys($booking_arr));
        //     $css_arr += $booking_arr;
        // }

        // $this->Template->dates = json_encode($date_arr);
        // $this->Template->dates_css = json_encode($css_arr);
    }
}
