<?php
// vim: set ts=4 sw=4 sts=4 et:

/**
 * Copyright (c) 2011-present Qualiteam software Ltd. All rights reserved.
 * See https://www.x-cart.com/license-agreement.html for license details.
 */

namespace XPaymentsCloud;

use XPaymentsCloud\Model\Subscription;

require_once __DIR__ . DIRECTORY_SEPARATOR . 'Request.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'Response.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'ApiException.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'Signature.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'Model' . DIRECTORY_SEPARATOR . 'Payment.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'Model' . DIRECTORY_SEPARATOR . 'Subscription.php';

class Client
{
    const SDK_VERSION = '0.2.14';

    private $account;
    private $secretKey;
    private $apiKey;

    /**
     * Call "pay" action
     *
     * @param string $token
     * @param string $refId
     * @param string $customerId
     * @param array $cart
     * @param string $returnUrl
     * @param string $callbackUrl
     *
     * @param null $forceSaveCard (optional)
     * @param null $forceTransactionType (optional)
     * @param int $forceConfId (optional)
     *
     * @return Response
     * @throws ApiException
     */
    public function doPay($token, $refId, $customerId, $cart, $returnUrl, $callbackUrl, $forceSaveCard = null, $forceTransactionType = null, $forceConfId = 0)
    {
        $request = new Request($this->account, $this->apiKey, $this->secretKey);

        $params = array(
            'token'       => $token,
            'refId'       => $refId,
            'customerId'  => $customerId,
            'cart'        => $cart,
            'returnUrl'   => $returnUrl,
            'callbackUrl' => $callbackUrl,
        );

        if (!is_null($forceSaveCard)) {
            $params['forceSaveCard'] = ($forceSaveCard) ? 'Y' : 'N';
        }
        if (!is_null($forceTransactionType)) {
            $params['forceTransactionType'] = $forceTransactionType;
        }
        if ($forceConfId) {
            $params['confId'] = $forceConfId;
        }

        $response = $request->send(
            'pay',
            $params
        );

        if (empty($response->getPayment())) {
            throw new ApiException('Invalid response');
        }

        return $response;
    }

    /**
     * Call "tokenize_card" action
     *
     * @param string $token
     * @param string $refId
     * @param string $customerId
     * @param array $cart
     * @param string $returnUrl
     * @param string $callbackUrl
     * @param int $forceConfId (optional)
     *
     * @return Response
     * @throws ApiException
     */
    public function doTokenizeCard($token, $refId, $customerId, $cart, $returnUrl, $callbackUrl, $forceConfId = 0)
    {
        $request = new Request($this->account, $this->apiKey, $this->secretKey);

        $params = array(
            'token'       => $token,
            'refId'       => $refId,
            'customerId'  => $customerId,
            'cart'        => $cart,
            'returnUrl'   => $returnUrl,
            'callbackUrl' => $callbackUrl,
        );

        if ($forceConfId) {
            $params['confId'] = $forceConfId;
        }

        $response = $request->send(
            'tokenize_card',
            $params
        );

        if (empty($response->getPayment())) {
            throw new ApiException('Invalid response');
        }

        return $response;
    }

    /**
     * @param $xpid
     * @param int $amount
     * @return Response
     * @throws ApiException
     */
    public function doCapture($xpid, $amount = 0)
    {
        return $this->doAction('capture', $xpid, $amount);
    }

    /**
     * @param $xpid
     * @param int $amount
     * @return Response
     * @throws ApiException
     */
    public function doRefund($xpid, $amount = 0)
    {
        return $this->doAction('refund', $xpid, $amount);
    }

    /**
     * @param $xpid
     * @param int $amount
     * @return Response
     * @throws ApiException
     */
    public function doVoid($xpid, $amount = 0)
    {
        return $this->doAction('void', $xpid, $amount);
    }

    /**
     * @param $xpid
     * @return Response
     * @throws ApiException
     */
    public function doGetInfo($xpid)
    {
        return $this->doAction('get_info', $xpid);
    }

