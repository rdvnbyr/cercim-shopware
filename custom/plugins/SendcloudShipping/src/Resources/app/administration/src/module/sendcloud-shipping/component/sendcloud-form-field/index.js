import template from './sendcloud-form-field.html.twig';
import './sendcloud-form-field.scss';

const {Component} = Shopware;

Component.register('sendcloud-form-field', {
    template,

    props: {
        sendcloudFieldName: {
            type: String,
            required: true,
            default: ''
        },
        sendcloudFieldType: {
            type: String,
            required: true,
            default: ''
        },
        sendcloudFieldLabel: {
            type: String,
            required: true,
            default: ''
        },
        sendcloudFieldValue: {
            type: String,
            required: false,
            default: ''
        },
        sendcloudFieldModel: {
            type: String,
            required: false,
            default: ''
        },
        sendcloudFieldPlaceholder: {
            type: String,
            required: false,
            default: ''
        },
        sendcloudHelpText: {
            type: String,
            required: false,
            default: ''
        }
    }
});