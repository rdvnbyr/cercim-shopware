import template from './klarna-payment-wizard.html.twig';
import './klarna-payment-wizard.scss';

const { Component, Mixin } = Shopware;

Component.register('klarna-payment-wizard', {
    template,

    mixins: [
        Mixin.getByName('notification'),
        Mixin.getByName('sw-inline-snippet')
    ],

    inject: ['KlarnaPaymentWizardService'],

    data() {
        return {
            isLoading: true,
            currentSalesChannelId: null,
            splitBreakpoint: 1024,
            minStep: 0,
            maxStep: 2,
            isInitialized: false,
            salesChannels: [],
            isMobile: null,
            step: 0,
            stepVariant: 'info'
        };
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.checkViewport();
            this.registerListener();
            this.loadTableData();
        },

        loadTableData() {
            this.KlarnaPaymentWizardService.fetchData().then((response) => {
                this.salesChannels = response.data.salesChannels;
                this.isInitialized = response.data.isInitialized;
            }).catch(() => {
                this.createErrorNotification({
                    title: this.$tc('klarna-payment-configuration.wizard.messages.titleError'),
                    message: this.$tc('klarna-payment-configuration.wizard.messages.messageLoadError')
                });
            }).finally(() => {
                this.isLoading = false;
            });
        },

        registerListener() {
            this.$device.onResize({
                listener: this.checkViewport.bind(this)
            });
        },

        checkViewport() {
            this.isMobile = this.$device.getViewportWidth() < this.splitBreakpoint;
        },

        onNext() {
            this.step = this.step + 1;

            if (this.step > this.maxStep) {
                this.step = this.maxStep;
            }
        },

        onBack() {
            this.step = this.step - 1;

            if (this.step < this.minStep) {
                this.step = this.minStep;
            }
        },

        onFinish() {
            this.isLoading = true;

            this.KlarnaPaymentWizardService.finalizeInstallation(this.salesChannels).then(() => {
                this.createNotificationSuccess({
                    title: this.$tc('klarna-payment-configuration.wizard.messages.titleSuccess'),
                    message: this.$tc('klarna-payment-configuration.wizard.messages.messageSaveSuccess')
                });

                this.$router.push({ name: 'klarna.payment.configuration.settings' });
            }).catch(() => {
                this.createErrorNotification({
                    title: this.$tc('klarna-payment-configuration.wizard.messages.titleError'),
                    message: this.$tc('klarna-payment-configuration.wizard.messages.messageSaveError')
                });
            }).finally(() => {
                this.isLoading = false;
            });
        },

        onSalesChannelChanged(salesChannelId) {
            this.currentSalesChannelId = salesChannelId;
        }
    }
});
