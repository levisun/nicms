import 'https://cdn.bootcss.com/vue/2.6.10/vue.esm.browser.min.js';

export class main {
    constructor() {
    };

    axios(_params) {
        const instance = axios.create({
            baseURL: APP_CONFIG.api_uri,
            headers: {
                Accept: 'application/vnd.' + APP_CONFIG.api.root + '.v' + APP_CONFIG.api.version + '+json',
                Authorization: APP_CONFIG.api.authorization
            },
            timeout: 10000,
            responseType: 'json',
            responseEncoding: 'utf8',
        });
        return instance(_params);
    };
}
