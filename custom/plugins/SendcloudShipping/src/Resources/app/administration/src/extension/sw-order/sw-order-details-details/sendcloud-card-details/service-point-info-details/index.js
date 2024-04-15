import template from './service-point-info-details.html.twig';
import './service-point-info-details.scss';

const {Component} = Shopware;

Component.register('service-point-info-details', {
    template,

    inject: [
        'sendcloudService'
    ],

    props: {
        servicePointInfo: {
            type: Object,
            required: true,
            default() {
                return null;
            }
        },
        apiKey: {
            type: String,
            required: true,
            default() {
                return '';
            }
        },

        sendcloudScriptUrl: {
            type: String,
            required: true,
            default() {
                return '';
            }
        },

        linkLabel: {
            type: String,
            required: true,
            default() {
                return this.$tc('send-cloud.shipment.selectServicePoint');
            }
        },

        carriers: {
            type: String,
            required: true,
            default() {
                return '';
            }
        },

        order: {
            type: Object,
            required: true,
            default() {
                return {};
            }
        }
    },

    mounted: function () {
        let sendcloudSdkScript = document.createElement('script');
        sendcloudSdkScript.setAttribute('src', this.sendcloudScriptUrl);
        document.head.appendChild(sendcloudSdkScript);
    },

    methods: {
        openServicePointPicker: function () {
            var config = this.getConfigParameters();
            var me = this;
            sendcloud.servicePoints.open(
                config,
                function (servicePointObject) {
                    me.sendcloudService.saveShipping(me.order.orderNumber, servicePointObject)
                        .then((result) => {
                            if (result.success) {
                                me.servicePointInfo = servicePointObject;
                                me.linkLabel = me.$tc('send-cloud.shipment.changeServicePoint');
                            }
                        })
                        .catch(error => {});
                },
                function (errors) {
                    errors.forEach(function (error) {
                        console.log('Failure callback, reason: ' + error);
                    });
                }
            );
        },

        getConfigParameters: function () {
            let deliveries = this.order.deliveries;
            let zipCode = '';
            let language = 'en';
            let countryCode = '';
            if (Array.isArray(deliveries)) {
                let shippingAddress = deliveries[0].shippingOrderAddress;
                if (shippingAddress) {
                    zipCode = shippingAddress.zipcode;
                }

                let country = shippingAddress.country;
                if (country) {
                    countryCode = country.iso;
                }
            }

            return {
                'apiKey': this.apiKey ? this.apiKey : null,
                'country': countryCode,
                'postalCode': zipCode,
                'language': language,
                'carriers': this.carriers,
                'weight': '2'
            };
        }
    }
});