import template from './sendcloud-container.html.twig';
import './sendcloud-container.scss';

const {Component} = Shopware;

Component.register('sendcloud-container', {
    template,

    data() {
        return {
            isExpanded: true
        }
    },

    created: function () {
        this.init();
    },

    methods: {
        init() {
            let scContainer;
            let adminMenu = document.getElementsByClassName('sw-admin-menu')[0];
            let toggleAdminMenuButton = document.getElementsByClassName('sw-admin-menu__toggle')[0];

            if (!adminMenu || !toggleAdminMenuButton) {
                return;
            }

            toggleAdminMenuButton.addEventListener('click', function () {
                scContainer = document.getElementsByClassName('sendcloud-container');

                if (!scContainer || !scContainer[0]) {
                    return;
                }
                this.isExpanded = !(adminMenu && !adminMenu.classList.contains('is--expanded'));
                scContainer[0].classList.toggle('adjust-width', this.isExpanded);
            });
        }
    }
});
