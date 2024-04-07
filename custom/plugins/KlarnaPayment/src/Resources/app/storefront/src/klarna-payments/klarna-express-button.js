/* eslint-disable import/no-unresolved */
/* eslint-disable no-debugger */
/* eslint-disable no-unused-vars */
/* global Klarna */

import Plugin from 'src/plugin-system/plugin.class';
import HttpClient from 'src/service/http-client.service';
import PageLoadingIndicatorUtil from 'src/utility/loading-indicator/page-loading-indicator.util';

export default class KlarnaExpressButton extends Plugin {
    /**
     * default plugin options
     *
     * @type {*}
     */
    static options = {
        url: 'https://x.klarnacdn.net/express-button/v1/lib.js',
        merchantId: '',
        environment: 'playground',
        callbackUrl: '',
    };

    init() {
        if (!this.el) {
            return;
        }

        this._createScript();
        this._initClient();
        this._handleScriptLoaded();
    }

    _initClient() {
        this._client = new HttpClient();
    }

    _createScript() {
        const script = document.createElement('script');
        script.type = 'text/javascript';
        script.src = this.options.url;
        script.dataset.environment = this.options.environment;
        script.dataset.id = this.options.merchantId;
        script.async = true;

        document.head.appendChild(script);
    }

    _handleScriptLoaded() {
        let me = this;

        window.klarnaExpressButtonAsyncCallback = function() {
            Klarna.ExpressButton.on('user-authenticated', function (callbackData) {
                PageLoadingIndicatorUtil.create();

                me._client.post(
                    me.options.callbackUrl,
                    JSON.stringify(callbackData),
                    (response) => {
                        const data = JSON.parse(response);
                        window.location.replace(data.url);
                    }
                );
            });
        }
    }
}
