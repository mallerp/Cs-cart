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

namespace Tygh\Enum\Addons\PaypalForMarketplaces;

use ReflectionClass;

class OrderStatuses
{
    const CREATED = 'CREATED';
    const APPROVED = 'APPROVED';
    const COMPLETED = 'COMPLETED';
    const FAILED = 'FAILED';

    protected static $all;

    public function getAll()
    {
        if (static::$all === null) {
            $reflector = new ReflectionClass(__CLASS__);
            $all = $reflector->getConstants();
            static::$all = fn_array_combine($all, $all);
        }

        return static::$all;
    }

}