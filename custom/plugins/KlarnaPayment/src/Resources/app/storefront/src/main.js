import KlarnaPayments from './klarna-payments/klarna-payments';
import KlarnaExpressButton from './klarna-payments/klarna-express-button';

window.PluginManager.register('KlarnaPayments', KlarnaPayments, '[data-is-klarna-payments]');
window.PluginManager.register('KlarnaExpressButton', KlarnaExpressButton, '[data-is-klarna-express-button]');

if (module.hot) {
    module.hot.accept();
}
