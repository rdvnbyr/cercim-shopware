const { Application } = Shopware;
const ApiService = Shopware.Classes.ApiService;

class KlarnaPaymentWizardService extends ApiService {
    constructor(httpClient, loginService, apiEndpoint = 'klarna_payment') {
        super(httpClient, loginService, apiEndpoint);
    }

    finalizeInstallation(tableData) {
        return this.httpClient
            .post(
                `_action/${this.getApiBasePath()}/finalize_installation`,
                {
                    tableData: JSON.stringify(tableData)
                },
                {
                    headers: this.getBasicHeaders()
                }
            )
            .then((response) => {
                return ApiService.handleResponse(response);
            });
    }

    fetchData() {
        return this.httpClient
            .get(`_action/${this.getApiBasePath()}/fetch_data`,
                {
                    headers: this.getBasicHeaders()
                })
            .then((response) => {
                return ApiService.handleResponse(response);
            });
    }
}

Application.addServiceProvider('KlarnaPaymentWizardService', (container) => {
    const initContainer = Application.getContainer('init');

    return new KlarnaPaymentWizardService(initContainer.httpClient, container.loginService);
});

