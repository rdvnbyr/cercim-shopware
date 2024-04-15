import template from './sendcloud-welcome.html.twig';

const {Component} = Shopware;

Component.register('sendcloud-welcome', {
    template,

    inject: [
        'sendcloudService'
    ],

    data() {
        return {
            isLoading: false,
            isMenuExpanded: true
        };
    },

    created: function () {
        this.init();
    },

    methods: {
        startAuthProcess: function () {
            return this.sendcloudService.getRedirectUrl()
                .then((apiResponse) => {
                    this.redirectToConnectionScreenAndStartChecking(apiResponse.redirectUrl);
                }).catch(error => {
                    console.log(error);
                });
        },

        redirectToConnectionScreenAndStartChecking: function (redirectUrl) {
            this.isLoading = true;
            var win = window.open(redirectUrl, '_blank');
            win.focus();
            this.checkStatus();
        },

        checkStatus: function () {
            this.sendcloudService.checkConnectionStatus()
                .then((apiResponse) => {
                    if (apiResponse.isConnected) {
                        window.location.reload();
                    } else {
                        var handler = this.checkStatus;
                        setTimeout(handler, 250);
                    }
                }).catch(error => {
                console.log(error);
            });
        },

        init() {
            let scLoader;
            let adminMenu;
            let toggleAdminMenuButton = document.getElementsByClassName('sw-admin-menu__toggle')[0];

            if (!toggleAdminMenuButton) {
                return;
            }

            toggleAdminMenuButton.addEventListener('click', function () {
                adminMenu = document.getElementsByClassName('sw-admin-menu')[0];
                scLoader = document.getElementsByClassName('sw-loader');

                if (!scLoader || !scLoader[0] || !adminMenu) {
                    return;
                }
                this.isMenuExpanded = !(adminMenu && !adminMenu.classList.contains('is--expanded'));
                scLoader[0].classList.toggle('sc-loader', this.isMenuExpanded);
            });
        }
    }
});