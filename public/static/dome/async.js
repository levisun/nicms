
class async {

    options = {
        type: "GET",                // 请求类型
        url: null,                  // 请求地址
        async: true,                // 异步开关,默认异步请求
        cache: false,               // 缓存
        username: null,
        password: null,

        responseType: "json",

        push: false,                        // 添加历史记录
        replace: false,                     // 替换历史记录
        requestUrl: null,                   // 重写地址
        scrollTo: false,                    // 是否回到顶部 可定义顶部像素

        header: [],
    };

    xhr = null;

    constructor(options) {
        this.options.header = this.header();
        this.options = new extend(this.options, options);

        console.log(this.options);
        console.log(this.timestamp());
        this._initRequest();

        return {

        };
    }



    _initRequest() {
        this.xhr = new window.XMLHttpRequest();

        if (false === this.options.cache) {
            this.options.url += -1 === this.options.url.indexOf("?") ? '?_=' + this.timestamp() : '&_=' + this.timestamp();
        }

        this.xhr.open(
            this.options.type,
            this.options.url,
            this.options.async,
            this.options.username,
            this.options.password,
        );

        if (this.options.responseType) {
            this.xhr.overrideMimeType(this.options.responseType);
        }

        if (this.options.mimeType && this.xhr.overrideMimeType) {
            this.xhr.overrideMimeType(this.options.mimeType);
        }

        for (let index = 0; index < this.options.header.length; index++) {
            const element = this.options.header[index];
            this.xhr.setRequestHeader(element.name, element.value);
        }
    }

    _sendRequest() {

        this.xhr.send(this.options.data);
    }

    timestamp() {
        return Date.parse(new Date()) / 1000;
    }

    header() {
        let root = window.location.host.substr(window.location.host.indexOf(".") + 1);
        root = root.substr(0, root.indexOf("."));
        return [
            { name: "Accept", value: "application/vnd." + root + ".v" + document.getElementsByName("csrf-version")[0].content + "+json" },
            { name: "Authorization", value: "Bearer " },
        ];
    }
}
