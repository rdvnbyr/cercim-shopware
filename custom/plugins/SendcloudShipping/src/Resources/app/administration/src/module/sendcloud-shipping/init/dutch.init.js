const { Application } = Shopware;

Application.addInitializerDecorator('locale', (localeFactory) => {
    const locale = 'nl-NL';
    const context = require.context('./../snippet/', true, /(?<!\w+\.)nl-NL\.json/);

    localeFactory.register(locale, {});
    context.keys().forEach((file) => {
        localeFactory.extend(locale, context(file));
    });

    return localeFactory;
});