    /**
     * @param $xpid
     * @return Response
     * @throws ApiException
     */
    public function doContinue($xpid)
    {
        return $this->doAction('continue', $xpid);
    }

    /**
     * @param $xpid
     * @return Response
     * @throws ApiException
     */
    public function doAccept($xpid)
    {
        return $this->doAction('accept', $xpid);
    }

    /**
     * @param $xpid
     * @return Response
     * @throws ApiException
     */
    public function doDecline($xpid)
    {
        return $this->doAction('decline', $xpid);
    }

    /**
     * @param string $refId
     * @param string $customerId
     * @param string $callbackUrl
     * @param string $xpid
     * @param array $cart
     * @return Response
     * @throws ApiException
     */
    public function doRebill(string $refId, string $customerId, string $callbackUrl, string $xpid, array $cart)
    {
        $request = new Request($this->account, $this->apiKey, $this->secretKey);

        $params = [
            'refId'       => $refId,
            'customerId'  => $customerId,
            'callbackUrl' => $callbackUrl,
            'xpid'        => $xpid,
            'cart'        => $cart,
        ];

        $response = $request->send('rebill', $params);

        if (is_null($response->getPayment())) {
            throw new ApiException('Invalid response');
        }

        return $response;
    }

    /**
     * @param $action
     * @param $xpid
     * @param int $amount
     * @return Response
     * @throws ApiException
     */
    private function doAction($action, $xpid, $amount = 0)
    {
        $request = new Request($this->account, $this->apiKey, $this->secretKey);

        $params = array(
            'xpid' => $xpid,
        );

        if (0 < $amount) {
            $params['amount'] = $amount;
        }

        $response = $request->send(
            $action,
            $params
        );

        if (is_null($response->result)) {
            throw new ApiException('Invalid response');
        }

        return $response;
    }

    /**
     * Get all customer's valid cards. Note: this doesn't include expired cards
     * and cards saved by switched off payment configuration
     *
     * @param string $customerId Public Customer ID
     * @param string $status Cards status
     *
     * @return Response
     *
     * @throws ApiException
     */
    public function doGetCustomerCards($customerId, $status = 'any')
    {
        $request = new Request($this->account, $this->apiKey, $this->secretKey);

        $params = array(
            'customerId' => $customerId,
            'status'     => $status,
        );

        $response = $request->send(
            'get_cards',
            $params,
            'customer'
        );

        if (is_null($response->cards)) {
            throw new ApiException('Invalid response');
        }

        return $response;
    }

    /**
     * Checks if card tokenization is possible (for any or particular customer) and other settings
     *
     * @param string $customerId Public Customer ID (optional)
     *
     * @return Response
     *
     * @throws ApiException
     */
    public function doGetTokenizationSettings($customerId = '')
    {
        $request = new Request($this->account, $this->apiKey, $this->secretKey);

        $params = array(
            'customerId' => $customerId,
        );

        $response = $request->send(
            'get_tokenization_settings',
            $params,
            'config'
        );

        if (is_null($response->tokenizationEnabled)) {
            throw new ApiException('Invalid response');
        }

        return $response;
    }

    /**
     * Set default customer's card
     *
     * @param string $customerId Public Customer ID
     * @param string $cardId Card ID
     *
     * @return Response
     *
     * @throws ApiException
     */
    public function doSetDefaultCustomerCard($customerId, $cardId)
    {
        $request = new Request($this->account, $this->apiKey, $this->secretKey);

        $params = array(
            'customerId' => $customerId,
            'cardId'     => $cardId,
        );

        $response = $request->send(
            'set_default_card',
            $params,
            'customer'
        );

        if (is_null($response->result)) {
            throw new ApiException('Invalid response');
        }

        return $response;
    }

    /**
     * Delete customer card
     *
     * @param string $customerId Public Customer ID
     * @param string $cardId Card ID
     *
     * @return Response
     *
     * @throws ApiException
     */
    public function doDeleteCustomerCard($customerId, $cardId)
    {
        $request = new Request($this->account, $this->apiKey, $this->secretKey);

        $params = array(
            'customerId' => $customerId,
            'cardId'     => $cardId,
        );

        $response = $request->send(
            'delete_card',
            $params,
            'customer'
        );

        if (is_null($response->result)) {
            throw new ApiException('Invalid response');
        }

        return $response;
    }

