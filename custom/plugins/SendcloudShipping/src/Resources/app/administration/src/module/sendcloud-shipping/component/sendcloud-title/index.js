import template from './sendcloud-title.html.twig';
import './sendcloud-title.scss';

const { Component } = Shopware;

Component.register('sendcloud-title', {
    template,

    props: {
        sendcloudTitle: {
            type: String,
            required: true,
            default: ''
        },
    }
});