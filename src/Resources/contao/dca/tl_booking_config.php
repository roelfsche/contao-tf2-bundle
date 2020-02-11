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
 * @copyright  lumturo Softwareentwicklung & Schulungen Rolf Staege 2011 
 * @author     Rolf Staege 
 * @package    booking 
 * @license    GNU/LGPL 
 * @filesource
 */

/**
 * Config 
 */
$GLOBALS['TL_DCA']['tl_booking_config'] = array(
    
    // Config
    'config' => array(
        'dataContainer' => 'File', 
        'closed' => true, 
        'label' => &$GLOBALS['TL_LANG']['tl_booking_config']['headline']
    ), 
    
    // Palettes
    'palettes' => array(
        'default' => '{price_legend:show},default_price,default_cleaning_fee,default_money_interval;{email_legend},email_server,email_account,email_ssl,email_password,email_copy_path;{template_legend},email_booking_template_for_invoice,email_booking_template_for_money,email_template_reply,email_booking_template_transaction_received,email_template_remind,email_template_footer,email_template_booking_ident;{html_template_legend},thank_you_template'
    ), 
    
    // Subpalettes
    'subpalettes' => array(), 
    
    // Fields
    'fields' => array(
        'default_price' => array(
            'label' => &$GLOBALS['TL_LANG']['tl_booking_config']['default_price'], 
            'inputType' => 'text', 
            'eval' => array(
                'mandatory' => true, 
                'rgxp' => 'digit'
            )
        ), 
        'default_cleaning_fee' => array(
            'label' => &$GLOBALS['TL_LANG']['tl_booking_config']['default_cleaning_fee'], 
            'inputType' => 'text', 
            'eval' => array(
                'mandatory' => true, 
                'rgxp' => 'digit'
            )
        ), 
        'default_money_interval' => array(
            'label' => &$GLOBALS['TL_LANG']['tl_booking_config']['default_money_interval'], 
            'inputType' => 'text', 
            'eval' => array(
                'mandatory' => true, 
                'regexp' => 'digit'
            )
        ), 
        'email_server' => array(
            'label' => &$GLOBALS['TL_LANG']['tl_booking_config']['email_server'], 
            'inputType' => 'text', 
            'eval' => array(
                'mandatory' => true
            )
        ), 
        'email_account' => array(
            'label' => &$GLOBALS['TL_LANG']['tl_booking_config']['email_account'], 
            'inputType' => 'text', 
            'eval' => array(
                'mandatory' => true
            )
        ), 
        'email_password' => array(
            'label' => &$GLOBALS['TL_LANG']['tl_booking_config']['email_password'], 
            'inputType' => 'password', 
            'eval' => array(
                'mandatory' => true
            )
        ), 
        'email_ssl' => array(
            'label' => &$GLOBALS['TL_LANG']['tl_booking_config']['email_ssl'], 
            'inputType' => 'checkbox', 
            'eval' => array(
                'isBoolean' => TRUE
            )
        ), 
        'email_copy_path' => array(
            'label' => &$GLOBALS['TL_LANG']['tl_booking_config']['email_copy_path'], 
            'inputType' => 'text', 
            'eval' => array(
                'mandatory' => FALSE
            )
        ), 
        'email_booking_template_for_invoice' => array(
            'label' => &$GLOBALS['TL_LANG']['tl_booking_config']['email_booking_template_for_invoice'], 
            'inputType' => 'textarea', 
            'eval' => array(
                'mandatory' => TRUE, 
                'rte' => 'tinyMCE'
            )
            
        ), 
        'email_booking_template_transaction_received' => array(
            'label' => &$GLOBALS['TL_LANG']['tl_booking_config']['email_booking_template_transaction_received'], 
            'inputType' => 'textarea', 
            'eval' => array(
                'mandatory' => TRUE, 
                'rte' => 'tinyMCE'
            )
            
        ), 
        'email_booking_template_for_money' => array(
            'label' => &$GLOBALS['TL_LANG']['tl_booking_config']['email_booking_template_for_money'], 
            'inputType' => 'textarea', 
            'eval' => array(
                'mandatory' => TRUE, 
                'rte' => 'tinyMCE'
            )
            
        ), 
        'email_template_reply' => array(
            'label' => &$GLOBALS['TL_LANG']['tl_booking_config']['email_booking_template_reply'], 
            'inputType' => 'textarea', 
            'eval' => array(
                'mandatory' => TRUE, 
                'rte' => 'tinyMCE'
            )
            
        ), 
        'email_template_remind' => array(
            'label' => &$GLOBALS['TL_LANG']['tl_booking_config']['email_booking_template_remind'], 
            'inputType' => 'textarea', 
            'eval' => array(
                'mandatory' => TRUE, 
                'rte' => 'tinyMCE'
            )
            
        ), 
        'email_template_footer' => array(
            'label' => &$GLOBALS['TL_LANG']['tl_booking_config']['email_booking_template_footer'], 
            'inputType' => 'textarea', 
            'eval' => array(
                'mandatory' => TRUE, 
                'rte' => 'tinyMCE'
            )
            
        ),
        'email_template_booking_ident' => array(
            'label' => &$GLOBALS['TL_LANG']['tl_booking_config']['email_template_booking_ident'], 
            'inputType' => 'text', 
            'eval' => array(
                'mandatory' => TRUE, 
            )
            
        ),
        'thank_you_template' => array(
            'label' => &$GLOBALS['TL_LANG']['tl_booking_config']['thank_you_template'], 
            'inputType' => 'textarea', 
            'eval' => array(
                'mandatory' => TRUE, 
                'rte' => 'tinyMCE'
            )
            
        ),
        
    )
);

?>