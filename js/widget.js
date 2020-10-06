/*
 * X-Payments Cloud SDK - Payment Widget
 */

function XPaymentsWidget()
{
    this.serverDomain = 'xpayments.com';
    this.messageNamespace = 'xpayments.widget.';
    this.receiverNamespace = 'xpayments.checkout.';
    this.widgetId = this.generateId();
    this.previousHeight = -1;
    this.applePaySession = null;
    this.paymentMethod = null;

    this.config = {
        debug: false,
        account: '',
        widgetKey: '',
        container: '',
        form: '',
        language: '',
        customerId: '',
        tokenName: 'xpaymentsToken',
        showSaveCard: true,
        enableWallets: true,
        applePay: {
            enabled: true,
            checkoutMode: false,
            shippingMethods: [],
            requiredShippingFields: [
                'email',
                'name',
                'phone',
                'postalAddress'
            ],
            requiredBillingFields: [
                'postalAddress'
            ]
        },
        company: {
            name: '',
            domain: document.location.hostname,
            countryCode: '',
        },
        order: {
            tokenizeCard: false,
            total: -1,
            currency: ''
        }
    }

    this.handlers = {};

    this.bindedListener = false;
    this.bindedSubmit = false;

}

XPaymentsWidget.prototype.on = function(event, handler, context)
{
    if ('undefined' === typeof context) {
        context = this;
    }

    if ('formSubmit' !== event) {

        this.handlers[event] = handler.bind(context);

    } else {
        var formElm = this.getFormElm();

        if (formElm) {
            if (this.bindedSubmit) {
                formElm.removeEventListener('submit', this.bindedSubmit);
            }
            this.bindedSubmit = handler.bind(context);
            formElm.addEventListener('submit', this.bindedSubmit);
        }
    }

    return this;
}


XPaymentsWidget.prototype.trigger = function(event, params)
{
    if ('function' === typeof this.handlers[event]) {
        this.handlers[event](params);
    }

    this._log('X-Payments widget triggered: ' + event, params);

    return this;
}

XPaymentsWidget.prototype.init = function(settings)
{
  for (var key in settings) {
      if ('undefined' !== typeof this.config[key]) {
          if ('object' === typeof this.config[key]) {
              for (var subkey in settings[key]) {
                  if ('undefined' !== typeof this.config[key][subkey]) {
                      this.config[key][subkey] = settings[key][subkey];
                  }
              }
          } else {
              this.config[key] = settings[key];
          }
      }
  }

  if (this.config.order.tokenizeCard) {
      this.config.showSaveCard = false;
  }

  // Set default handlers
  this.on('formSubmit', function (domEvent) {
      // "this" here is the widget
      this.submit();
      domEvent.preventDefault();
  })
  .on('success', this._defaultSuccessHandler)
  .on('applepay.paymentauthorized', this._applePayAuthorized)
  .on('alert', function(params) {
      window.alert(params.message);
  });

  this.bindedListener = this.messageListener.bind(this);
  window.addEventListener('message', this.bindedListener);

  if (
      'undefined' !== typeof settings.autoload
      && settings.autoload
  ) {
      this.load();
  }

  return this;
}

XPaymentsWidget.prototype.initCheckoutWithApplePay = function(settings)
{
    this.config.container = 'body';
    this.config.applePay.checkoutMode = true;
    this.init(settings);
}

XPaymentsWidget.prototype.generateId = function()
{
    return Math.random().toString(36).substring(2, 15) + Math.random().toString(36).substring(2, 15);
}

XPaymentsWidget.prototype.getIframeId = function()
{
    return 'xpayments-' + this.widgetId;
}

XPaymentsWidget.prototype.getIframeElm = function()
{
    return document.getElementById(this.getIframeId());
}

XPaymentsWidget.prototype.getContainerElm = function()
{
    return this.safeQuerySelector(this.config.container);
}

XPaymentsWidget.prototype.getFormElm = function()
{
    return this.safeQuerySelector(this.config.form);
}

XPaymentsWidget.prototype.isValid = function()
{
    return this.getIframeElm() && this.getFormElm();
}

XPaymentsWidget.prototype.safeQuerySelector = function(selector)
{
    var elm = false;
    if (selector) {
        elm = document.querySelector(selector);
    }
    return elm;
}

XPaymentsWidget.prototype.load = function()
{
    var containerElm = this.getContainerElm();
    if (!containerElm) {
        return this;
    }

    var elm = this.getIframeElm();
    if (!elm) {
        elm = document.createElement('iframe');
        elm.id = this.getIframeId();
        elm.style.width = '100%';
        elm.style.height = '0';
        elm.style.overflow = 'hidden';
        elm.style.border = 'none';
        if (this.config.applePay.checkoutMode) {
            elm.style.display = 'none';
        }
        elm.setAttribute('scrolling', 'no');
        containerElm.appendChild(elm);
    }

    var url =
        this.getServerUrl() + '/payment.php' +
        '?widget_key=' + encodeURIComponent(this.config.widgetKey) +
        '&widget_id=' + encodeURIComponent(this.widgetId);
    if (this.config.customerId) {
        url += '&customer_id=' + encodeURIComponent(this.config.customerId);
    }
    if (this.config.language) {
        url += '&language=' + encodeURIComponent(this.config.language);
    }
    if (this.config.applePay.checkoutMode) {
        url += '&target=checkout_apple_pay';
    }
    elm.src = url;

    return this;
}