    /**
     * Get payment configurations
     *
     * @return Response
     * @throws ApiException
     */
    public function doGetPaymentConfs()
    {
        $request = new Request($this->account, $this->apiKey, $this->secretKey);

        $params = array();

        $response = $request->send(
            'get_payment_configurations',
            $params,
            'config'
        );

        if (is_null($response->paymentModule)) {
            throw new ApiException('Invalid response');
        }

        return $response;
    }

    /**
     * Get wallets
     *
     * @return Response
     * @throws ApiException
     */
    public function doGetWallets()
    {
        $request = new Request($this->account, $this->apiKey, $this->secretKey);

        $params = array();

        $response = $request->send(
            'get_wallets',
            $params,
            'config'
        );

        if (is_null($response->result)) {
            throw new ApiException('Invalid response');
        }

        return $response;
    }

    /**
     * Change wallet status
     *
     * @param string $walletId Wallet public ID (same as in JS)
     * @param bool $status Send True to enable, false to disable
     *
     * @return Response
     * @throws ApiException
     */
    public function doSetWalletStatus($walletId, $status)
    {
        $request = new Request($this->account, $this->apiKey, $this->secretKey);

        $params = array(
            'walletId' => $walletId,
            'status' => $status
        );

        $response = $request->send(
            'set_wallet_status',
            $params,
            'config'
        );

        if (is_null($response->result)) {
            throw new ApiException('Invalid response');
        }

        return $response;
    }

    /**
     * Verify Apple Pay domain
     *
     * @param bool $status
     *
     * @return Response
     * @throws ApiException
     */
    public function doVerifyApplePayDomain($domain)
    {
        $request = new Request($this->account, $this->apiKey, $this->secretKey);

        $params = array(
            'domain' => $domain
        );

        $response = $request->send(
            'verify_apple_pay_domain',
            $params,
            'config'
        );

        if (is_null($response->result)) {
            throw new ApiException('Invalid response');
        }

        return $response;
    }

    /**
     * Update subscription
     *
     * @param string  $subscriptionPublicId
     * @param array  $updateParams
     *
     * @return Response
     *
     * @throws ApiException
     */
    public function doUpdateSubscription(string $subscriptionPublicId, array $updateParams)
    {
        $request = new Request($this->account, $this->apiKey, $this->secretKey);

        $params = ['public_id' => $subscriptionPublicId] + $updateParams;

        $response = $request->send(
            'update_subscription',
            $params,
            'subscription'
        );

        if (is_null($response->result)) {
            throw new ApiException('Invalid response');
        }

        return $response;
    }

    /**
     * Get subscriptions settings
     *
     * @return Response
     *
     * @throws ApiException
     */
    public function doGetSubscriptionsSettings()
    {
        $request = new Request($this->account, $this->apiKey, $this->secretKey);

        $params = [];

        $response = $request->send(
            'get_settings',
            $params,
            'subscription'
        );

        if (is_null($response)) {
            throw new ApiException('Invalid response');
        }

        return $response;
    }

    /**
     * Create subscriptions using saved card id
     *
     * @param array  $subscriptionPlans
     * @param string $customerId
     * @param string $xpid
     * @param string $savedCardId
     *
     * @return Response
     *
     * @throws ApiException
     */
    public function doCreateSubscriptions(array $subscriptionPlans, string $customerId, string $xpid, string $savedCardId = ''): Response
    {
        $request = new Request($this->account, $this->apiKey, $this->secretKey);

        $params = [
            'subscriptionPlans' => $subscriptionPlans,
            'customerId'        => $customerId,
            'xpid'              => $xpid,
            'savedCardId'       => $savedCardId,
        ];

        $response = $request->send(
            'create_subscriptions',
            $params,
            'subscription'
        );

        if (is_null($response->getSubscriptions())) {
            throw new ApiException('Invalid response');
        }

        return $response;
    }

