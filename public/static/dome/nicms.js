
class nicms {

    async_default_options = {
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

    constructor() {
    }

    // 异步请求
    async(options) {
        let root = window.location.host.substr(window.location.host.indexOf(".") + 1);
        root = root.substr(0, root.indexOf("."));
        options.header = {
            Accept: "application/vnd." + root + ".v" + document.getElementsByName("csrf-version")[0].content + "+json",
            Authorization: "Bearer " + window.atob(window.localStorage.getItem('XSRF_AUTHORIZATION')),
        };
        options = this.extend(this.async_default_options, options);

        this.xhr = new window.XMLHttpRequest();
        if ("get" === options.type.toLowerCase()) {
            for (let index in options.data) {
                if ("Array" === Object.prototype.toString.call(options.data[index]).slice(8, -1)) {
                    console.log(1);
                } else if ("Object" === Object.prototype.toString.call(options.data[index]).slice(8, -1)) {
                    options.url += -1 === options.url.indexOf("?")
                        ? "?" + options.data[index]["name"] + "=" + options.data[index]["value"]
                        : "&" + options.data[index]["name"] + "=" + options.data[index]["value"];
                } else {
                    options.url += -1 === options.url.indexOf("?")
                        ? "?" + index + "=" + options.data[index]
                        : "&" + index + "=" + options.data[index];
                }
            }
        }

        if (false === options.cache) {
            options.url += -1 === options.url.indexOf("?") ? "?_=" + this.timestamp() : "&_=" + this.timestamp();
        }

        this.xhr.open(
            options.type,
            options.url,
            options.async,
            options.username,
            options.password,
        );

        if (options.responseType) {
            this.xhr.overrideMimeType(options.responseType);
        }

        if (options.mimeType && this.xhr.overrideMimeType) {
            this.xhr.overrideMimeType(options.mimeType);
        }

        for (let index in options.header) {
            if ("Object" === Object.prototype.toString.call(options.data[index]).slice(8, -1)) {
                this.xhr.setRequestHeader(options.header[index].name, options.header[index].value);
            } else {
                this.xhr.setRequestHeader(index, options.header[index]);
            }
        }

        this.xhr.addEventListener("load", () => {
            if (400 > this.xhr.status && options.requestUrl) {
                window.history.pushState(null, document.title, options.requestUrl);

                this.xhr.response = "json" === options.responseType
                    ? JSON.parse(this.xhr.response)
                    : this.xhr.response;

                options.success(this.xhr.response);
            } else {
                options.error(this.xhr.response);
            }
        });

        if ("get" === options.type.toLowerCase()) {
            this.xhr.send();
        } else {
            this.xhr.send(options.data);
        }
    }

    // 复制
    copy(content, message = "copy success") {
        let aux = document.createElement("input");
        aux.setAttribute("value", content);
        document.body.appendChild(aux);
        aux.select();
        document.execCommand("copy");
        document.body.removeChild(aux);
        alert(message);
    }

    /*
     cookie操作
     cookie(名称, 内容, 保存时间, 地址, 类型, 域名);
     设置: cookie(名称, 内容);
     读取: cookie(名称);
     移除: cookie(名称, null, -1);
     */
    cookie(name, value = "", expire = 0, path = "/", samesite = "lax", domain = "") {
        let cookie = "";

        if (name && value) {
            cookie = name + "=" + value + ";";
            cookie += expire
                ? "expires=" + this.timestamp() + expire + ";"
                : "";
            cookie += "path=" + path + ";";
            cookie += "SameSite=" + samesite + ";";
            cookie += domain ? domain : "." + this.rootDomain();
            document.cookie = cookie;
        } else if (name && 0 > expire) {
            cookie = name + "=" + null + ";";
            cookie += "expires=-1440;";
            cookie += "path=" + path + ";";
            cookie += "SameSite=" + samesite + ";";
            cookie += domain ? domain : "." + this.rootDomain();
            document.cookie = cookie;
        } else if (name) {
            name += "=";
            let decodedCookie = decodeURIComponent(document.cookie);
            let ca = decodedCookie.split(';');
            for (let i = 0; i < ca.length; i++) {
                let c = ca[i];
                while (c.charAt(0) == ' ') {
                    c = c.substring(1);
                }
                if (c.indexOf(name) == 0) {
                    return c.substring(name.length, c.length);
                }
            }
            return '';
        }
    }

    // 时间戳
    timestamp() {
        return Date.parse(new Date()) / 1000;
    }

    // 访问(请求地址)
    url(type = "href") {
        if ("hash" === type) {
            return window.location.hash;
        } else if ("origin" === type) {
            return window.location.origin;
        } else if ("path" === type) {
            return window.location.pathname;
        } else if ("protocol" === type) {
            return window.location.protocol;
        } else {
            return window.location.href;
        }
    }

    // 访问域名
    domain() {
        return window.location.host;
    }

    // 顶级域名
    rootDomain() {
        let host = window.location.host;
        return host.substr(host.indexOf(".") + 1);
    }

    /* 组合数组,对象等类型数据 */
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
