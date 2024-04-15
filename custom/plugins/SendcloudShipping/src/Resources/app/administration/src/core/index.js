import SendcloudApiService from './service/sendcloud-api.service';

const initContainer = Shopware.Application.getContainer('init');

Shopware.Application.addServiceProvider('sendcloudService', (container) => {
    return new SendcloudApiService(initContainer.httpClient, container.loginService);
});