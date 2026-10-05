# X-Payments Cloud SDK for PHP

When processing online credit card payments, X-Payments works as an intermediary between a shopping cart application or any other API-friendly system on one side and payment gateways and 3D-Secure systems on the other side, implementing the PCI compliant secure layer that works with credit card data and keeps your applications and systems out of the PCI scope.

This SDK covers the [X-Payments Cloud server-side API](https://xpayments.stoplight.io/docs/server-side-api/spsxnj22ewcd7-x-payments-cloud-api-overview) **version 4.8** and ships the browser scripts that collect card details.

## Requirements

- PHP 7.1 or newer, including PHP 8.x
- PHP extensions: curl, hash, openssl, json
- An X-Payments Cloud account: the account name, API key, secret key and widget key. The connect widget (`js/connect.js`) hands these keys to your admin area when a store is connected.

## Installation

```sh
composer require xpayments/cloud-sdk-php
```

Without Composer, include `lib/XPaymentsCloud/Client.php`. It loads the rest of the SDK.

## How a payment works

1. The checkout page shows the card form with `js/widget.js`. The form lives in an iframe served by X-Payments Cloud, so card data never reaches your server.
2. When the shopper submits the form, the widget returns a one-time token, which your page posts to your server along with the order.
3. Your server calls `doPay()` with that token and the cart.
4. If the response contains `redirectUrl` (3-D Secure), send the shopper there. When they come back to your `returnUrl`, call `doContinue()`.
5. X-Payments Cloud reports later status changes to your `callbackUrl`. Read them with `parseCallback()`, which also verifies their signature.

## Server side

```php
use XPaymentsCloud\ApiException;
use XPaymentsCloud\Client;
use XPaymentsCloud\Model\Payment;

$client = new Client($account, $apiKey, $secretKey);

try {
    $response = $client->doPay(
        $token,                // one-time token from the widget
        $orderNumber,          // your reference ID for this payment
        $xpaymentsCustomerId,  // customerId from an earlier payment by this shopper, or ''
        $cart,                 // see "Cart" below
        'https://shop.example.com/checkout/xpayments-return',
        'https://shop.example.com/xpayments-callback'
    );

    if ($response->redirectUrl) {
        // 3-D Secure: redirect the shopper to $response->redirectUrl,
        // then call $client->doContinue($xpid) when they return to returnUrl
    }

    $payment = $response->getPayment();

    if (in_array($payment->status, array(Payment::AUTH, Payment::CHARGED))) {
        // Accepted. Store $payment->xpid for capture, void, refund and get_info,
        // and $payment->customerId to offer the shopper's saved cards next time.
    }
} catch (ApiException $exception) {
    // Show $exception->getPublicMessage() to the shopper when it is set;
    // log $exception->getMessage() and $exception->getCode().
}
```

### Cart

Amounts are strings in `1234.56` format, and addresses use ISO country codes:

```php
$address = array(
    'firstname' => 'John',
    'lastname'  => 'Smith',
    'address'   => '123 Main St',
    'city'      => 'Springfield',
    'state'     => 'IL',
    'country'   => 'US',
    'zipcode'   => '62701',
    'phone'     => '',
    'fax'       => '',
    'company'   => '',
    'email'     => 'john@example.com',
);

$cart = array(
    'login'           => 'john@example.com',  // customer identifier in your store
    'billingAddress'  => $address,
    'shippingAddress' => $address,
    'items'           => array(
        array('sku' => 'T-100', 'name' => 'T-shirt', 'price' => '20.00', 'quantity' => 2),
    ),
    'currency'        => 'USD',
    'shippingCost'    => '5.00',
    'taxCost'         => '0.00',
    'discount'        => '0.00',
    'totalCost'       => '45.00',
    'description'     => 'Order #1001',
    'merchantEmail'   => 'sales@example.com',
);
```

### Callbacks

```php
$response = $client->parseCallback();   // reads php://input and the X-Payments-Signature header

$payment = $response->getPayment();              // payment status updates
$subscription = $response->getSubscription();    // subscription updates
```

`parseCallback()` throws `ApiException` when the signature does not match. Reject such requests.

### Methods

| Method | API call |
|---|---|
| `doPay()` | `payment/pay` |
| `doTokenizeCard()` | `payment/tokenize_card` (save a card for later payments) |
| `doContinue($xpid)` | `payment/continue` (after 3-D Secure) |
| `doCapture($xpid, $amount = 0)` | `payment/capture` (0 means the full amount) |
| `doVoid($xpid, $amount = 0)` | `payment/void` |
| `doRefund($xpid, $amount = 0)` | `payment/refund` |
| `doAccept($xpid)`, `doDecline($xpid)` | `payment/accept`, `payment/decline` (payments held for fraud review) |
| `doGetInfo($xpid, $refresh = false)` | `payment/get_info` |
| `doRefresh($xpid)` | `payment/refresh` (re-read the payment state from the gateway) |
| `doRebill()` | `payment/rebill` (charge a saved card again) |
| `doGetCustomerCards()`, `doSetDefaultCustomerCard()`, `doDeleteCustomerCard()` | `customer/get_cards`, `customer/set_default_card`, `customer/delete_card` |
| `doGetTokenizationSettings()` | `config/get_tokenization_settings` |
| `doGetPaymentConfs()` | `config/get_payment_configurations` |
| `doGetWallets()`, `doSetWalletStatus()`, `doVerifyApplePayDomain()` | `config/get_wallets`, `config/set_wallet_status`, `config/verify_apple_pay_domain` |
| `doCreateSubscriptions()`, `doUpdateSubscription()`, `doGetSubscriptionsSettings()` | `subscription/create_subscriptions`, `subscription/update_subscription`, `subscription/get_settings` |
| `doAddBulkOperation()`, `doStartBulkOperation()`, `doStopBulkOperation()`, `doGetBulkOperation()`, `doDeleteBulkOperation()` | `bulk_operation/*` |
| `doTestConnection()` | `connect/test` (checks the account, API key and secret key) |
| `parseCallback()` | incoming callbacks |

Whether a gateway supports partial or repeated capture, void and refund is listed in `$payment->supportedTransactions`; see the `Payment::TXN_*` constants.

### Protocol

Useful if you integrate without this SDK:

- Requests are `POST https://<account>.xpayments.com/api/4.8/<controller>/<action>` with a JSON body.
- They use HTTP Basic authorization with `<account>:<API key>`.
- Requests, responses and callbacks all carry an `X-Payments-Signature` header. It is the hex HMAC-SHA256 of the action name followed by the raw JSON body, keyed with the secret key. Callbacks use the action name `callback`.

## Browser side

`js/widget.js` renders the card form and the Apple Pay / Google Pay buttons:

```html
<form id="checkout-form" method="post" action="/checkout/place-order">
    <div id="xpayments-container"></div>
    <button type="submit">Place order</button>
</form>

<script src="widget.js"></script>
<script>
    var widget = new XPaymentsWidget();
    widget.init({
        account: 'your-account',
        widgetKey: 'your-widget-key',
        container: '#xpayments-container',
        form: '#checkout-form',
        customerId: '',                          // X-Payments customer ID, to show saved cards
        order: { total: '45.00', currency: 'USD' }
    }).load();
</script>
```

On form submit the widget gets a token for the entered card. It puts the token into the hidden field `xpaymentsToken` and submits the form. Use `widget.on('success', function (params) { ... })` to handle `params.token` yourself, `widget.on('fail', ...)` to react to errors, and `widget.setOrder(total, currency)` when the total changes.

`js/connect.js` embeds the X-Payments Cloud sign-up / connect wizard into your admin area. Its `config` event delivers the account name and keys to store.

## Security and PCI DSS

- Card details must be entered only in the X-Payments Cloud iframe. Never build your own card form or send card data to your server, your logs or the API.
- Keep the API key and secret key on the server: not in JavaScript, logs or version control. Only the account name and widget key go to the browser.
- The SDK verifies the server's TLS certificate and the signature of every response and callback. Do not turn either check off. If cURL cannot find CA certificates, fix the CA bundle on the server (`curl.cainfo` / `openssl.cafile` in php.ini).
- Use HTTPS for `returnUrl` and `callbackUrl`.
- The widget token is single-use and must not be logged.

## Documentation

- [X-Payments Cloud API overview](https://xpayments.stoplight.io/docs/server-side-api/spsxnj22ewcd7-x-payments-cloud-api-overview)
- [Accepting payments: server-side API guide](https://xpayments.stoplight.io/docs/server-side-api/7rgifzzzjejex-accept-payment)
- [X-Payments development docs](https://support.x-cart.com/en/articles/5842415-x-payments-development-docs)
- [X-Payments Cloud user manual](https://support.x-cart.com/en/collections/3159781-x-payments-cloud)

### Supported payment gateways

X-Payments Cloud supports more than 60 payment gateway integrations: ANZ eGate, American Express Web-Services API Integration, Authorize.Net, Bambora (Beanstream), Beanstream (legacy API), Bendigo Bank, BillriantPay, BluePay, BlueSnap Payment API (XML), Braintree, BluePay Canada (Caledon), Cardinal Commerce Centinel, Chase Paymentech, CommWeb - Commonwealth Bank, BAC Credomatic, CyberSource - SOAP Toolkit API, X-Payments Demo Pay, X-Payments Demo Pay 3-D Secure, DIBS, DirectOne - Direct Interface, eProcessing Network - Transparent Database Engine, SecurePay Australia, Moneris eSELECTplus, Elavon (Realex API), ePDQ MPI XML (Phased out), eWAY Rapid - Direct Connection, eWay Realtime Payments XML, Sparrow (5th Dimension Gateway), First Data Payeezy Gateway (ex- Global Gateway e4), Global Iris, Global Payments, GoEmerchant - XML Gateway API, HeidelPay, Innovative Gateway, iTransact XML, Payment XP (Meritus) Web Host, NAB - National Australia Bank, NMI (Network Merchants Inc.), Netbilling - Direct Mode, Netevia, Ingenico ePayments (Ogone e-Commerce), PayGate South Africa, Payflow Pro, PayPal REST API, PayPal Payments Pro (PayPal API), PayPal Payments Pro (Payflow API), PSiGate XML API, QuantumGateway - XML Requester, Intuit QuickBooks Payments, QuickPay, Worldpay Corporate Gateway - Direct Model, Global Payments (ex. Realex), Opayo Direct (ex. Sage Pay Go - Direct Interface), Paya (ex. Sage Payments US), Simplify Commerce by MasterCard, SkipJack, Suncorp, TranSafe, powered by Monetra, 2Checkout, USA ePay - Transaction Gateway API, Elavon Converge (ex VirtualMerchant), WebXpress, Worldpay Total US, Worldpay US (Lynk Systems).

### Supported fraud-screening services

- [Kount](https://support.x-cart.com/en/articles/5627364-kount-antifraud-screening-x-payments-cloud)
- [NoFraud](https://support.x-cart.com/en/articles/5627410-nofraud-fraud-prevention-x-payments-cloud)
- [Signifyd](https://support.x-cart.com/en/articles/5676424-signifyd-fraud-protection-x-payments-cloud)

## License

Use of this SDK is subject to the [X-Cart License Agreement](https://www.x-cart.com/license-agreement.html).

Copyright (c) 2011-present X-Cart Holdings LLC. All rights reserved.

## Support

If you have any questions, please [contact us](https://www.x-payments.com/contact-us).
