import template from './klarna-order-items.html.twig';

const { Component } = Shopware;
const { currency } = Shopware.Utils.format;

Component.register('klarna-order-items', {
    template,

    props: {
        klarnaOrder: {
            type: Object,
            required: true
        },

        mode: {
            type: String,
            required: false
        }
    },

    computed: {
        orderItems() {
            const data = [];

            this.klarnaOrder.order_lines.forEach((orderItem) => {
                const price = currency(
                    orderItem.total_amount / 100,
                    this.klarnaOrder.currency
                );

                let disabled = false;
                let quantity = orderItem.quantity;

                if (this.mode === 'refund' && orderItem.captured_quantity > 0) {
                    quantity = orderItem.captured_quantity;
                }

                if (this.mode === 'capture') {
                    quantity -= orderItem.captured_quantity;
                } else if (this.mode === 'refund') {
                    quantity -= orderItem.refunded_quantity;
                }

                if (quantity <= 0) {
                    disabled = true;
                }

                data.push({
                    id: orderItem.reference,
                    reference: orderItem.reference,
                    product: orderItem.name,
                    options: orderItem.options,
                    amount: quantity,
                    disabled: disabled,
                    price: price,
                    orderItem: orderItem
                });
            });

            return data;
        },

        orderItemColumns() {
            return [
                {
                    property: 'reference',
                    label: this.$tc('klarna-payment-order-management.modal.columns.reference'),
                    rawData: true
                },
                {
                    property: 'product',
                    label: this.$tc('klarna-payment-order-management.modal.columns.product'),
                    rawData: true
                },
                {
                    property: 'amount',
                    label: this.$tc('klarna-payment-order-management.modal.columns.amount'),
                    rawData: true
                },
                {
                    property: 'price',
                    label: this.$tc('klarna-payment-order-management.modal.columns.price'),
                    rawData: true
                }
            ];
        }
    },

    methods: {
        onSelectItem(selection, item, selected) {
            this.$emit('select-item', item.id, selected);
        },

        onChangeQuantity(value, reference) {
            this.$emit('change-quantity', reference, value);
        }
    }
});
