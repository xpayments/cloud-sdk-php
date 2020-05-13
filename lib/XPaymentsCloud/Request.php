<?php
// vim: set ts=4 sw=4 sts=4 et:

/**
 * Copyright (c) 2011-present Qualiteam software Ltd. All rights reserved.
 * See https://www.x-cart.com/license-agreement.html for license details.
 */

namespace XPaymentsCloud;
 
class Request
{
    const XP_DOMAIN = 'xpayments.com';
    const API_VERSION = '4.2';

    private $connectionTimeout = 120;

    private $account;
    private $apiKey;
    private $secretKey;

    /**
     * Request constructor.
     * @param $account
     * @param $apiKey
     * @param $secretKey
     */
    public function __construct($account, $apiKey, $secretKey)
    {
        // TODO validate account (domain name)

        $this->account = $account;
        $this->apiKey = $apiKey;
        $this->secretKey = $secretKey;
    }

    /**
     * Send API request
     *
     * @param string $action
     * @param array $requestData
     * @param string $controller
     *
     * @return Response
     * @throws ApiException
     */
    public function send($action, $requestData, $controller = 'payment')
    {
        // Prepare date
        $url = $this->getApiEndpoint($action, $controller);
        $post = $this->convertHashToJSON($requestData);
        $signature = Signature::get($action, $post, $this->secretKey);
        $postHeaders = array(
            'Authorization: Basic ' . base64_encode($this->account . ':' . $this->apiKey),
            'Content-Type: application/json',
            Signature::HEADER . ': ' . $signature,
        );

        // Send data and get response
        $ch = $this->initCURL($url, $post, $postHeaders);
        $body = curl_exec($ch);
        $error = curl_error($ch);
        $errNo = curl_errno($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $signatureFromHeaders = $this->getSignatureFromHeaders();

        if (
            !empty($error)
            || 0 != $errNo
        ) {
            throw new ApiException($error ?: 'Communication error', $errNo);
        }

        $response = new Response($action, $body, $httpCode, $signatureFromHeaders, $this->secretKey);

        return $response;
    }

    /**
     * Returns X-Payments server host
     *
     * @return string
     */
    private function getServerHost()
    {
        $host = $this->account . '.' . static::XP_DOMAIN;

        if (defined('XPAYMENTS_SDK_DEBUG_SERVER_HOST')) {
            $host = constant('XPAYMENTS_SDK_DEBUG_SERVER_HOST');
        }

        return $host;
    }

    /**
     * @param $action      API action
     * @param $controller  API controller
     *
     * @return string
     */
    private function getApiEndpoint($action, $controller)
    {
        return 'https://' . $this->getServerHost() .  '/api/' . static::API_VERSION . '/' . $controller . '/' . $action;
    }

    /**
     * Initializes cURL resource for posting data
     *
     * @param string $url URL to which send data
     * @param string $content Data to post
     * @param string $headers Headers for content
     *
     * @return resource
     */
    private function initCURL($url, $content, $headers)
    {
        // Clear static var
        $this->getSignatureFromHeaders();

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->connectionTimeout);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $content);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_ENCODING, '');
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

        curl_setopt($ch, CURLOPT_HEADERFUNCTION, array($this, 'getSignatureFromHeaders'));

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

        return $ch;
    }

    /**
     * Convert hash array to JSON
     *
     * @param array   $data  Hash array
     *
     * @return string
     */
    private function convertHashToJSON(array $data)
    {
        return json_encode($data);
    }

    /**
     * CURL headers collector callback used to get signature header
     *
     * @return mixed
     */
    protected function getSignatureFromHeaders()
    {
        static $signature = '';

        $args = func_get_args();

        if (0 == count($args)) {
            $return = $signature;
            $signature = '';
        } else {
            if (0 === stripos($args[1], Signature::HEADER)) {
                $signature = substr(trim($args[1]), strlen(Signature::HEADER) + 2);
            }
            $return = strlen($args[1]);
        }

        return $return;
    }

}
