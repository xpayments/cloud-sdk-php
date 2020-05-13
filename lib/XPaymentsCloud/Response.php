<?php
// vim: set ts=4 sw=4 sts=4 et:

/**
 * Copyright (c) 2011-present Qualiteam software Ltd. All rights reserved.
 * See https://www.x-cart.com/license-agreement.html for license details.
 */

namespace XPaymentsCloud;

class Response
{
    private $fields;
    private $payment = null;

    /**
     * Response constructor.
     * @param string $action Request action
     * @param string $body Unparsed response body
     * @param int|null $httpCode Response HTTP code (can be null if unknown)
     * @param string $signature X-Payments-Signature value
     * @param string $secretKey Secret key from X-Payments
     *
     * @throws ApiException
     */
    public function __construct($action, $body, $httpCode, $signature, $secretKey)
    {
        // Parse and validate response
        $signatureLocal = Signature::get($action, $body, $secretKey);
        $fields = $this->convertJSONToHash($body);

        if (
            0 !== strcmp($signatureLocal, $signature)
            || empty($fields)
            || !is_array($fields)
        ) {
            throw new ApiException('Invalid response signature', 403);
        }

        if (
            !is_null($httpCode)
            && 200 !== $httpCode
        ) {
            // API error returned
            $errNo = !empty($fields['code']) ? $fields['code'] : $httpCode;
            $error = !empty($fields['error']) ? $fields['error'] : 'Request could not be completed';
            $exception = new ApiException($error, $errNo);
            if (!empty($fields['message'])) {
                $exception->setPublicMessage($fields['message']);
            }
            throw $exception;
        }

        if (!empty($fields['payment'])) {
            $this->payment = new \XPaymentsCloud\Model\Payment($fields['payment']);
            unset($fields['payment']);
        }
        $this->fields = $fields;
    }

    /**
     * Convert JSON to hash array
     *
     * @param string $json JSON string
     *
     * @return array|string
     */
    private function convertJSONToHash($json)
    {
        return json_decode($json, true);
    }

    /**
     * @param $param
     * @return mixed
     */
    public function __get($param)
    {
        return (array_key_exists($param, $this->fields)) ? $this->fields[$param] : null;
    }

    /**
     * @return Model\Payment
     */
    public function getPayment()
    {
        return $this->payment;
    }
}
