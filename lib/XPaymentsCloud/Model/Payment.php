<?php
// vim: set ts=4 sw=4 sts=4 et:

/**
 * Copyright (c) 2011-present Qualiteam software Ltd. All rights reserved.
 * See https://www.x-cart.com/license-agreement.html for license details.
 */

namespace XPaymentsCloud\Model;

class Payment
{
    /**
     * Main information
     */
    public $xpid;
    public $parentXpid = '';
    public $status;
    public $message;
    public $amount;
    public $currency;
    public $isFraudulent;
    public $refId;
    public $initialTransactionId;
    public $description;
    public $customerId = '';

    /**
     * Amount details
     */
    public $authorized;
    public $charged;
    public $captured;
    public $voided;
    public $refunded;

    /**
     * Other complex entites
     */
    public $details;
    public $supportedTransactions;
    public $lastTransaction;
    public $card;
    public $verification;
    public $secure3d;
    public $fraudCheck;

    /**
     * Payment status codes
     */
    const INITIALIZED   = 1;
    const AUTH          = 2;
    const DECLINED      = 3;
    const CHARGED       = 4;
    const REFUNDED      = 5;
    const PART_REFUNDED = 6;

    /**
     * Transaction types
     */
    const TXN_AUTH          = 'auth';
    const TXN_SALE          = 'sale';
    const TXN_CAPTURE       = 'capture';
    const TXN_CAPTURE_PART  = 'capturePart';
    const TXN_CAPTURE_MULTI = 'captureMulti';
    const TXN_VOID          = 'void';
    const TXN_VOID_PART     = 'voidPart';
    const TXN_VOID_MULTI    = 'voidMulti';
    const TXN_REFUND        = 'refund';
    const TXN_REFUND_PART   = 'refundPart';
    const TXN_REFUND_MULTI  = 'refundMulti';
    const TXN_GET_INFO      = 'getInfo';
    const TXN_ACCEPT        = 'accept';
    const TXN_DECLINE       = 'decline';
    const TXN_TEST          = 'test';
    const TXN_GET_CARD      = 'getCard';

    public function __construct($paymentData = null)
    {
        if (!is_null($paymentData)) {
            $this->createFromResponse($paymentData);
        }
    }

    public function createFromResponse($paymentData)
    {
        // TODO: validate fields

        foreach ($paymentData as $key => $field) {
            if (is_array($field) && 'supportedTransactions' != $key) {
                $paymentData[$key] = (object)$field;
            }
            if (property_exists($this, $key)) {
                $this->{$key} = $paymentData[$key];
            }
        }

        return $this;
    }

    /**
     * @param string $txnCode
     *
     * @return bool
     */
    public function isTransactionSupported($txnCode)
    {
        return in_array($txnCode, $this->supportedTransactions);
    }

}
