
class tools {

    constructor() {
    }

    ajax(options) {
        // 组合参数
        options = this.extend({
            type: "GET",                // 请求类型
            url: null,                  // 请求地址
            async: true,                // 异步开关,默认异步请求
            cache: true,                // 缓存
            username: null,
            password: null,
            timeout: 60000,             // 超时
            header: [],
            data: null,
            responseType: "json",

            push: false,                // 添加历史记录
            replace: false,             // 替换历史记录
            requestUrl: null,           // 重写地址

            success: function () { },
            error: function () { },
        }, options);

        // GET请求
        if ("get" === options.type.toLowerCase() && options.data) {
            let query = "";
            for (const key in options.data) {
                if (Object.hasOwnProperty.call(options.data, key)) {
                    const element = options.data[key];
                    query += "&" + key + "=" + element;
                }
            }
            query = query.substr(1);
            options.data = null;

            options.url += -1 === options.url.indexOf("?") ? "?" + query : query;
        }

        // 缓存
        if (false === options.cache) {
            options.url += "_" + this.timestamp();
        }

        const xhr = new window.XMLHttpRequest();
        xhr.timeout = options.timeout;
        xhr.open(options.type, options.url, options.async, options.username, options.password);

        // 设置响应数据类型
        if (options.responseType) {
            xhr.overrideMimeType(options.responseType);
        }

        if (options.mimeType && xhr.overrideMimeType) {
            xhr.overrideMimeType(options.mimeType);
        }

        // 设置头部信息
        for (let index in options.header) {
            if ("Object" === Object.prototype.toString.call(options.header[index]).slice(8, -1)) {
                xhr.setRequestHeader(options.header[index].name.toLowerCase(), options.header[index].value);
            } else {
                xhr.setRequestHeader(index.toLowerCase(), options.header[index]);
            }
        }

        // 设置监听事件
        xhr.addEventListener("load", () => {
            try {
                if (4 === xhr.readyState && 400 > xhr.status) {
                    if (options.requestUrl) {
                        window.history.pushState(null, document.title, options.requestUrl);
                    }

                    xhr.response = "json" === options.responseType ? JSON.parse(xhr.response) : xhr.response;

                    options.success(result);
                } else {
                    options.error(xhr.response);
                }
            } catch (error) {
                console.log(error.message);
            }
        });

        xhr.send(options.data);
    }

    // 异步请求
    ajaxold(options) {
        // 获得顶级域名(不加后缀)
        let root = this.rootDomain().substr(0, this.rootDomain().indexOf("."));

        // 头部信息
        options.header = {
            Accept: "application/vnd." + root + ".v" + document.getElementsByName("csrf-version")[0].content + "+json",
            Authorization: "Bearer " + window.atob(window.localStorage.getItem('XSRF_AUTHORIZATION')),
        };

        // 组合参数
        options = this.extend(this.asyncOptions, options);

        // 组合GET请求参数
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

        // 当关闭缓存时,拼接时间戳
        if (false === options.cache) {
            options.url += -1 === options.url.indexOf("?") ? "?_=" + this.timestamp() : "&_=" + this.timestamp();
        }

        const xhr = new window.XMLHttpRequest();
        xhr.timeout = options.timeout;
        xhr.open(options.type, options.url, options.async, options.username, options.password);

        // 设置响应数据类型
        if (options.responseType) {
            xhr.overrideMimeType(options.responseType);
        }

        if (options.mimeType && xhr.overrideMimeType) {
            xhr.overrideMimeType(options.mimeType);
        }

        // 设置头部信息
        for (let index in options.header) {
            if ("Object" === Object.prototype.toString.call(options.data[index]).slice(8, -1)) {
                xhr.setRequestHeader(options.header[index].name, options.header[index].value);
            } else {
                xhr.setRequestHeader(index, options.header[index]);
            }
        }

        // 设置监听事件
        xhr.addEventListener("load", () => {
            if (400 > xhr.status && options.requestUrl) {
                window.history.pushState(null, document.title, options.requestUrl);

                xhr.response = "json" === options.responseType ? JSON.parse(xhr.response) : xhr.response;

                options.success(xhr.response);
            } else {
                options.error(xhr.response);
            }
        });

        // 发起请求
        if ("get" === options.type.toLowerCase()) {
            xhr.send();
        } else {
            xhr.send(options.data);
        }
    }

