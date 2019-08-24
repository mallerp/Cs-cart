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

use Tygh\Enum\Addons\PaypalForMarketplaces\CaptureStatuses;

$schema = array(
    CaptureStatuses::CAPTURED      => 'P',
    CaptureStatuses::PENDING       => 'O',
    CaptureStatuses::NOT_PROCESSED => 'O',
    CaptureStatuses::VOIDED        => STATUS_CANCELED_ORDER,
    CaptureStatuses::AUTHORIZED    => 'O',
);

return $schema;