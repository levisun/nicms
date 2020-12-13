
class async {

    options = {
        type: "GET",                // 请求类型
        url: null,                  // 请求地址
        async: true,                // 异步开关,默认异步请求
        cache: false,               // 缓存
        username: null,
        password: null,

        responseType: "json",

        push: false,                // 添加历史记录
        replace: false,             // 替换历史记录
        requestUrl: null,           // 重写地址

        header: [],
        data: {},
        success: function () { },
        error: function () { },
    };

    xhr = null;

    constructor(options) {
        this.options.header = this.header();
        this.options = this.extend(this.options, options);
        this._initRequest();
        this._sendRequest();

        return {

        };
    }



    _initRequest() {
        this.xhr = new window.XMLHttpRequest();

        if ("get" === this.options.type.toLowerCase()) {
            for (let index in this.options.data) {
                if ("Array" === Object.prototype.toString.call(this.options.data[index]).slice(8, -1)) {
                    console.log(1);
                } else if ("Object" === Object.prototype.toString.call(this.options.data[index]).slice(8, -1)) {
                    this.options.url += -1 === this.options.url.indexOf("?")
                        ? "?" + this.options.data[index]["name"] + "=" + this.options.data[index]["value"]
                        : "&" + this.options.data[index]["name"] + "=" + this.options.data[index]["value"];
                } else {
                    this.options.url += -1 === this.options.url.indexOf("?")
                        ? "?" + index + "=" + this.options.data[index]
                        : "&" + index + "=" + this.options.data[index];
                }
            }
        }

        if (false === this.options.cache) {
            this.options.url += -1 === this.options.url.indexOf("?") ? "?_=" + this.timestamp() : "&_=" + this.timestamp();
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

        for (let index in this.options.header) {
            if ("Object" === Object.prototype.toString.call(this.options.data[index]).slice(8, -1)) {
                this.xhr.setRequestHeader(this.options.header[index].name, this.options.header[index].value);
            } else {
                this.xhr.setRequestHeader(index, this.options.header[index]);
            }
        }

        this.xhr.addEventListener("load", () => {
            if (400 > this.xhr.status && this.options.requestUrl) {
                window.history.pushState(null, document.title, this.options.requestUrl);

                this.xhr.response = "json" === this.options.responseType
                    ? JSON.parse(this.xhr.response)
                    : this.xhr.response;

                this.options.success(this.xhr.response);
            } else {
                this.options.error(this.xhr.response);
            }
        });
    }

    _sendRequest() {
        if ("get" === this.options.type.toLowerCase()) {
            this.xhr.send();
        } else {
            this.xhr.send(this.options.data);
        }
    }

    timestamp() {
        return Date.parse(new Date()) / 1000;
    }

    header() {
        let root = window.location.host.substr(window.location.host.indexOf(".") + 1);
        root = root.substr(0, root.indexOf("."));
        return {
            Accept: "application/vnd." + root + ".v" + document.getElementsByName("csrf-version")[0].content + "+json",
            Authorization: "Bearer " + window.atob(window.localStorage.getItem('XSRF_AUTHORIZATION')),
        };
    }

    extend(destination, source) {

        for (let property in source) {
            if ("Array" === Object.prototype.toString.call(source[property]).slice(8, -1)) {
                destination[property] = JSON.parse(JSON.stringify(source[property]));
            } else if ("Object" === Object.prototype.toString.call(source[property]).slice(8, -1)) {
                // 递归
                destination[property] = this.extend([], source[property]);
            } else if ("Boolean" === Object.prototype.toString.call(source[property]).slice(8, -1)) {
                destination[property] = source[property];
            } else if ("Null" === Object.prototype.toString.call(source[property]).slice(8, -1)) {
                destination[property] = source[property];
            } else {
                destination[property] = source[property];
            }
        }

        return destination;
    }
}
