import template from './sendcloud-button.html.twig';
import './sendcloud-button.scss';

const { Component } = Shopware;

Component.register('sendcloud-button', {
    template,

    props: {
        onClick: {
            type: Function,
            required: true,
        },
        sendcloudButtonLabel: {
            type: String,
            required: true,
            default: ''
        },
    }
});