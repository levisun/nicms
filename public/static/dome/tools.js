/**
 * JS常用功能
 *
 * @package   NICMS
 * @author    失眠小枕头 [312630173@qq.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2021
 */
var t = Tools = tools();
Tools.ui();

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
        ui: function () {
            var head = document.getElementsByTagName("head")[0];
            var style = document.createElement("style");
            style.id = "tools-ui";
            style.innerText = ".toast-mask,.loading-mask,.popup-mask{width:100%;height:100%;position:fixed;margin:auto;background:rgba(0,0,0,.6);visibility:visible;z-index:10;top:0;left:0;}.toast-mask .toast-tips{width:40%;background:rgba(0,0,0,.7);color:white;margin:30% auto;border-radius:5px;overflow:hidden;position:relative;padding:10px 15px;text-align:center;}.loading-mask .loading-tips{width:70px;height:70px;margin:30% auto;vertical-align:middle;animation:loadingAnimation 1s steps(12,end) infinite;background:transparent url(\"data:image/svg+xml;charset=utf8, %3Csvg xmlns='http://www.w3.org/2000/svg' width='120' height='120' viewBox='0 0 100 100'%3E%3Cpath fill='none' d='M0 0h100v100H0z'/%3E%3Crect width='7' height='20' x='46.5' y='40' fill='%23E9E9E9' rx='5' ry='5' transform='translate(0 -30)'/%3E%3Crect width='7' height='20' x='46.5' y='40' fill='%23989697' rx='5' ry='5' transform='rotate(30 105.98 65)'/%3E%3Crect width='7' height='20' x='46.5' y='40' fill='%239B999A' rx='5' ry='5' transform='rotate(60 75.98 65)'/%3E%3Crect width='7' height='20' x='46.5' y='40' fill='%23A3A1A2' rx='5' ry='5' transform='rotate(90 65 65)'/%3E%3Crect width='7' height='20' x='46.5' y='40' fill='%23ABA9AA' rx='5' ry='5' transform='rotate(120 58.66 65)'/%3E%3Crect width='7' height='20' x='46.5' y='40' fill='%23B2B2B2' rx='5' ry='5' transform='rotate(150 54.02 65)'/%3E%3Crect width='7' height='20' x='46.5' y='40' fill='%23BAB8B9' rx='5' ry='5' transform='rotate(180 50 65)'/%3E%3Crect width='7' height='20' x='46.5' y='40' fill='%23C2C0C1' rx='5' ry='5' transform='rotate(-150 45.98 65)'/%3E%3Crect width='7' height='20' x='46.5' y='40' fill='%23CBCBCB' rx='5' ry='5' transform='rotate(-120 41.34 65)'/%3E%3Crect width='7' height='20' x='46.5' y='40' fill='%23D2D2D2' rx='5' ry='5' transform='rotate(-90 35 65)'/%3E%3Crect width='7' height='20' x='46.5' y='40' fill='%23DADADA' rx='5' ry='5' transform='rotate(-60 24.02 65)'/%3E%3Crect width='7' height='20' x='46.5' y='40' fill='%23E2E2E2' rx='5' ry='5' transform='rotate(-30 -5.98 65)'/%3E%3C/svg%3E\") no-repeat;-webkit-background-size:100%;background-size:100%;}@-webkit-keyframes loadingAnimation{0%{-webkit-transform:rotate3d(0,0,1,0deg);transform:rotate3d(0,0,1,0deg);}100%{-webkit-transform:rotate3d(0,0,1,360deg);transform:rotate3d(0,0,1,360deg);}}@keyframes loadingAnimation{0%{-webkit-transform:rotate3d(0,0,1,0deg);transform:rotate3d(0,0,1,0deg);}100%{-webkit-transform:rotate3d(0,0,1,360deg);transform:rotate3d(0,0,1,360deg);}}.popup-mask .popup-box{width:400px;min-height:120px;background-color:white;margin:30% auto;border-radius:5px;overflow:hidden;position:relative;padding:10px 15px;}.popup-mask .popup-title,.popup-mask .popup-content{display:flex;justify-content:space-between;margin:0;padding:0;}.popup-mask .popup-title .popup-left{font-size:16px;font-weight:700;margin:0;padding:0;}.popup-mask .popup-title .popup-close{padding-top:5px;cursor:pointer;margin:0;padding:0;}.popup-mask .popup-title .popup-close{width:20px;height:20px;background:transparent url(\"data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAADAAAAAwCAYAAABXAvmHAAACmElEQVR4AWLAAaKAmJcB0K09AOmxBQEUXtvPtm3btm0ztm3btm3bScW2bXROnMyq73Arp+orLvr+GAffc7gZRv0LwRgkIKiexx6swo1Q9TfkLKMQD797FrshEKzEDciyPyBWGOHzIp7GLojFClyHDPsNkhkMQxy87knr8BbLca31l37BEUg2hiAWXvUEdirmWIZrQERNIUqDPVrEY9gBUdiL50BEYWgJURqIGLjVI9huMPzLsMQi0Aai1B/RcNrD2AZR2IdXkWnhaAdR6utwEQ9iK0RhP15HtoWjA0SpN6Jg2v3YYjD8m1AXgU4QpZ6IhLb7DIY/gLdhXAS6QJS6KxdxDzYbDP8ubBeJbhClrohAZt2NTRCFg3gfjotED4hSp0wWcSc2Ggz/IVwrCr0gSh0QjlPdgQ0QhUP4GK4XhT4QpXYIx21YbzD8p/CsaPQz/GKvMxj+c3heDAZAXHQYX8K3YjDIxeG/hu/FYjDEgSP4DoEVh6EOhv8BgReH4TAd/ifkmO7AYYjScIQiR3Q1lkIM1UHgXYXFEJtqIbCuxCKIQzXge5djIcQlVeFbl2E+xGWV4XmXYh5EqTgmQ5QqwrMuwVyIUn6EUCqmQpTKwfUuxhyIUiGcXRqmQ5TKwLUuwmyIUhFk1AWYAVEqCcddiJkQpWKKvzfL8Dukz/tXzPk7qi0N00w+s95/pxT5vNUw3aoVQLal+LrdNt+v5EWmJWNiAHvOy7EAopQb6UrCBIhSNbjZFYbHVv/hnAYFf/RofHT7keauoFVteNlVWAKxUN01td6Xtaob1Bme6r615c64WNRHKPzqWiyDWKieHHjBsohGAZ2AX4flEAjGIhGqXsJeNEEogup6rMR4JMGoexCGoLsOyTh/OwoWaVLb26+tkQAAAABJRU5ErkJggg==\") no-repeat;-webkit-background-size:100%;background-size:100%;}.popup-mask .popup-button{display:flex;justify-content:space-between;position:absolute;left:0;right:0;bottom:0;}.popup-mask .popup-button button{width:50%;height:40px;border:none;border-top:1px solid #ccc;padding:0;border-radius:0;}.popup-mask .popup-button button:first-child{border-right:1px solid #ccc;}";
            head.appendChild(style);
            // head.parentNode.insertBefore(style, head);
        },


        popup: function (title, msg) {
            title = title ? title : window.location.hostname;
            var html = "<div class='popup-box'><div class='popup-title'><div class='popup-left'>" + title + "</div><div class='popup-close' onclick='t.popupClose()'></div></div><div class='popup-content'>" + msg + "</div><div class='popup-button'><button>确认</button><button>取消</button></div></div>";
            // cancel

            var body = document.getElementsByTagName("body")[0];
            var div = document.createElement("div");
            div.className = "popup-mask";
            div.innerHTML = "<div class='popup-box'><div class='popup-title'><div class='popup-left'>" + title + "</div><div class='popup-close' onclick='t.popupClose()'></div></div><div class='popup-content'>" + msg + "</div><div class='popup-button'><button>确认</button><button>取消</button></div></div><script></script>";
            body.appendChild(div);
        },

        popupClose: function () {
            var div = document.getElementsByClassName("popup-mask")[0];
            div.parentNode.removeChild(div);
        },

        // 轻提示
        toast: function (msg, time = 1) {
            var body = document.getElementsByTagName("body")[0];
            var div = document.createElement("div");
            div.className = "toast-mask";
            div.innerHTML = "<div class='toast-tips'>" + msg + "</div>";
            body.appendChild(div);
            setTimeout(function () {
                var div = document.getElementsByClassName("toast-mask")[0];
                div.parentNode.removeChild(div);
            }, time * 1000);
        },

        // 加载动画
        loading: function (status = false) {
            if (status) {
                var body = document.getElementsByTagName("body")[0];
                var div = document.createElement("div");
                div.className = "loading-mask";
                div.innerHTML = "<div class='loading-tips'></div>";
                body.appendChild(div);
            } else {
                var div = document.getElementsByClassName("loading-mask")[0];
                div.parentNode.removeChild(div);
            }
        },

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
        before: function (html) {
            html = document.createRange().createContextualFragment(html);
            this.element().parentNode.insertBefore(html, this.element());
        },
        // 元素内追加内容
        append: function (html) {
            html = document.createRange().createContextualFragment(html);
            this.element().appendChild(html);
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

        video: function (options) {
            // 组合参数
            options = this.extend({
                controls: true, // 控件
                autoplay: true, // 自动播放
                loop: false,    // 循环播放
            }, options);

            // 循环播放
            this.element().loop = options.loop;
            // 自动播放
            this.element().autoplay = options.autoplay;
            if (true === options.autoplay) {
                this.element().play();
            }

            // 加载进度
            this.element().addEventListener("progress", function () {
                var buffered = Math.round(this.buffered.end(0));
                var seekable = Math.round(this.seekable.end(0));
                var cache = Math.round(buffered / seekable * 100);
                // console.log(cache + "%");
            }, false);

            // 播放中
            this.element().addEventListener("timeupdate", function () {
                // 控件
                this.controls = options.controls;
                // 屏蔽右键
                this.oncontextmenu = function () {
                    return false;
                }
                // console.log("monitoring");
            }, false);

            var div = document.createElement("div");
            div.className = "video-mask";
            div.style.display = "none";
            div.style.width = this.width() + "px";
            div.style.height = this.height() + "px";
            div.style.background = "rgba(0,0,0,.6)";
            div.style.position = "absolute";
            div.innerHTML = "<div class='video-tips'></div>";
            this.element().parentNode.insertBefore(div, this.element());

            return this.element();

            // return this.element().currentTime;
        },

        toFullVideo: function () {
            if (videoDom.requestFullscreen) {
                return videoDom.requestFullscreen();
            } else if (videoDom.webkitRequestFullScreen) {
                return videoDom.webkitRequestFullScreen();
            } else if (videoDom.mozRequestFullScreen) {
                return videoDom.mozRequestFullScreen();
            } else {
                return videoDom.msRequestFullscreen();
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

        // 监听滚动
        scroll: function (callback) {
            window.addEventListener("scroll", function () {
                callback();
            });
        },

        // 返回顶部
        goTop: function () {
            window.scrollTo({
                top: 0,
                behavior: "smooth"
            });
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
