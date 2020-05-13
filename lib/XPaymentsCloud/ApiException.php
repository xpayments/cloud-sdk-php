<?php
// vim: set ts=4 sw=4 sts=4 et:

/**
 * Copyright (c) 2011-present Qualiteam software Ltd. All rights reserved.
 * See https://www.x-cart.com/license-agreement.html for license details.
 */

namespace XPaymentsCloud;

class ApiException extends \Exception
{
    protected $publicMessage = null;

    /**
     * Returns error message that can be displayed to customer
     *
     * @return string
     */
    public function getPublicMessage()
    {
        return $this->publicMessage;
    }

    /**
     * Set error message that can be displayed to customer
     *
     * @param $message
     *
     * @return void
     */
    public function setPublicMessage($message)
    {
        $this->publicMessage = $message;
    }
}