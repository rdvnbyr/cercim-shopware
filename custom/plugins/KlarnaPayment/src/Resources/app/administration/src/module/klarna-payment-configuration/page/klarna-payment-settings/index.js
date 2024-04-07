import templateDefault from './klarna-payment-settings.html.twig';
import templateWithInheritance from './klarna-payment-settings-with-inheritance.html.twig';
import './klarna-payment-settings.scss';

const { Component, Mixin, Context } = Shopware;
const { Criteria } = Shopware.Data;

const version = Context.app.config.version;
const match = version.match(/((\d+)\.?(\d+?)\.?(\d+)?\.?(\d*))-?([A-z]+?\d+)?/i);

let template = templateDefault;

// Version has to be higher than 6.3.4.x for using inheritance
if (match && parseInt(match[2], 10) === 6 && (parseInt(match[3], 10) > 3 || parseInt(match[4], 10) > 4)) {
    template = templateWithInheritance;
}

Component.register('klarna-payment-settings', {
    template,

    mixins: [
        Mixin.getByName('notification'),
        Mixin.getByName('sw-inline-snippet')
    ],

    inject: [
        'repositoryFactory',
        'KlarnaPaymentConfigurationService'
    ],

    data() {
        return {
            euApiRegionKey: '',
            usApiRegionKey: '',
            apiRegionKeys: [],
            isLoading: false,
            isTesting: false,
            isTestSuccessful: false,
            isSaveSuccessful: false,
            config: {},
            paymentMethods: [],
            externalCheckoutPaymentMethods: [],
            configDomain: 'KlarnaPayment.settings.',
            salesChannelDomainsWithoutHttps: [],
            showNotificationGlobalPurchaseFlowMissing: false,
        };
    },

    metaInfo() {
        return {
            title: this.$createTitle()
        };
    },

    created() {
        this.createdComponent();
    },

    computed: {
        paymentMethodRepository() {
            return this.repositoryFactory.create('payment_method');
        },

        salesChannelDomainRepository() {
            return this.repositoryFactory.create('sales_channel_domain');
        },

        arrowIconName() {
            if (match[3] >= 5) {
                return 'regular-chevron-right-xs';
            }

            return 'small-arrow-medium-right';
        }
    },

    methods: {
        createdComponent() {
            const me = this;

            this.setSalesChannelDomainsWithoutHttps();

            this.paymentMethodRepository.search(new Criteria(), Context.api).then((searchResult) => {
                searchResult.forEach(((paymentMethod) => {
                    me.paymentMethods.push({
                        value: paymentMethod.id,
                        label: paymentMethod.name
                    });

                    if (paymentMethod.formattedHandlerIdentifier === 'handler_swag_paypalpaymenthandler') {
                        me.externalCheckoutPaymentMethods.push({
                            value: paymentMethod.id,
                            label: paymentMethod.name
                        });
                    }
                }));
            });

            this.KlarnaPaymentConfigurationService.getEuApiRegionKey().then(response => {
                me.euApiRegionKey = response.data.key;
                me.apiRegionKeys.push(me.euApiRegionKey);
            });

            this.KlarnaPaymentConfigurationService.getUsApiRegionKey().then(response => {
                me.usApiRegionKey = response.data.key;
                me.apiRegionKeys.push(me.usApiRegionKey);
            });
        },

        setSalesChannelDomainsWithoutHttps() {
            const me = this;

            let criteria = new Criteria();
            criteria.addFilter(Criteria.not('AND', [Criteria.contains('url', 'https://')]));

            if (this.$refs.systemConfig && this.$refs.systemConfig.currentSalesChannelId) {
                criteria.addFilter(Criteria.equals('salesChannelId', this.$refs.systemConfig.currentSalesChannelId));
            }

            me.salesChannelDomainsWithoutHttps = [];

            this.salesChannelDomainRepository.search(criteria, Context.api).then((searchResult) => {
                searchResult.forEach(((salesChannelDomain) => {
                    me.salesChannelDomainsWithoutHttps.push(salesChannelDomain.url);
                }));
            });
        },

        getConfigValue(field) {
            if (this.$refs.systemConfig === undefined) {
                return null;
            }

            const salesChannelId  = this.$refs.systemConfig.currentSalesChannelId;
            const inheritedConfig = this.$refs.systemConfig.actualConfigData.null[this.configDomain + field];

            if (salesChannelId === null) {
                // switching into any salesChannel and back does not overwrite config, so we just use inheritedConfig
                return inheritedConfig;
            }

            const config = this.config[this.configDomain + field];

            if (config === null || config === undefined) {
                return inheritedConfig;
            }

            return config;
        },

        onTest() {
            this.testCredentials(false);
        },

        testCredentials(isSaveMode) {
            const me = this;

            let possibleFailures = me.apiRegionKeys.length;

            me.apiRegionKeys.forEach((endpoint) => {
                if (isSaveMode) {
                    this.isSaveSuccessful = false;
                    this.isLoading = true;
                } else {
                    me.isTestSuccessful = false;
                    me.isTesting = true;
                }

                const credentials = {
                    testMode: me.getConfigValue('testMode'),
                    salesChannel: me.$refs.systemConfig.currentSalesChannelId
                };

                let regionPostfix = (endpoint === me.usApiRegionKey ? 'US' : '');
                credentials.apiUsername = me.getConfigValue('apiUsername' + regionPostfix);
                credentials.apiPassword = me.getConfigValue('apiPassword' + regionPostfix);
                credentials.testApiUsername = me.getConfigValue('testApiUsername' + regionPostfix);
                credentials.testApiPassword = me.getConfigValue('testApiPassword' + regionPostfix);

                if (!me.shouldTestCredentials(credentials)) {
                    --possibleFailures;

                    me.releaseLoadingAndSave(isSaveMode, possibleFailures);

                    return;
                }

                me.KlarnaPaymentConfigurationService.validateCredentials(credentials, endpoint).then(() => {
                    me.createNotificationSuccess({
                        title: me.$tc('klarna-payment-configuration.settingsForm.messages.titleSuccess'),
                        message: me.$tc('klarna-payment-configuration.settingsForm.messages.messageTest' + endpoint + 'Success')
                    });

                    if (isSaveMode) {
                        me.isSaveSuccessful = true;
                    } else {
                        me.isTestSuccessful = true;
                    }

                    --possibleFailures;
                }).catch(() => {
                    me.createNotificationError({
                        title: me.$tc('klarna-payment-configuration.settingsForm.messages.titleError'),
                        message: me.$tc('klarna-payment-configuration.settingsForm.messages.messageTest' + endpoint + 'Error' + (credentials.testMode ? 'Test' : 'Live'))
                    });
                }).finally(() => {
                    me.releaseLoadingAndSave(isSaveMode, possibleFailures);
                });
            });
        },

        releaseLoadingAndSave(isSaveMode, possibleFailures) {
            const me = this;

            if (isSaveMode) {
                me.isLoading = false;

                if (possibleFailures <= 0) {
                    me.$refs.systemConfig.saveAll();
                }
            } else {
                me.isTesting = false;
            }
        },

        shouldTestCredentials(data) {
            if (data.testMode) {
                return (data.testApiPassword && data.testApiPassword.length > 0)
                    || (data.testApiUsername && data.testApiUsername.length > 0);
            }

            return (data.apiPassword && data.apiPassword.length > 0)
                || (data.apiUsername && data.apiUsername.length > 0);
        },

        onSave() {
            this.testCredentials(true);
        },

        onConfigChange(config) {
            this.config = config;

            this.showNotificationGlobalPurchaseFlowMissing = !this.getConfigValue('activeGlobalPurchaseFlow');

            this.redirectToWizard();
        },

        redirectToWizard() {
            const isInitialized = this.getConfigValue('isInitialized');

            if (!isInitialized) {
                this.$router.push({ name: 'klarna.payment.configuration.wizard' });
            }
        },

        onSaveFinished() {
            this.isSaveSuccessful = false;
        },

        onTestFinished() {
            this.isTestSuccessful = false;
        },

        getBind(element, config) {
            if (config !== this.config) {
                this.config = config;
            }

            return element;
        },

        onWizard() {
            this.$router.push({ name: 'klarna.payment.configuration.wizard' });
        },

        /**
         * TODO: Depending on the klarnaType (checkout or payments) fields could be filtered via their name
         */
        displayField(element, config) {
            if (element.name === `${this.configDomain}isInitialized`) {
                return false;
            }

            if (element.name === `${this.configDomain}activeGlobalPurchaseFlow`) {
                return false;
            }

            if (element.name === `${this.configDomain}onsiteMessagingScript`) {
                // "== null" also matches undefined
                if (config[`${this.configDomain}isOnsiteMessagingActive`] == null) {
                    return this.getConfigValue('isOnsiteMessagingActive') === true;
                }

                return config[`${this.configDomain}isOnsiteMessagingActive`] === true;
            }
            if (element.name === `${this.configDomain}onsiteMessagingSnippet`) {
                // "== null" also matches undefined
                if (config[`${this.configDomain}isOnsiteMessagingActive`] == null) {
                    return this.getConfigValue('isOnsiteMessagingActive') === true;
                }

                return config[`${this.configDomain}isOnsiteMessagingActive`] === true;
            }

            if (element.name.replace(this.configDomain, '').indexOf('klarnaExpress') === 0) {
                // "== null" also matches undefined
                if (config[`${this.configDomain}isKlarnaExpressActive`] == null) {
                    return this.getConfigValue('isKlarnaExpressActive') === true;
                }

                return config[`${this.configDomain}isKlarnaExpressActive`] === true;
            }

            if (element.name === `${this.configDomain}captureOrderStatus`) {
                return this.getConfigValue('automaticCapture') === 'orderStatus';
            }
            if (element.name === `${this.configDomain}captureDeliveryStatus`) {
                return this.getConfigValue('automaticCapture') === 'deliveryStatus';
            }

            if (element.name === `${this.configDomain}refundOrderStatus`) {
                return this.getConfigValue('automaticRefund') === 'orderStatus';
            }
            if (element.name === `${this.configDomain}refundDeliveryStatus`) {
                return this.getConfigValue('automaticRefund') === 'deliveryStatus';
            }

            if (element.name === `${this.configDomain}newsletterCheckboxLabel`) {
                return config[`${this.configDomain}enableNewsletterCheckbox`];
            }
            if (element.name === `${this.configDomain}accountCheckboxLabel`) {
                return config[`${this.configDomain}enableAccountCheckbox`];
            }

            if (element.name === `${this.configDomain}kcoFooterBadgeStyle`) {
                return config[`${this.configDomain}kcoDisplayFooterBadge`];
            }
            if (element.name === `${this.configDomain}kcoFooterBadgeCountryCode`) {
                return config[`${this.configDomain}kcoDisplayFooterBadge`];
            }
            if (element.name === `${this.configDomain}kcoFooterBadgeWidth`) {
                return config[`${this.configDomain}kcoDisplayFooterBadge`];
            }

            return true;
        },
    }
});
