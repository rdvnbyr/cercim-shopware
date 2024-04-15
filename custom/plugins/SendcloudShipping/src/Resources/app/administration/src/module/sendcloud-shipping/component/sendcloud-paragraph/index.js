import template from './sendcloud-paragraph.html.twig'
import './sendcloud-paragraph.scss'

const { Component } = Shopware;

Component.register('sendcloud-paragraph', {
    template,

    props: {
        sendcloudParagraph: {
            type: String,
            required: true,
            default: ''
        },
    }
});