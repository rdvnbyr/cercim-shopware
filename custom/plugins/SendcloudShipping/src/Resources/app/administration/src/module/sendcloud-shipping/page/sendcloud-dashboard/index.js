import template from './sendcloud-dashboard.html.twig';
import '../../component/sendcloud-notification';
import '../sendcloud-customs-info'

const {Component} = Shopware;

Component.register('sendcloud-dashboard', {
    template,

    inject: [
        'sendcloudService'
    ],

    data() {
        return {
            isLoading: true,
            isServicePointEnabled: false,
            salesChannel: '',
            sendcloudUrl: '',
            activeTab: 'dashboard',

            shipmentTypes: [],
            countries: [],
            customFields: [],
            shipmentType: ''
        };
    },

    created: function () {
        this.getDashboardConfig();
    },

    methods: {
        getDashboardConfig: function () {

            return this.sendcloudService.getDashboardConfig()
                .then((configData) => {
                    this.isLoading = false;
                    this.isServicePointEnabled = configData.isServicePointEnabled;
                    this.salesChannel = configData.salesChannel;
                    this.sendcloudUrl = configData.sendcloudUrl;
                    this.shipmentTypes = configData.shipmentTypes;
                    this.countries = configData.countries;
                    this.customFields = configData.customFields;
                    this.shipmentType = configData.shipmentTypePreselectedValue;
                }).catch(error => {

                });
        },

        goToSendCloud: function () {
            var win = window.open(this.sendcloudUrl, '_blank');
            win.focus();
        },
    }
});