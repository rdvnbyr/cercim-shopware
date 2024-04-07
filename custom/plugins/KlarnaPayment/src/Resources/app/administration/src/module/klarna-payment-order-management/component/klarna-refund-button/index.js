import template from './klarna-refund-button.html.twig';
import './klarna-refund-button.scss';

const { Component, Mixin } = Shopware;

Component.register('klarna-refund-button', {
    template,

    mixins: [
        Mixin.getByName('notification')
    ],

    inject: ['KlarnaPaymentOrderService'],

    props: {
        klarnaOrder: {
            type: Object,
            required: true
        }
    },

    data() {
        return {
            isLoading: false,
            hasError: false,
            showRefundModal: false,
            isRefundSuccessful: false,
            selection: [],
            refundAmount: 0.0,
            description: ''
        };
    },

    computed: {
        remainingAmount() {
            return this.klarnaOrder.captured_amount - this.klarnaOrder.refunded_amount;
        },

        buttonEnabled() {
            if (this.remainingAmount <= 0) {
                return false;
            }
            if (this.klarnaOrder.order_status === 'CANCELLED') {
                return false;
            }
            if (this.klarnaOrder.captured_amount <= 0) {
                return false;
            }

            return true;
        },

        maxRefundAmount() {
            return this.remainingAmount;
        },

        minRefundValue() {
            return 0.01;
        }
    },

    methods: {
        openRefundModal() {
            this.showRefundModal = true;
            this.isRefundSuccessful = false;

            this.refundAmount = this.remainingAmount;
            this.description = '';
            this.selection = [];
        },

        calculateRefundAmount() {
            let amount = 0;

            this.selection.forEach((selection) => {
                if (selection.selected) {
                    amount += (selection.unit_price / 100) * selection.quantity;
                }
            });

            if (amount === 0 || amount > this.remainingAmount) {
                amount = this.remainingAmount;
            }

            this.refundAmount = amount;
        },

        closeRefundModal() {
            this.showRefundModal = false;
        },

        onRefundFinished() {
            this.isRefundSuccessful = false;
        },

        refundOrder() {
            this.isLoading = true;

            const orderLines = [];

            this.selection.forEach((selection) => {
                this.klarnaOrder.order_lines.forEach((orderItem) => {
                    if (orderItem.reference === selection.reference && selection.selected && selection.quantity > 0) {
                        const copy = { ...orderItem };

                        copy.quantity = selection.quantity;
                        copy.total_amount = copy.unit_price * copy.quantity;

                        const taxRate = copy.tax_rate / 100;

                        copy.total_tax_amount = Math.round(copy.total_amount / (100 + taxRate) * taxRate);

                        orderLines.push(copy);
                    }
                });
            });

            const request = {
                orderTransactionId: this.klarnaOrder.orderTransactionId,
                klarna_order_id: this.klarnaOrder.order_id,
                salesChannel: this.klarnaOrder.salesChannel,
                refundAmount: this.refundAmount,
                description: this.description,
                orderLines: JSON.stringify(orderLines),
                complete: this.refundAmount === this.maxRefundAmount
            };

            this.KlarnaPaymentOrderService.refundOrder(request).then(() => {
                this.createNotificationSuccess({
                    title: this.$tc('klarna-payment-order-management.messages.refundSuccessTitle'),
                    message: this.$tc('klarna-payment-order-management.messages.refundSuccessMessage')
                });

                this.isRefundSuccessful = true;
            }).catch(() => {
                this.createNotificationError({
                    title: this.$tc('klarna-payment-order-management.messages.refundErrorTitle'),
                    message: this.$tc('klarna-payment-order-management.messages.refundErrorMessage')
                });

                this.isRefundSuccessful = false;
            }).finally(() => {
                this.$emit('reload');

                this.isLoading = false;
                this.showRefundModal = false;
            });
        },

        onSelectItem(reference, selected) {
            if (this.selection.length === 0) {
                this._populateSelectionProperty();
            }

            this.selection.forEach((selection) => {
                if (selection.reference === reference) {
                    selection.selected = selected;
                }
            });

            this.calculateRefundAmount();
        },

        onChangeQuantity(reference, quantity) {
            if (this.selection.length === 0) {
                this._populateSelectionProperty();
            }

            this.selection.forEach((selection) => {
                if (selection.reference === reference) {
                    selection.quantity = quantity;
                }
            });

            this.calculateRefundAmount();
        },

        onChangeDescription(description) {
            const maxChars = 255;

            if (description.length >= maxChars) {
                this.description = description.substr(0, maxChars);
            } else {
                this.description = description;
            }
        },

        _populateSelectionProperty() {
            this.klarnaOrder.order_lines.forEach((orderItem) => {
                let quantity = orderItem.quantity;

                if (orderItem.captured_quantity > 0) {
                    quantity = orderItem.captured_quantity;
                }

                this.selection.push({
                    quantity: quantity - orderItem.refunded_quantity,
                    reference: orderItem.reference,
                    unit_price: orderItem.unit_price,
                    selected: false
                });
            });
        }
    }
});
