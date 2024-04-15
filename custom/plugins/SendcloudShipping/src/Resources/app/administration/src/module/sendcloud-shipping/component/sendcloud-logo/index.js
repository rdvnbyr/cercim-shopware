import template from './sendcloud-logo.html.twig';
import './sendcloud-logo.scss';

const { Component } = Shopware;

Component.register('sendcloud-logo', {
    template,

    props: {
        sendcloudLogoUrl: {
            type: String,
            required: true,
            default: '/bundles/sendcloudshipping/administration/img/sendcloud.svg'
        },
    }
});
