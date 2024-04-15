import './service-point-info-details';
import template from './sendcloud-card-details.html.twig';

const { Component } = Shopware;

Component.register('sendcloud-card-details', {
    template,

    inject: [
        'sendcloudService'
    ],

    data() {
        return {
            showSendCloudCard: false,
            isLoading: true,
            orderStatus: '',
            trackingNumber: '',
            showTrackingNumber: false,
            trackingUrl: '',
            carriers: '',
            apiKey: '',
            servicePointInfo: null,
            linkLabel: this.$tc('send-cloud.shipment.selectServicePoint'),
            sendcloudScriptUrl: ''
        };
    },

    props: {
        order: {
            type: Object,
            required: true,
            default() {
                return {};
            }
        }
    },

    created: function() {
        this.fetchShipmentInfo();
    },

    updated: function() {
        this.fetchShipmentInfo();
    },

    methods: {
        fetchShipmentInfo: function () {
            return this.sendcloudService.sendShipment(this.order.orderNumber)
                .then((shipmentInfo) => {
                    this.isLoading = false;
                    this.orderStatus = shipmentInfo.status ? shipmentInfo.status : this.$tc('send-cloud.shipment.emptyStatusMessage');
                    this.trackingNumber = shipmentInfo.trackingNumber;
                    this.trackingUrl = shipmentInfo.trackingUrl;
                    this.showTrackingNumber = !!shipmentInfo.trackingNumber;
                    this.apiKey = shipmentInfo.apiKey;
                    this.showSendCloudCard = (this.apiKey.length > 0);
                    this.carriers = shipmentInfo.carriers;
                    this.sendcloudScriptUrl = shipmentInfo.sendcloudScriptUrl;
                    this.servicePointInfo = JSON.parse(shipmentInfo.servicePointInfo);
                    if (this.servicePointInfo.id) {
                        this.linkLabel = this.$tc('send-cloud.shipment.changeServicePoint');
                    }
                }).catch(error => {
                });
        }
    }
});