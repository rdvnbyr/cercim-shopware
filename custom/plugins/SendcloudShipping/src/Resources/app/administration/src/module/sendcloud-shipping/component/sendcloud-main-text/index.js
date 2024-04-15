import template from './sendcloud-main-text.html.twig';
import './sendcloud-main-text.scss';

const { Component } = Shopware;

Component.register('sendcloud-main-text', {
    template,

    props: {
        sendcloudMainText: {
            type: String,
            required: true,
            default: ''
        },
    }
});