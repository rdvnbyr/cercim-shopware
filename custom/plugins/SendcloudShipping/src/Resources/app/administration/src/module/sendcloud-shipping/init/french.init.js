const { Application } = Shopware;

Application.addInitializerDecorator('locale', (localeFactory) => {
    const locale = 'fr-FR';
    const context = require.context('./../snippet/', true, /(?<!\w+\.)fr-FR\.json/);

    localeFactory.register(locale, {});
    context.keys().forEach((file) => {
        localeFactory.extend(locale, context(file));
    });

    return localeFactory;
});