    /**
     * Add bulk operation
     *
     * @param string $operation
     * @param array $xpids
     *
     * @return Response
     */
    public function doAddBulkOperation($operation, array $xpids = array())
    {
        $request = new Request($this->account, $this->apiKey, $this->secretKey);

        $params = array(
            'operation'    => $operation,
            'payments' => array(),
        );

        foreach ($xpids as $xpid) {
            $params['payments'][] = array(
                'xpid' => $xpid
            );
        }

        $response = $request->send(
            'add',
            $params,
            'bulk_operation'
        );

        return $response;
    }

    /**
     * Delete bulk operation
     *
     * @param string $batchId
     *
     * @return Response
     */
    public function doDeleteBulkOperation($batchId)
    {
        $request = new Request($this->account, $this->apiKey, $this->secretKey);

        $params = array(
            'batch_id' => $batchId,
        );

        $response = $request->send(
            'delete',
            $params,
            'bulk_operation'
        );

        return $response;
    }

    /**
     * Get bulk operation
     *
     * @param string $batchId
     *
     * @return Response
     */
    public function doGetBulkOperation($batchId)
    {
        $request = new Request($this->account, $this->apiKey, $this->secretKey);

        $params = array(
            'batch_id' => $batchId,
        );

        $response = $request->send(
            'get',
            $params,
            'bulk_operation'
        );

        return $response;
    }

    /**
     * Start bulk operation
     *
     * @param string $batchId
     *
     * @return Response
     */
    public function doStartBulkOperation($batchId)
    {
        $request = new Request($this->account, $this->apiKey, $this->secretKey);

        $params = array(
            'batch_id' => $batchId,
        );

        $response = $request->send(
            'start',
            $params,
            'bulk_operation'
        );

        return $response;
    }

    /**
     * Stop bulk operation
     *
     * @param string $batchId
     *
     * @return Response
     */
    public function doStopBulkOperation($batchId)
    {
        $request = new Request($this->account, $this->apiKey, $this->secretKey);

        $params = array(
            'batch_id' => $batchId,
        );

        $response = $request->send(
            'stop',
            $params,
            'bulk_operation'
        );

        return $response;
    }

    /**
     * @param null $inputData
     * @param null $signature
     *
     * @return Response
     *
     * @throws ApiException
     */
    public function parseCallback($inputData = null, $signature = null)
    {
        if (is_null($inputData)) {
            $inputData = file_get_contents('php://input');
        }
        if (is_null($signature) && !empty($_SERVER)) {
            $header = 'HTTP_' . strtoupper(str_replace('-', '_', Signature::HEADER));
            $signature = (array_key_exists($header, $_SERVER)) ? $_SERVER[$header] : '';
        }

        $response = new Response('callback', $inputData, null, $signature, $this->secretKey);

        if (
            empty($response->getPayment())
            && !$response->getSubscription()
        ) {
            throw new ApiException('Invalid response');
        }

        return $response;
    }

    /**
     * Get X-Payments web location
     *
     * @return string
     */
    public function getXpaymentsWebLocation()
    {
        $host = $this->account . '.' . Request::XP_DOMAIN;

        if (defined('XPAYMENTS_SDK_DEBUG_SERVER_HOST')) {
            $host = constant('XPAYMENTS_SDK_DEBUG_SERVER_HOST');
        }

        return sprintf('https://%s/', $host);
    }

    /**
     * Get X-Payments admin URL
     *
     * @return string
     */
    public function getAdminUrl()
    {
        return $this->getXpaymentsWebLocation() . 'admin.php';
    }

    /**
     * Get X-Payments payment URL
     *
     * @return string
     */
    public function getPaymentUrl()
    {
        return $this->getXpaymentsWebLocation() . 'payment.php';
    }

    /**
     * Client constructor.
     * @param string $account
     * @param string $apiKey
     * @param string $secretKey
     */
    public function __construct($account, $apiKey, $secretKey)
    {
        $this->account = $account;
        $this->apiKey = $apiKey;
        $this->secretKey = $secretKey;
    }

}
