/*
 * X-Payments Cloud SDK - Connect Widget
 */

function XPaymentsConnect(elmSelector, quickAccessKey, handlers) {
    this.serverDomain = 'xpayments.com';
    this.messageNamespace = 'xpayments.connect.';

    this.previousHeight = -1;

    this.config = {
        debug: false,
        account: '',
        container: '',
        topElement: '',
        referrerUrl: document.location.href,
        applePayOnly: false,
        quickAccessKey: ''
    }

    this.handlers = {};

    this.bindedListener = false;

}

XPaymentsConnect.prototype.init = function(settings)
{
    for (var key in settings) {
        if ('undefined' !== typeof this.config[key]) {
            this.config[key] = settings[key];
        }
    }

    // Set default handlers
    this.on('alert', function(params) {
        window.alert(params.message);
    }).on('config', function() {
        console.error('X-Payments Widget is not configured properly!');
    });

    this.bindedListener = this.messageListener.bind(this);
    window.addEventListener('message', this.bindedListener);

    return this;
}

XPaymentsConnect.prototype.getContainerElm = function()
{
    return this.safeQuerySelector(this.config.container);
}

XPaymentsConnect.prototype.getIframeId = function()
{
    return 'xpayments-connect';
}

XPaymentsConnect.prototype.getIframeElm = function()
{
    return document.getElementById(this.getIframeId());
}

XPaymentsConnect.prototype.safeQuerySelector = function(selector)
{
    var elm = false;
    if (selector) {
        elm = document.querySelector(selector);
    }
    return elm;
}

XPaymentsConnect.prototype.resize = function(height)
{
    var elm = this.getIframeElm();
    if (elm) {
        this.previousHeight = elm.style.height;
        elm.style.height = height + 'px';
    }
}

XPaymentsConnect.prototype.load = function()
{
    var containerElm = this.getContainerElm();
    if (!containerElm) {
        return this;
    }

    var elm = document.createElement('iframe');
    elm.id = this.getIframeId();
    elm.style.width = '100%';
    elm.style.height = '0';
    elm.style.overflow = 'hidden';
    elm.setAttribute('scrolling', 'no')
    containerElm.appendChild(elm);

    elm.src = this.getRedirectUrl();

}

XPaymentsConnect.prototype.getRedirectUrl = function()
{
    return 'https://' + this.getServerHost() + '/' +
        '?ref=' + encodeURIComponent(this.config.referrerUrl) +
        '&account=' + encodeURIComponent(this.config.account) +
        '&apple_pay=' + (this.config.applePayOnly ? 'Y' : 'N') +
        '&quickaccess=' + encodeURIComponent(this.config.quickAccessKey);
}

XPaymentsConnect.prototype.on = function(event, handler)
{
    this.handlers[event] = handler.bind(this);
    return this;
}

XPaymentsConnect.prototype.trigger = function(event, params)
{
    if ('function' === typeof this.handlers[event]) {
        this.handlers[event](params);
    }
    return this;
}

XPaymentsConnect.prototype.getServerHost = function()
{
    return 'connect.' + this.serverDomain;
}

XPaymentsConnect.prototype.messageListener = function(event)
{
    if (window.JSON) {
        var msg = false;

        try {
            msg = window.JSON.parse(event.data);
        } catch (e) {
            // Skip invalid messages
        }

        if (msg && msg.event && 0 === msg.event.indexOf(this.messageNamespace)) {
            this.log('X-Payments Event: ' + msg.event + "\n" + window.JSON.stringify(msg.params));

            var eventType = msg.event.substr(this.messageNamespace.length);

            if ('loaded' === eventType) {
                if (-1 !== this.previousHeight) {
                    var topElm = (this.config.topElement)
                        ? this.safeQuerySelector(this.config.topElement)
                        : this.getContainerElm();
                    if (topElm) {
                        topElm.scrollIntoView(true);
                    }
                }
                this.resize(msg.params.height);
            } else if ('resize' === eventType) {
                this.resize(msg.params.height);
            } else if ('alert' === eventType) {
                msg.params.message = msg.params.message.replace(/<\/?[^>]+>/gi, '');
            }

            this.trigger(eventType, msg.params);
        }
    }
}

XPaymentsConnect.prototype.postMessage = function(message)
{
    var elm = this.getIframeElm();
    if (
        window.postMessage
        && window.JSON
        && elm
        && elm.contentWindow
    ) {
        this.log('Sent to X-Payments: ' + message.event + "\n" + window.JSON.stringify(message.params));
        elm.contentWindow.postMessage(window.JSON.stringify(message), '*');
    } else {
        this.log('Error sending message - iframe wasn\'t initialized!');
    }
}

XPaymentsConnect.prototype.log = function(msg)
{
    if (this.config.debug) {
        console.log(msg);
    }
}