XPaymentsWidget.prototype.getServerHost = function()
{
    return this.config.account + '.' + this.serverDomain;
}

XPaymentsWidget.prototype.getServerUrl = function()
{
    return 'https://' + this.getServerHost();
}

XPaymentsWidget.prototype.submit = function()
{
    if (!this.config.applePay.checkoutMode) {
        this._sendEvent('submit');
    } else {
        this.beginCheckoutWithApplePay();
    }
}

XPaymentsWidget.prototype.beginCheckoutWithApplePay = function()
{
    if (this._isApplePayAvailable()) {
        this._sendEvent('applepay.begincheckout');
    }
}

XPaymentsWidget.prototype._afterLoad = function(params)
{
    this.showSaveCard();
    if (this.config.enableWallets) {
        if (this._isApplePayAvailable()) {
            this._sendEvent('applepay.enable');
        }
    }
    this.setOrder();
    this.resize(params.height);
}

XPaymentsWidget.prototype._defaultSuccessHandler = function(params) {
    var formElm = this.getFormElm();
    if (formElm) {
        var input = document.getElementById(this.config.tokenName);
        if (!input) {
            input = document.createElement('input');
            input.type = 'hidden';
            input.name = input.id = this.config.tokenName;
            formElm.appendChild(input);
        }
        input.value = params.token;
        formElm.submit();
    }
}

XPaymentsWidget.prototype.getPaymentMethod = function()
{
    return (!this.config.applePay.checkoutMode) ? this.paymentMethod : 'apple_pay';
}

XPaymentsWidget.prototype._paymentMethodChange = function(params)
{
    this.paymentMethod = params.newId;
}

XPaymentsWidget.prototype._applePayValidated = function(params)
{
    try {
        this.applePaySession.completeMerchantValidation(params.data);
    } catch (e) {
    }
}

XPaymentsWidget.prototype._applePayAuthorized = function(params)
{
    this.succeedApplePayPayment(params);
}

XPaymentsWidget.prototype._applePayCompleted = function(params)
{
    this.completeApplePayPayment({ status: ApplePaySession.STATUS_SUCCESS, errors: [] });
}

XPaymentsWidget.prototype._applePayError = function(params)
{
    try {
        this.applePaySession.abort();
    } catch (e) {
        // Skip errors if any
    }
}

XPaymentsWidget.prototype._applePayStart = function(params)
{
    var request = {
        countryCode: this.config.company.countryCode,
        currencyCode: this.config.order.currency,
        supportedNetworks: params.supportedNetworks,
        merchantCapabilities: ['supports3DS'],
        total: {
            label: this.config.company.name,
            amount: this.config.order.total
        },
    };

    this.applePayCustomerAddress = null;
    if (this.config.applePay.checkoutMode) {
        if (this.config.applePay.shippingMethods) {
            request.shippingMethods = this.config.applePay.shippingMethods;
        }
        if (this.config.applePay.requiredShippingFields) {
            request.requiredShippingContactFields = this.config.applePay.requiredShippingFields;
        }
        if (this.config.applePay.requiredBillingFields) {
            request.requiredBillingContactFields = this.config.applePay.requiredBillingFields;
        }
    }

    this.applePaySession = new ApplePaySession(3, request);

    this.applePaySession.onvalidatemerchant = (function(event) {
        this._sendEvent('applepay.validatemerchant', {
            validationURL: event.validationURL,
            displayName: this.config.company.name,
            context: this.config.company.domain,
        });
    }).bind(this);

    this.applePaySession.onpaymentauthorized = (function(event) {
        this.trigger('applepay.paymentauthorized', event.payment);
    }).bind(this);

    this.applePaySession.oncancel = (function(event) {
        this._sendEvent('applepay.cancel');
    }).bind(this);

    if (this.config.applePay.checkoutMode) {
        this.applePaySession.onshippingcontactselected = (function(event) {
            this.trigger('applepay.shippingcontactselected', event.shippingContact);
        }).bind(this);
        this.applePaySession.onshippingmethodselected = (function(event) {
            this.trigger('applepay.shippingmethodselected', event.shippingMethod);
        }).bind(this);
    }

    this.applePaySession.begin();

}

XPaymentsWidget.prototype.completeApplePayShippingContactSelection = function(updateData) {
    this.applePaySession.completeShippingContactSelection(updateData);
}

XPaymentsWidget.prototype.completeApplePayShippingMethodSelection = function(updateData) {
    this.applePaySession.completeShippingMethodSelection(updateData);
}

XPaymentsWidget.prototype.completeApplePayPayment = function(updateData) {
    this.applePaySession.completePayment(updateData);
}

