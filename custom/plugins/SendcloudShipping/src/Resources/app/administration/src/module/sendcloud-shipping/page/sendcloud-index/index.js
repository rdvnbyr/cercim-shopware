import template from './sendcloud-index.html.twig';

const {Component} = Shopware;

Component.register('sendcloud-index', {
    template,

    inject: [
        'sendcloudService'
    ],
    data() {
        return {
            isLoading: true
        };
    },

    mounted: function () {
        this.getCurrentRoute({});
    },

    watch: {
        $route(to, from) {
            let query = {};

            if (to.hasOwnProperty('query') && Object.keys(to.query).length > 0) {
                query = to.query;
            } else if (from.hasOwnProperty('query') && Object.keys(from.query).length > 0) {
                query = from.query;
            }

            this.getCurrentRoute(query);
        }
    },

    methods: {
        getCurrentRoute: function (query) {

            return this.sendcloudService.getCurrentRoute()
                .then((response) => {
                    this.isLoading = false;
                    let routeName = response.page;
                    let route = {
                        name: 'sendcloud.shipping.index',
                        params: {
                            page: routeName
                        },
                        query: query
                    };

                    if ((this.$route.name !== route.name || JSON.stringify(this.$route.params) !== JSON.stringify(route.params))
                        && window.location.hash.indexOf('sendcloud') !== -1) {
                        this.$router.replace(route);
                    }

                    this.isLoading = false;
                }).catch(error => {

                });
        }
    }
});
