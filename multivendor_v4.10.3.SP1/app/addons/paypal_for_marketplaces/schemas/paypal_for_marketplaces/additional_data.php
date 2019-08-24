<?php
/***************************************************************************
 *                                                                          *
 *   (c) 2004 Vladimir V. Kalynyak, Alexey V. Vinokurov, Ilya M. Shalnev    *
 *                                                                          *
 * This  is  commercial  software,  only  users  who have purchased a valid *
 * license  and  accept  to the terms of the  License Agreement can install *
 * and use this program.                                                    *
 *                                                                          *
 ****************************************************************************
 * PLEASE READ THE FULL TEXT  OF THE SOFTWARE  LICENSE   AGREEMENT  IN  THE *
 * "copyright.txt" FILE PROVIDED WITH THIS DISTRIBUTION PACKAGE.            *
 ****************************************************************************/

defined('BOOTSTRAP') or die('Access denied');

$schema = array(
    'sender_email'                  => 'email',
    'sender_first_name'             => 'b_firstname',
    'sender_last_name'              => 'b_lastname',
    'sender_phone'                  => 'b_phone',
    'sender_country_code'           => 'b_country',
    'sender_address_line1'          => 'b_address',
    'sender_address_line2'          => 'b_address_2',
    'sender_address_city'           => 'b_city',
    'sender_address_state'          => 'b_state',
    'sender_address_zip'            => 'b_zipcode',
    'sender_signup_ip'              => 'ip_address',
    'receiver_address_country_code' => array('product_groups', 0, 'package_info', 'origination', 'country'),
    'receiver_address_line1'        => array('product_groups', 0, 'package_info', 'origination', 'address'),
    'receiver_address_city'         => array('product_groups', 0, 'package_info', 'origination', 'city'),
    'receiver_address_state'        => array('product_groups', 0, 'package_info', 'origination', 'state'),
    'receiver_address_zip'          => array('product_groups', 0, 'package_info', 'origination', 'zipcode'),
);

return $schema;