XPaymentsWidget.prototype.succeedApplePayPayment = function(payment) {
    this._sendEvent('applepay.paymentauthorized', { payment: payment });
}


XPaymentsWidget.prototype.isApplePaySupportedByDevice = function() {
    return (window.ApplePaySession && ApplePaySession.canMakePayments());
}

XPaymentsWidget.prototype._isApplePayAvailable = function() {
    return this.config.applePay.enabled && this.isApplePaySupportedByDevice();
}

XPaymentsWidget.prototype._checkApplePayActiveCard = function(params)
{
    var promise = ApplePaySession.canMakePaymentsWithActiveCard(params.merchantId);
    promise.then((function (canMakePayments) {
        if (canMakePayments) {
            this.trigger('applepay.forceselect');
            this._sendEvent('applepay.select');
        }
    }).bind(this));
}

XPaymentsWidget.prototype.showSaveCard = function(value)
{
    if ('undefined' === typeof value) {
        value = this.config.showSaveCard;
    } else {
        this.config.showSaveCard = (true === value);
    }
    this._sendEvent('savecard', { show: value });
}


XPaymentsWidget.prototype.refresh = function()
{
    this._sendEvent('refresh');
}

XPaymentsWidget.prototype.resize = function(height)
{
    var elm = this.getIframeElm();
    if (elm) {
        this.previousHeight = elm.style.height;
        elm.style.height = height + 'px';
    }
}

XPaymentsWidget.prototype.setOrder = function(total, currency)
{
    if ('undefined' !== typeof total) {
        this.config.order.total = total;
    }
    if ('undefined' !== typeof currency) {
        this.config.order.currency = currency;
    }

    this._sendEvent('details', {
        tokenizeCard: this.config.order.tokenizeCard,
        total: this.config.order.total,
        currency: this.config.order.currency
    });
}

XPaymentsWidget.prototype.destroy = function()
{
    if (this.bindedListener) {
        window.removeEventListener('message', this.bindedListener);
    }

    var formElm = this.getFormElm();
    if (this.bindedSubmit && formElm) {
        formElm.removeEventListener('submit', this.bindedSubmit);
    }

    var containerElm = this.getContainerElm();
    if (containerElm) {
        var elm = this.getIframeElm();
        if (elm && containerElm.contains(elm)) {
            containerElm.removeChild(elm);
        }
    }
}

XPaymentsWidget.prototype.messageListener = function(event)
{
    if (window.JSON) {
        var msg = false;
        if (-1 !== this.getServerUrl().toLowerCase().indexOf(event.origin.toLowerCase())) {
            try {
                msg = window.JSON.parse(event.data);
            } catch (e) {
                // Skip invalid messages
            }
        }

        if (
            msg &&
            msg.event &&
            0 === msg.event.indexOf(this.messageNamespace) &&
            (!msg.widgetId || msg.widgetId === this.widgetId)
        ) {
            this._log('Received from X-Payments: ' + msg.event);

            var eventType = msg.event.substr(this.messageNamespace.length);

            if ('loaded' === eventType) {
                this._afterLoad(msg.params);
            } else if ('applepay.start' === eventType) {
                this._applePayStart(msg.params);
            } else if ('applepay.checkactivecard' === eventType) {
                this._checkApplePayActiveCard(msg.params);
            } else if ('applepay.merchantvalidated' === eventType) {
                this._applePayValidated(msg.params);
            } else if ('applepay.completed' === eventType) {
                this._applePayCompleted(msg.params);
            } else if ('applepay.error' === eventType) {
                this._applePayError(msg.params);
            } else if ('paymentmethod.change' === eventType) {
                this._paymentMethodChange(msg.params);
            } else if ('resize' === eventType) {
                this.resize(msg.params.height);
            } else if ('alert' === eventType) {
                msg.params.message =
                    ('string' === typeof msg.params.message)
                    ? msg.params.message.replace(/<\/?[^>]+>/gi, '')
                    : '';
            }

            this.trigger(eventType, msg.params);
        }

    }
}

XPaymentsWidget.prototype._isDebugMode = function()
{
    return this.config.debug;
}

XPaymentsWidget.prototype._log = function(msg, params)
{
    if (this._isDebugMode()) {
        if ('undefined' !== typeof params) {
            msg = msg + "\n" + JSON.stringify(params);
        }
        console.log(msg);
    }
}

XPaymentsWidget.prototype._sendEvent = function(eventName, eventParams)
{
    if ('undefined' === typeof eventParams) {
        eventParams = {};
    }

    this._postMessage({
        event: this.receiverNamespace + eventName,
        params: eventParams
    })
}

XPaymentsWidget.prototype._postMessage = function(message)
{
    var elm = this.getIframeElm();
    if (
        window.postMessage
        && window.JSON
        && elm
        && elm.contentWindow
    ) {
        this._log('Sent to X-Payments: ' + message.event, message.params);
        elm.contentWindow.postMessage(window.JSON.stringify(message), '*');
    } else {
        this._log('Error sending message - iframe wasn\'t initialized!');
    }
}
