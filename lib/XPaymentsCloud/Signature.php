<?php
// vim: set ts=4 sw=4 sts=4 et:

/**
 * Copyright (c) 2011-present Qualiteam software Ltd. All rights reserved.
 * See https://www.x-cart.com/license-agreement.html for license details.
 */

namespace XPaymentsCloud;
 
class Signature
{
    const HEADER = 'X-Payments-Signature';

    /**
     * @param string $action Request action
     * @param string $jsonFields Raw JSON data
     * @param string $secretKey Secret Key from X-Payments
     *
     * @return string
     */
    public static function get($action, $jsonFields, $secretKey) {
        return hash_hmac('sha256', $action . $jsonFields, $secretKey);
    }

}
