const { Component } = Shopware;

Component.override('sw-system-config', {
    computed: {
        typesWithMapInheritanceSupport() {
            let types = this.$super('typesWithMapInheritanceSupport');

            if (this.domain === 'KlarnaPayment.settings') {
                types.push('single-select');
                types.push('multi-select');
            }

            return types;
        }
    },

    methods: {
        onSalesChannelChanged(salesChannelId) {
            this.$super('onSalesChannelChanged', salesChannelId);

            this.$emit('saleschannel-changed', this.currentSalesChannelId);
        },

        hasMapInheritanceSupport(element) {
            const customComponentNames = [
                'klarna-select-salutation',
                'klarna-select-delivery-state',
                'klarna-select-order-state',
                'klarna-select-payment-codes'
            ];

            const componentName = element.config ? element.config.componentName : undefined;

            if (customComponentNames.includes(componentName)) {
                return true;
            }

            return this.$super('hasMapInheritanceSupport', element);
        }
    }
});

