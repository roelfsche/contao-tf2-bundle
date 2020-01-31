<?php
$GLOBALS['FE_MOD']['tf2']['tf2calendarmodule'] = 'Lumturo\ContaoTF2Bundle\Module\TF2CalendarModule';
$GLOBALS['FE_MOD']['tf2']['tf2bookingmodule'] = 'Lumturo\ContaoTF2Bundle\Module\TF2BookingModule';


$GLOBALS['TL_MODELS']['tl_booking'] = '\Lumturo\ContaoTF2Bundle\Model\BookingModel';
$GLOBALS['TL_MODELS']['tl_email'] = '\Lumturo\ContaoTF2Bundle\Model\EmailModel';
$GLOBALS['TL_MODELS']['tl_document'] = '\Lumturo\ContaoTF2Bundle\Model\DocumentModel';
$GLOBALS['TL_MODELS']['tl_season'] = '\Lumturo\ContaoTF2Bundle\Model\SeasonModel';


array_insert($GLOBALS['BE_MOD'], 0, array(
    // 'tl_booking' => array(
    //     'tables' => array(
    //         'tl_booking'
    //     ),
    //     'icon' => '/system/themes/default/images/tablewizard.gif'
    // ),
    'tf2booking' => array(
        'tl_season' => array(
            'tables' => array(
                'tl_season'
            )
        ),
        'tl_booking_config' => array(
            'tables' => array(
                'tl_booking_config'
            )
        )
    )
));
