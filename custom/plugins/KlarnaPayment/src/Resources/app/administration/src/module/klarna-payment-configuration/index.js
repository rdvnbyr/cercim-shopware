import './components/klarna-payment-plugin-icon';
import './components/klarna-select-order-state';
import './components/klarna-select-delivery-state';
import './components/klarna-select-payment-codes';
import './components/klarna-select-salutation';

import './extension/sw-settings-index';

import './page/klarna-payment-settings';
import './page/klarna-payment-wizard';

import deDE from './snippet/de_DE.json';
import enGB from './snippet/en_GB.json';

const { Module } = Shopware;

let configuration = {
    type: 'plugin',
    name: 'KlarnaPayment',
    title: 'klarna-payment-configuration.module.title',
    description: 'klarna-payment-configuration.module.description',
    version: '1.0.0',
    targetVersion: '1.0.0',

    snippets: {
        'de-DE': deDE,
        'en-GB': enGB
    },

    routes: {
        settings: {
            component: 'klarna-payment-settings',
            path: 'settings',
            meta: {
                parentPath: 'sw.settings.index'
            }
        },
        wizard: {
            component: 'klarna-payment-wizard',
            path: 'wizard',
            meta: {
                parentPath: 'sw.settings.index'
            }
        }
    }
};

const version = Shopware.Context.app.config.version;
const match = version.match(/((\d+)\.?(\d+?)\.?(\d+)?\.?(\d*))-?([A-z]+?\d+)?/i);

if(match && parseInt(match[2]) === 6 && parseInt(match[3]) > 3) {
    configuration.settingsItem = [{
        name:   'klarna-payment-configuration',
        to:     'klarna.payment.configuration.settings',
        label:  'klarna-payment-configuration.module.title',
        group:  'plugins',
        iconComponent: 'klarna-payment-plugin-icon',
        backgroundEnabled: false
    }];
}

Module.register('klarna-payment-configuration', configuration);
