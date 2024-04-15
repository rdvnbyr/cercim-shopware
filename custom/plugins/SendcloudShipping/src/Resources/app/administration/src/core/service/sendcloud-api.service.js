const ApiService = Shopware.Classes.ApiService;

class SendcloudApiService extends ApiService {
    constructor(httpClient, loginService,) {
        super(httpClient, loginService, 'sendcloud');
        this.name = 'sendcloudService';
    }

    saveShipping(orderNumber, body) {
        return this.httpClient.post(
            `/sendcloud/shipment/save/${orderNumber}`,
            body,
            {headers: this.getBasicHeaders()}
        ).then((response) => {
            return ApiService.handleResponse(response);
        });
    }

    sendShipment(orderNumber) {
        return this.httpClient.get(
            `/sendcloud/shipment/${orderNumber}`,
            {headers: this.getBasicHeaders()}
        ).then((response) => {
            return ApiService.handleResponse(response);
        });
    }

    getCurrentRoute() {
        return this.httpClient.get(
            '/sendcloud/router',
            {headers: this.getBasicHeaders()}
        ).then((response) => {
            return ApiService.handleResponse(response);
        });
    }

    getDashboardConfig() {
        return this.httpClient.get(
            '/sendcloud/dashboard',
            {headers: this.getBasicHeaders()}
        ).then((response) => {
            return ApiService.handleResponse(response);
        });
    }

    getRedirectUrl() {
        return this.httpClient.get(
            '/sendcloud/redirectUrl',
            {headers: this.getBasicHeaders()}
        ).then((response) => {
            return ApiService.handleResponse(response);
        });
    }

    checkConnectionStatus() {
        return this.httpClient.get(
            '/sendcloud/connectionStatus',
            {headers: this.getBasicHeaders()}
        ).then((response) => {
            return ApiService.handleResponse(response);
        });
    }

    getCustomsInfo() {
        return this.httpClient.get(
            '/sendcloud/getcustoms',
            {headers: this.getBasicHeaders()}
        ).then((response) => {
            return ApiService.handleResponse(response);
        });
    }

    saveCustomsInfo(customInfoObject) {
        return this.httpClient.post(
            '/sendcloud/savecustoms',
            customInfoObject,
            {headers: this.getBasicHeaders()}
        ).then((response) => {
            return ApiService.handleResponse(response);
        });
    }
}

export default SendcloudApiService;