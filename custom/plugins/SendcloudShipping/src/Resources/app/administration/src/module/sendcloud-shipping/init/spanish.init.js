const { Application } = Shopware;

Application.addInitializerDecorator('locale', (localeFactory) => {
    const locale = 'es-ES';
    const context = require.context('./../snippet/', true, /(?<!\w+\.)es-ES\.json/);

    localeFactory.register(locale, {});
    context.keys().forEach((file) => {
        localeFactory.extend(locale, context(file));
    });

    return localeFactory;
});