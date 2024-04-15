import template from './sendcloud-notification.html.twig';
import './sendcloud-notification.scss';

const { Component } = Shopware;

Component.register('sendcloud-notification', {
    template,

    props: {
        sendcloudNotificationTitle: {
            type: String,
            required: true,
            default: ''
        },

        sendcloudNotificationMessage: {
            type: String,
            required: true,
            default: ''
        },
    }
});