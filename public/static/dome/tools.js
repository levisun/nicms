/**
 * JS常用功能
 *
 * @package   NICMS
 * @author    失眠小枕头 [312630173@qq.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2021
 */
var t = tools();

function tools(_ele = "") {
    var elementObject;
    if (_ele && "#" === _ele.substr(0, 1)) {
        elementObject = document.getElementById(_ele.substr(1));
    } else if (_ele && "." === _ele.substr(0, 1)) {
        elementObject = document.getElementsByClassName(_ele.substr(1));
    } else if (_ele) {
        elementObject = document.getElementsByTagName(_ele);
    }

    return {
        // 宽
        width: function () {
            return this.element() ? this.element().offsetWidth : 0;
        },
        // 高
        height: function () {
            return this.element() ? this.element().offsetHeight : 0;
        },
        // 显示
        show: function () {
            this.element() ? this.element().style.display = "block" : null;
        },
        // 隐藏
        hide: function () {
            this.element() ? this.element().style.display = "none" : null;
        },
        // 获得内容或修改内容
        text: function (content = "") {
            if (this.element() && content) {
                this.element().innerText = content;
            }
            return this.element() ? this.element().innerText : "";
        },
        // 获得HTML或修改HTML
        html: function (content = "") {
            if (this.element() && content) {
                this.element().innerHTML = content;
            }
            return this.element() ? this.element().innerHTML : "";
        },


        // 遍历选择器
        eq: function (num) {
            num = 0 > num ? this.element().length + num : num;
            elementObject = elementObject[num];
            return this;
        },

        /*
        图片加水印
        water(图片地址, 水印内容, 替换图片ID, 字体大小, X轴偏移值, Y轴偏移值);
        */
        water: function (content, fontSize = 20, x = 10, y = 10) {
            var toolsWaterImg = new Image();
            toolsWaterImg.src = this.element().src;
            toolsWaterImg.crossOrigin = "*";
            toolsWaterImg.onload = function () {
                var toolsWaterCanvas = document.createElement("canvas");
                toolsWaterCanvas.width = toolsWaterImg.width;
                toolsWaterCanvas.height = toolsWaterImg.height;
                var toolsWaterCtx = toolsWaterCanvas.getContext("2d");

                toolsWaterCtx.drawImage(toolsWaterImg, 0, 0, toolsWaterImg.width, toolsWaterImg.height);
                toolsWaterCtx.textAlign = "left";
                toolsWaterCtx.textBaseline = "middle";
                toolsWaterCtx.font = fontSize + "px Microsoft Yahei";
                toolsWaterCtx.fillStyle = "rgba(255, 0, 0, 1)";
                toolsWaterCtx.fillText(content, x, y);

                element.src = toolsWaterCanvas.toDataURL();
            }
        },

        element: function () {
            return elementObject;
        },

        /* ============================================================================================================
        ===============================================================================================================
        ===============================================================================================================
        ===============================================================================================================
        ===============================================================================================================
        ===============================================================================================================
        ===============================================================================================================
         */

        ajax: function (options) {
            // 组合参数
            options = this.extend({
                type: "GET",                // 请求类型
                url: null,                  // 请求地址
                async: true,                // 异步开关,默认异步请求
                cache: true,                // 缓存
                username: null,
                password: null,
                timeout: 60,                // 超时
                header: [],
                data: null,
                responseType: "json",

                push: false,                // 添加历史记录
                replace: false,             // 替换历史记录
                requestUrl: null,           // 重写地址

                success: function () { },
                error: function () { },
            }, options);

            var toolsXhr = new window.XMLHttpRequest();
            toolsXhr.timeout = options.timeout * 1000;
            toolsXhr.open(options.type, options.url, options.async, options.username, options.password);
            toolsXhr.onload = function () {
                if (200 === toolsXhr.status) {
                    options.success();
                } else {
                    options.error();
                }
                console.log(toolsXhr.status);
                console.log(toolsXhr.readyState);
            };
            toolsXhr.onerror = function (error) {
                console.log(error);
            }
            toolsXhr.send(options.data);
        },

        /*
        打开窗口
        open(url地址);
        */
        open: function (url) {
            var toolsOpenLink = document.createElement("a");
            toolsOpenLink.setAttribute("href", url);
            toolsOpenLink.setAttribute("target", "_blank");
            toolsOpenLink.setAttribute("id", "tools-open-window");
            document.body.appendChild(toolsOpenLink);
            toolsOpenLink.click();
            document.body.removeChild(toolsOpenLink);
        },

        // 返回顶部
        goTop: function () {
            var toolsGoTopTimer = setInterval(function () {
                var toolsGoTop = document.documentElement.scrollTop || document.body.scrollTop;
                var toolsGoTopSpeed = Math.floor(toolsGoTop / 3);
                document.documentElement.scrollTop = document.body.scrollTop = toolsGoTopSpeed
                    ? parseInt(toolsGoTop - toolsGoTopSpeed)
                    : 0;
                if (toolsGoTop === 0) clearInterval(toolsGoTopTimer);
            }, 30);
        },

        /*
         复制
         copy(内容, 提示语);
         */
        copy: function (content, msg = "copy success") {
            var toolsCopyAux = document.createElement("input");
            toolsCopyAux.setAttribute("value", content);
            document.body.appendChild(toolsCopyAux);
            toolsCopyAux.select();
            document.execCommand("copy");
            document.body.removeChild(toolsCopyAux);
            alert(msg);
        },

        /*
         是否移动端访问
         */
        isMobile: function () {
            return !!navigator.userAgent.match(/(blackberry|configuration\/cldc|hp |hp-|htc |htc_|htc-|iemobile|kindle|midp|mmp|motorola|mobile|nokia|opera mini|opera |Googlebot-Mobile|YahooSeeker\/M1A1-R2D2|android|iphone|ipod|mobi|palm|palmos|pocket|portalmmm|ppc;|smartphone|sonyericsson|sqh|spv|symbian|treo|up.browser|up.link|vodafone|windows ce|xda |xda_)/i);
        },

        /*
         日期与时间
         date("y-m-d h:i:s");
         */
        date: function (format = "y-m-d h:i:s") {
            var toolsDate = new Date();

            format = format.toLowerCase();

            format = format.replace(/y/i, toolsDate.getFullYear());
            format = format.replace(/m/i, 10 < toolsDate.getMonth() ? toolsDate.getMonth() + 1 : "0" + (toolsDate.getMonth() + 1));
            format = format.replace(/d/i, 10 < toolsDate.gettoolsDate() ? toolsDate.gettoolsDate() : "0" + toolsDate.gettoolsDate());

            format = format.replace(/h/i, 10 < toolsDate.getHours() ? toolsDate.getHours() : "0" + toolsDate.getHours());
            format = format.replace(/i/i, 10 < toolsDate.getMinutes() ? toolsDate.getMinutes() : "0" + toolsDate.getMinutes());
            format = format.replace(/s/i, 10 < toolsDate.getSeconds() ? toolsDate.getSeconds() : "0" + toolsDate.getSeconds());

            return format;
        },

        // 时间戳
        timestamp: function () {
            return Date.parse(new Date()) / 1000;
        },

        // 获得URL path name参数
        pathname: function () {
            var toolsPathName = window.location.pathname.substr(1);
            if (toolsPathName.indexOf(".") > 0) {
                toolsPathName = toolsPathName.substr(0, toolsPathName.lastIndexOf("."));
            }
            return toolsPathName.split("/");
        },

        // 获得GET参数
        get: function (name) {
            var toolsGetReg = new RegExp("(^|&)" + name + "=([^&]*)(&|$)", "i");
            var toolsGet = window.location.search.substr(1).match(toolsGetReg);
            if (toolsGet != null) {
                return decodeURIComponent(toolsGet[2]);
            };
            return null;
        },

        /*
         访问(请求地址)
         url(类型);
         hash: 锚点
         origin: 域名(带协议)
         path: 路径(不带域名和参数)
         protocol: 协议
         href: 请求地址
         */
        url: function (type = "href") {
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
        },

        /*
         cookie操作
         cookie(名称, 内容, 保存时间(单位天), 地址, 类型, 域名);
         设置: cookie(名称, 内容);
         读取: cookie(名称);
         移除: cookie(名称, null, -1);
         */
        cookie: function (name, value = "", expire = 365, path = "/", sameSite = "lax", domain = "") {
            var toolsCookie = "";
            var toolsTimestamp = new Date();
            var toolsRootDomain = window.location.host.substr(window.location.host.indexOf(".") + 1);

            if (name && value) {
                toolsTimestamp.setTime(toolsTimestamp.getTime() + expire * 24 * 60 * 60 * 1000);
                toolsCookie = name + "=" + value + ";";
                toolsCookie += "expires=" + toolsTimestamp.toGMTString() + ";"
                toolsCookie += "path=" + path + ";";
                toolsCookie += "SameSite=" + sameSite + ";";
                toolsCookie += domain ? domain : "." + toolsRootDomain;
                document.cookie = toolsCookie;
            } else if (name && null === value) {
                toolsTimestamp.setTime(toolsTimestamp.getTime() + -1 * 24 * 60 * 60 * 1000);
                toolsCookie = name + "=;";
                toolsCookie += "expires=" + toolsTimestamp.toGMTString() + ";"
                toolsCookie += "path=" + path + ";";
                toolsCookie += "SameSite=" + sameSite + ";";
                toolsCookie += domain ? domain : "." + toolsRootDomain;
                document.cookie = toolsCookie;
            } else if (name) {
                name += "=";
                var decodedCookie = decodeURIComponent(document.cookie);
                var ca = decodedCookie.split(";");
                for (var i = 0; i < ca.length; i++) {
                    var c = ca[i];
                    while (c.charAt(0) == " ") {
                        c = c.substring(1);
                    }
                    if (c.indexOf(name) == 0) {
                        return c.substring(name.length, c.length) ? c.substring(name.length, c.length) : null;
                    }
                }
                return null;
            }
        },

        /*
         storage操作
         storage(名称, 内容);
         设置: storage(名称, 内容);
         读取: storage(名称);
         移除: storage(名称, null);
         */
        storage: function (name, value = "") {
            if (!window.localStorage) {
                alert("浏览器不支持localStorage");
                return null;
            }

            if (name && value) {
                window.localStorage.setItem(name, JSON.stringify(value));
            } else if (name && null === value) {
                window.localStorage.removeItem(name);
            } else if (name) {
                var value = window.localStorage.getItem(name);
                return value ? JSON.parse(value) : null;
            }
        },

        /*
         session操作
         session(名称, 内容);
         设置: session(名称, 内容);
         读取: session(名称);
         移除: session(名称, null);
         */
        session: function (name, value = "") {
            if (!window.sessionStorage) {
                alert("浏览器不支持sessionStorage");
                return null;
            }

            if (name && value) {
                window.sessionStorage.setItem(name, JSON.stringify(value));
            } else if (name && null === value) {
                window.sessionStorage.removeItem(name);
            } else if (name) {
                var value = window.sessionStorage.getItem(name);
                return value ? JSON.parse(value) : null;
            }
        },

        /* 组合数组,对象等类型数据 */
        extend: function (destination, source) {
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
    };
}
