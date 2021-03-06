import 'https://cdn.bootcss.com/vue/2.6.10/vue.esm.browser.min.js';

export class main {
    constructor() {
    };

    axios(_params) {
        const instance = axios.create({
            baseURL: NICMS.api_uri,
            headers: {
                Accept: 'application/vnd.' + NICMS.api.root + '.v' + NICMS.api.version + '+json',
                Authorization: NICMS.api.authorization
            },
            timeout: 10000,
            responseType: 'json',
            responseEncoding: 'utf8',
        });
        return instance(_params);
    };
}