    /*
     图片加水印
     water(图片地址, 水印内容, 替换图片ID, 字体大小, X轴偏移值, Y轴偏移值);
     */
    water(img_path, content, elementId, font_size = "20px", x = 10, y = 10) {
        let img = new Image();
        img.src = img_path;
        img.crossOrigin = "*";
        img.onload = function () {
            const canvas = document.createElement("canvas");
            canvas.width = img.width;
            canvas.height = img.height;
            const ctx = canvas.getContext('2d');

            ctx.drawImage(img, 0, 0, img.width, img.height);
            ctx.textAlign = "left";
            ctx.textBaseline = "middle";
            ctx.font = font_size + " Microsoft Yahei";
            ctx.fillStyle = "rgba(255, 0, 0, 1)";
            ctx.fillText(content, x, y);

            document.getElementById(elementId).src = canvas.toDataURL();
        }
    }

    /*
     复制
     copy(内容, 提示语);
     */
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
     storage操作
     storage(名称, 内容);
     设置: storage(名称, 内容);
     读取: storage(名称);
     移除: storage(名称, null);
     */
    storage(name, value = "") {
        if (name && value) {
            window.localStorage.setItem(name, JSON.stringify(value));
        } else if (name && null === value) {
            window.localStorage.removeItem(name);
        } else if (name) {
            let value = window.localStorage.getItem(name);
            return value ? JSON.parse(value) : null;
        }
    }

    /*
     session操作
     session(名称, 内容);
     设置: session(名称, 内容);
     读取: session(名称);
     移除: session(名称, null);
     */
    session(name, value = "") {
        if (name && value) {
            window.sessionStorage.setItem(name, JSON.stringify(value));
        } else if (name && null === value) {
            window.sessionStorage.removeItem(name);
        } else if (name) {
            let value = window.sessionStorage.getItem(name);
            return value ? JSON.parse(value) : null;
        }
    }

    /*
     cookie操作
     cookie(名称, 内容, 保存时间, 地址, 类型, 域名);
     设置: cookie(名称, 内容);
     读取: cookie(名称);
     移除: cookie(名称, null, -1);
     */
    cookie(name, value = "", expire = 0, path = "/", same_site = "lax", domain = "") {
        let cookie = "";

        if (name && value) {
            cookie = name + "=" + value + ";";
            cookie += expire
                ? "expires=" + this.timestamp() + expire + ";"
                : "";
            cookie += "path=" + path + ";";
            cookie += "SameSite=" + same_site + ";";
            cookie += domain ? domain : "." + this.rootDomain();
            document.cookie = cookie;
        } else if (name && 0 > expire) {
            cookie = name + "=" + null + ";";
            cookie += "expires=-1440;";
            cookie += "path=" + path + ";";
            cookie += "SameSite=" + same_site + ";";
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

    // 日期与时间
    date(format = "y-m-d h:i:s") {
        let date = new Date();

        format = format.toLowerCase();

        format = format.replace(/y/i, date.getFullYear());
        format = format.replace(/m/i, 10 < date.getMonth() ? date.getMonth() + 1 : "0" + (date.getMonth() + 1));
        format = format.replace(/d/i, 10 < date.getDay() ? date.getDay() : "0" + date.getDay());

        format = format.replace(/h/i, 10 < date.getHours() ? date.getHours() : "0" + date.getHours());
        format = format.replace(/i/i, 10 < date.getMinutes() ? date.getMinutes() : "0" + date.getMinutes());
        format = format.replace(/s/i, 10 < date.getSeconds() ? date.getSeconds() : "0" + date.getSeconds());

        return format;
    }

    // 时间戳
    timestamp() {
        return Date.parse(new Date()) / 1000;
    }

    /*
     访问(请求地址)
     url(类型);
     hash: 锚点
     origin: 域名(带协议)
     path: 路径(不带域名和参数)
     protocol: 协议
     href: 请求地址
     */
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
        return window.location.host.substr(window.location.host.indexOf(".") + 1);
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
