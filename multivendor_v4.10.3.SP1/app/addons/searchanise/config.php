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

if (!defined('BOOTSTRAP')) { die('Access denied'); }

define('SE_STATUS_NONE', 'none');
define('SE_STATUS_QUEUED', 'queued');
define('SE_STATUS_PROCESSING', 'processing');
define('SE_STATUS_SENT', 'sent');
define('SE_STATUS_DONE', 'done');
define('SE_STATUS_SYNC_ERROR', 'sync_error');
