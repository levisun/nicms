/*
http://google.github.io/traceur-compiler/demo/repl.html#
移动端浏览器大部分不支持ES6
*/

try {
    let arrowFunction = "let t = () => {};";
    f = new Function(arrowFunction);
    // document.write("当前浏览器支持ES6!");
} catch (e) {
    document.write("当前浏览器不支持ES6! " + e);
}

class Lev {

    // 构造函数
    constructor() {
        this.init();
    }














    // https://www.jianshu.com/p/e5dfc486ecc6
    video(options) {
        // 组合参数
        options = this.extend({
            controls: true, // 控件
            autoplay: true, // 自动播放
            loop: false,    // 循环播放
        }, options);

        // 循环播放
        this.elementObject.loop = options.loop;
        // 自动播放
        this.elementObject.autoplay = options.autoplay;
        if (true === options.autoplay) {
            this.elementObject.play();
        } else {
            this.elementObject.pause();
        }

        // 加载进度
        this.elementObject.addEventListener("progress", function () {
            // let buffered = Math.round(this.buffered.end(0));
            // let seekable = Math.round(this.seekable.end(0));
            // let cache = Math.round(buffered / seekable * 100);
            // console.log(cache + "%");
        }, false);

        // 播放中
        this.elementObject.addEventListener("timeupdate", function () {
            // 控件
            this.controls = options.controls;
            // 屏蔽右键
            this.oncontextmenu = function () {
                return false;
            }
            // console.log("monitoring");
        }, false);

        let div = document.createElement("div");
        div.className = "video-mask";
        div.style.display = "none";
        div.style.width = this.width() + "px";
        div.style.height = this.height() + "px";
        div.style.background = "rgba(0,0,0,.6)";
        div.style.position = "absolute";
        div.style.zIndex = "";
        div.innerHTML = "<div class='video-tips'></div>";
        this.elementObject.parentNode.insertBefore(div, this.elementObject);

        return this.elementObject;

        // return this.elementObject.currentTime;
    }

    toFullVideo() {
        if (this.elementObject.requestFullscreen) {
            return this.elementObject.requestFullscreen();
        } else if (this.elementObject.webkitRequestFullScreen) {
            return this.elementObject.webkitRequestFullScreen();
        } else if (this.elementObject.mozRequestFullScreen) {
            return this.elementObject.mozRequestFullScreen();
        } else {
            return this.elementObject.msRequestFullscreen();
        }
    }

    request(options) {
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

        options.type = options.type.toUpperCase();

        let xhr = new window.XMLHttpRequest();
        xhr.open(options.type, options.url, options.async, options.username, options.password);
        xhr.timeout = options.timeout * 1000;
        xhr.addEventListener("load", function () {
            if (200 === xhr.status) {
                options.success();
            } else {
                options.error();
            }
            console.log(xhr.status);
            console.log(xhr.response);
            console.log(xhr.readyState);
        });
        if ("POST" == options.type) {
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded;");
        }

        xhr.onerror = function (error) {
            console.log(error);
        }
        options.data = options.data ? JSON.stringify(options.data) : null;
        xhr.send(options.data);
    }

    /* 测试可用方法 ================================================================================================
    ===============================================================================================================
    ===============================================================================================================
    ===============================================================================================================
    ===============================================================================================================
    ===============================================================================================================
    ===============================================================================================================
     */

    payOrder(trade_no) {
        if (null === this.storage(trade_no)) {
            this.storage(trade_no, this.timestamp());
        }
    }

    popupButton(title, msg, but, callback) {
        title = title ? title : window.location.hostname;

        let name = "popup-" + md5(msg);
        let body = document.getElementsByTagName("body")[0];
        let div = document.createElement("div");
        div.className = "lev-popup-mask";
        div.id = name;
        div.innerHTML = "<div class='lev-popup-box'><div class='lev-popup-title'><div class='lev-popup-left'>" + title + "</div><div class='lev-popup-close'></div></div><div class='lev-popup-content'>" + msg + "</div><div class='lev-popup-button'><button class='lev-popup-btn-confirm' style='width:100%;'>" + but + "</button></div></div>";
        body.appendChild(div);

        document.getElementById(name).getElementsByClassName('lev-popup-close')[0].onclick = function () {
            let div = document.getElementById(name);
            div.parentNode.removeChild(div);
        };
        document.getElementById(name).getElementsByClassName('lev-popup-btn-confirm')[0].onclick = function () {
            callback(true);
        };
    }

    popupConfirm(title, msg, callback) {
        title = title ? title : window.location.hostname;

        let name = "popup-" + md5(msg);
        let body = document.getElementsByTagName("body")[0];
        let div = document.createElement("div");
        div.className = "lev-popup-mask";
        div.id = name;
        div.innerHTML = "<div class='lev-popup-box'><div class='lev-popup-title'><div class='lev-popup-left'>" + title + "</div><div class='lev-popup-close'></div></div><div class='lev-popup-content'>" + msg + "</div><div class='lev-popup-button'><button class='lev-popup-btn-confirm'>确认</button><button class='lev-popup-btn-cancel'>取消</button></div></div>";
        body.appendChild(div);

        document.getElementById(name).getElementsByClassName('lev-popup-close')[0].onclick = function () {
            let div = document.getElementById(name);
            div.parentNode.removeChild(div);
        };
        document.getElementById(name).getElementsByClassName('lev-popup-btn-confirm')[0].onclick = function () {
            callback(true);
        };
        document.getElementById(name).getElementsByClassName('lev-popup-btn-cancel')[0].onclick = function () {
            callback(false);
        };
    }

    /*
     轻提示
     */
    toast(msg, time = 1.5) {
        let body = document.getElementsByTagName("body")[0];
        let div = document.createElement("div");
        div.className = "lev-toast-mask";
        div.innerHTML = "<div class='lev-toast-tips'>" + msg + "</div>";
        body.appendChild(div);
        setTimeout(function () {
            let div = document.getElementsByClassName("lev-toast-mask")[0];
            div.parentNode.removeChild(div);
        }, time * 1000);
    }

    /*
     加载动画
     */
    loading(status = false) {
        if (status) {
            let body = document.getElementsByTagName("body")[0];
            let div = document.createElement("div");
            div.className = "lev-loading-mask";
            div.innerHTML = "<div class='lev-loading-tips'></div>";
            body.appendChild(div);
        } else {
            let div = document.getElementsByClassName("lev-loading-mask")[0];
            div.parentNode.removeChild(div);
        }
    }

    // 初始化
    init() {
        let head = document.getElementsByTagName("head")[0];
        let style = document.createElement("style");
        style.id = "Lev-ui";
        style.innerText = ".lev-toast-mask,.lev-loading-mask,.lev-popup-mask{width:100%;height:100%;position:fixed;margin:auto;background:rgba(0,0,0,.6);visibility:visible;z-index:10;top:0;left:0;}.lev-toast-mask .lev-toast-tips{width:40%;background:rgba(0,0,0,.7);color:white;margin:30% auto;border-radius:5px;overflow:hidden;position:relative;padding:10px 15px;text-align:center;}.lev-loading-mask .lev-loading-tips{width:70px;height:70px;margin:30% auto;vertical-align:middle;animation:loadingAnimation 1s steps(12,end) infinite;background:transparent url(\"data:image/svg+xml;charset=utf8, %3Csvg xmlns='http://www.w3.org/2000/svg' width='120' height='120' viewBox='0 0 100 100'%3E%3Cpath fill='none' d='M0 0h100v100H0z'/%3E%3Crect width='7' height='20' x='46.5' y='40' fill='%23E9E9E9' rx='5' ry='5' transform='translate(0 -30)'/%3E%3Crect width='7' height='20' x='46.5' y='40' fill='%23989697' rx='5' ry='5' transform='rotate(30 105.98 65)'/%3E%3Crect width='7' height='20' x='46.5' y='40' fill='%239B999A' rx='5' ry='5' transform='rotate(60 75.98 65)'/%3E%3Crect width='7' height='20' x='46.5' y='40' fill='%23A3A1A2' rx='5' ry='5' transform='rotate(90 65 65)'/%3E%3Crect width='7' height='20' x='46.5' y='40' fill='%23ABA9AA' rx='5' ry='5' transform='rotate(120 58.66 65)'/%3E%3Crect width='7' height='20' x='46.5' y='40' fill='%23B2B2B2' rx='5' ry='5' transform='rotate(150 54.02 65)'/%3E%3Crect width='7' height='20' x='46.5' y='40' fill='%23BAB8B9' rx='5' ry='5' transform='rotate(180 50 65)'/%3E%3Crect width='7' height='20' x='46.5' y='40' fill='%23C2C0C1' rx='5' ry='5' transform='rotate(-150 45.98 65)'/%3E%3Crect width='7' height='20' x='46.5' y='40' fill='%23CBCBCB' rx='5' ry='5' transform='rotate(-120 41.34 65)'/%3E%3Crect width='7' height='20' x='46.5' y='40' fill='%23D2D2D2' rx='5' ry='5' transform='rotate(-90 35 65)'/%3E%3Crect width='7' height='20' x='46.5' y='40' fill='%23DADADA' rx='5' ry='5' transform='rotate(-60 24.02 65)'/%3E%3Crect width='7' height='20' x='46.5' y='40' fill='%23E2E2E2' rx='5' ry='5' transform='rotate(-30 -5.98 65)'/%3E%3C/svg%3E\") no-repeat;-webkit-background-size:100%;background-size:100%;}@-webkit-keyframes loadingAnimation{0%{-webkit-transform:rotate3d(0,0,1,0deg);transform:rotate3d(0,0,1,0deg);}100%{-webkit-transform:rotate3d(0,0,1,360deg);transform:rotate3d(0,0,1,360deg);}}@keyframes loadingAnimation{0%{-webkit-transform:rotate3d(0,0,1,0deg);transform:rotate3d(0,0,1,0deg);}100%{-webkit-transform:rotate3d(0,0,1,360deg);transform:rotate3d(0,0,1,360deg);}}.lev-popup-mask .lev-popup-box{width:70%;min-height:120px;background-color:white;margin:30% auto;border-radius:5px;overflow:hidden;position:relative;padding:10px 15px;}.lev-popup-mask .lev-popup-title,.lev-popup-mask .lev-popup-content{display:flex;justify-content:space-between;margin:0;padding:0;}.lev-popup-mask .lev-popup-title .lev-popup-left{font-size:16px;font-weight:700;margin:0;padding:0;}.lev-popup-mask .lev-popup-title .lev-popup-close{padding-top:5px;cursor:pointer;margin:0;padding:0;}.lev-popup-mask .lev-popup-title .lev-popup-close{width:20px;height:20px;background:transparent url(\"data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAADAAAAAwCAYAAABXAvmHAAACmElEQVR4AWLAAaKAmJcB0K09AOmxBQEUXtvPtm3btm0ztm3btm3bScW2bXROnMyq73Arp+orLvr+GAffc7gZRv0LwRgkIKiexx6swo1Q9TfkLKMQD797FrshEKzEDciyPyBWGOHzIp7GLojFClyHDPsNkhkMQxy87knr8BbLca31l37BEUg2hiAWXvUEdirmWIZrQERNIUqDPVrEY9gBUdiL50BEYWgJURqIGLjVI9huMPzLsMQi0Aai1B/RcNrD2AZR2IdXkWnhaAdR6utwEQ9iK0RhP15HtoWjA0SpN6Jg2v3YYjD8m1AXgU4QpZ6IhLb7DIY/gLdhXAS6QJS6KxdxDzYbDP8ubBeJbhClrohAZt2NTRCFg3gfjotED4hSp0wWcSc2Ggz/IVwrCr0gSh0QjlPdgQ0QhUP4GK4XhT4QpXYIx21YbzD8p/CsaPQz/GKvMxj+c3heDAZAXHQYX8K3YjDIxeG/hu/FYjDEgSP4DoEVh6EOhv8BgReH4TAd/ifkmO7AYYjScIQiR3Q1lkIM1UHgXYXFEJtqIbCuxCKIQzXge5djIcQlVeFbl2E+xGWV4XmXYh5EqTgmQ5QqwrMuwVyIUn6EUCqmQpTKwfUuxhyIUiGcXRqmQ5TKwLUuwmyIUhFk1AWYAVEqCcddiJkQpWKKvzfL8Dukz/tXzPk7qi0N00w+s95/pxT5vNUw3aoVQLal+LrdNt+v5EWmJWNiAHvOy7EAopQb6UrCBIhSNbjZFYbHVv/hnAYFf/RofHT7keauoFVteNlVWAKxUN01td6Xtaob1Bme6r615c64WNRHKPzqWiyDWKieHHjBsohGAZ2AX4flEAjGIhGqXsJeNEEogup6rMR4JMGoexCGoLsOyTh/OwoWaVLb26+tkQAAAABJRU5ErkJggg==\") no-repeat;-webkit-background-size:100%;background-size:100%;}.lev-popup-mask .lev-popup-button{display:flex;justify-content:space-between;position:absolute;left:0;right:0;bottom:0;}.lev-popup-mask .lev-popup-button button{width:50%;height:40px;border:none;border-top:1px solid #ccc;padding:0;border-radius:0;}.lev-popup-mask .lev-popup-button button:first-child{border-right:1px solid #ccc;}";
        head.appendChild(style);

        try {
            md5(1);
        } catch (e) {
            let script = document.createElement("script");
            script.src = "https://cdn.jsdelivr.net/npm/blueimp-md5@2.12.0/js/md5.min.js";
            head.appendChild(script);
        }
    }

    /* DOM方法 ====================================================================================================
    ===============================================================================================================
    ===============================================================================================================
    ===============================================================================================================
    ===============================================================================================================
    ===============================================================================================================
    ===============================================================================================================
     */

    /*
    图片加水印
    water(图片地址, 水印内容, 替换图片ID, 字体大小, X轴偏移值, Y轴偏移值);
    */
    water(content, fontSize = 20, x = 10, y = 10) {
        let toolsWaterImg = new Image();
        toolsWaterImg.src = this.elementObject.src;
        toolsWaterImg.crossOrigin = "*";
        toolsWaterImg.onload = function () {
            let toolsWaterCanvas = document.createElement("canvas");
            toolsWaterCanvas.width = toolsWaterImg.width;
            toolsWaterCanvas.height = toolsWaterImg.height;
            let toolsWaterCtx = toolsWaterCanvas.getContext("2d");

            toolsWaterCtx.drawImage(toolsWaterImg, 0, 0, toolsWaterImg.width, toolsWaterImg.height);
            toolsWaterCtx.textAlign = "left";
            toolsWaterCtx.textBaseline = "middle";
            toolsWaterCtx.font = fontSize + "px Microsoft Yahei";
            toolsWaterCtx.fillStyle = "rgba(255, 0, 0, 1)";
            toolsWaterCtx.fillText(content, x, y);

            element.src = toolsWaterCanvas.toDataURL();
        }
    }

    // 宽
    width() {
        return this.elementObject ? this.elementObject.offsetWidth : 0;
    }
    // 高
    height() {
        return this.elementObject ? this.elementObject.offsetHeight : 0;
    }
    // 显示
    show() {
        this.elementObject ? this.elementObject.style.display = "block" : null;
    }
    // 隐藏
    hide() {
        this.elementObject ? this.elementObject.style.display = "none" : null;
    }

    before(html) {
        html = document.createRange().createContextualFragment(html);
        this.elementObject.parentNode.insertBefore(html, this.elementObject);
    }
    // 元素内追加内容
    append(html) {
        html = document.createRange().createContextualFragment(html);
        this.elementObject.appendChild(html);
    }

    // 获得表单内容或修改内容
    value(content = "") {
        if (this.elementObject && content) {
            this.elementObject.value = content;
        }
        return this.elementObject && this.elementObject.value ? this.elementObject.value : null;
    }

    // 获得内容或修改内容
    text(content = "") {
        if (this.elementObject && content) {
            this.elementObject.innerText = content;
        }
        return this.elementObject && this.elementObject.innerText ? this.elementObject.innerText : null;
    }

    // 获得HTML或修改HTML
    html(content = "") {
        if (this.elementObject && content) {
            this.elementObject.innerHTML = content;
        }
        return this.elementObject && this.elementObject.innerHTML ? this.elementObject.innerHTML : null;
    }

    // 遍历选择器
    eq(num) {
        if (this.elementObject) {
            num = 0 > num ? this.elementObject.length + num : num;
            this.elementObject = this.elementObject.length > num ? this.elementObject[num] : null;
        }
        return this;
    }

    // 遍历元素
    each(callback) {
        if (this.elementObject && this.elementObject.length) {
            for (let index = 0; index < this.elementObject.length; index++) {
                const element = this.elementObject[index];
                callback(index, element);
            }
        }
    }

    /*
     选择DOM元素
     select("#idName");
     select(".className");
     select("elementName");
     */
    select(element) {
        if (element) {
            let sub = element.substr(0, 1);

            if ("#" === sub) {
                this.elementObject = document.getElementById(element.substr(1));
            } else if ("." === sub) {
                this.elementObject = document.getElementsByClassName(element.substr(1));
            } else {
                this.elementObject = document.getElementsByTagName(element);
            }
        }
        return this;
    }

    /* 常用方法 ====================================================================================================
    ===============================================================================================================
    ===============================================================================================================
    ===============================================================================================================
    ===============================================================================================================
    ===============================================================================================================
    ===============================================================================================================
     */

    imgToBase64(img, callback) {
        const reader = new FileReader();
        reader.onload = function (ev) {
            callback(ev.target.result);
        }
        reader.readAsDataURL(img.files[0]);
    }

    imgError(img) {
        let element = document.getElementsByTagName("img");
        for (const key in element) {
            const item = element[key];
            item.onerror = function () {
            };
            item.onerror = null;
            console.log(item);

        }
    }

    /*
     打开窗口
     open(url地址);
    */
    open(url) {
        let toolsOpenLink = document.createElement("a");
        toolsOpenLink.setAttribute("href", url);
        toolsOpenLink.setAttribute("target", "_blank");
        toolsOpenLink.setAttribute("id", "tools-open-window");
        document.body.appendChild(toolsOpenLink);
        toolsOpenLink.click();
        document.body.removeChild(toolsOpenLink);
    }

    /*
     监听滚动
     */
    scroll(callback) {
        window.addEventListener("scroll", function () {
            callback();
        });
    }

    /*
     返回顶部
     */
    goTop() {
        window.scrollTo({ top: 0, behavior: "smooth" });
    }

    /*
     复制
     copy(内容, 提示语);
     */
    copy(content, msg = "copy success") {
        let toolsCopyAux = document.createElement("input");
        toolsCopyAux.setAttribute("value", content);
        document.body.appendChild(toolsCopyAux);
        toolsCopyAux.select();
        document.execCommand("copy");
        document.body.removeChild(toolsCopyAux);
        alert(msg);
    }

    /*
     是否移动端访问
     */
    isMobile() {
        return !!navigator.userAgent.match(/(blackberry|configuration\/cldc|hp |hp-|htc |htc_|htc-|iemobile|kindle|midp|mmp|motorola|mobile|nokia|opera mini|opera |Googlebot-Mobile|YahooSeeker\/M1A1-R2D2|android|iphone|ipod|mobi|palm|palmos|pocket|portalmmm|ppc;|smartphone|sonyericsson|sqh|spv|symbian|treo|up.browser|up.link|vodafone|windows ce|xda |xda_)/i);
    }

    /*
     日期与时间
     date("y-m-d h:i:s");
     */
    date(format = "y-m-d h:i:s") {
        let toolsDate = new Date();

        format = format.toLowerCase();

        format = format.replace(/y/i, toolsDate.getFullYear());
        format = format.replace(/m/i, 10 < toolsDate.getMonth() ? toolsDate.getMonth() + 1 : "0" + (toolsDate.getMonth() + 1));
        format = format.replace(/d/i, 10 < toolsDate.gettoolsDate() ? toolsDate.gettoolsDate() : "0" + toolsDate.gettoolsDate());

        format = format.replace(/h/i, 10 < toolsDate.getHours() ? toolsDate.getHours() : "0" + toolsDate.getHours());
        format = format.replace(/i/i, 10 < toolsDate.getMinutes() ? toolsDate.getMinutes() : "0" + toolsDate.getMinutes());
        format = format.replace(/s/i, 10 < toolsDate.getSeconds() ? toolsDate.getSeconds() : "0" + toolsDate.getSeconds());

        return format;
    }

    /*
     时间戳
     */
    timestamp() {
        return Date.parse(new Date()) / 1000;
    }

    /*
     获得URL path name参数
     */
    pathname() {
        let toolsPathName = window.location.pathname.substr(1);
        if (toolsPathName.indexOf(".") > 0) {
            toolsPathName = toolsPathName.substr(0, toolsPathName.lastIndexOf("."));
        }
        return toolsPathName.split("/");
    }

    /*
     获得GET参数
     */
    get(name) {
        let toolsGetReg = new RegExp("(^|&)" + name + "=([^&]*)(&|$)", "i");
        let toolsGet = window.location.search.substr(1).match(toolsGetReg);
        if (toolsGet != null) {
            return decodeURIComponent(toolsGet[2]);
        };
        return null;
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

    /*
     cookie操作
     cookie(名称, 内容, 保存时间(单位天), 地址, 类型, 域名);
     设置: cookie(名称, 内容);
     读取: cookie(名称);
     移除: cookie(名称, null, -1);
     */
    cookie(name, value = "", expire = 365, path = "/", sameSite = "lax", domain = "") {
        let toolsCookie = "";
        let toolsTimestamp = new Date();
        let toolsRootDomain = window.location.host.substr(window.location.host.indexOf(".") + 1);

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
            let decodedCookie = decodeURIComponent(document.cookie);
            let ca = decodedCookie.split(";");
            for (let i = 0; i < ca.length; i++) {
                let c = ca[i];
                while (c.charAt(0) == " ") {
                    c = c.substring(1);
                }
                if (c.indexOf(name) == 0) {
                    return c.substring(name.length, c.length) ? c.substring(name.length, c.length) : null;
                }
            }
            return null;
        }
    }

    /*
     storage操作
     storage(名称, 内容);
     设置: storage(名称, 内容);
     读取: storage(名称);
     移除: storage(名称, null);
     */
    storage(name, value = "") {
        if (!window.localStorage) {
            alert("浏览器不支持localStorage");
            return null;
        }

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
        if (!window.sessionStorage) {
            alert("浏览器不支持sessionStorage");
            return null;
        }

        if (name && value) {
            window.sessionStorage.setItem(name, JSON.stringify(value));
        } else if (name && null === value) {
            window.sessionStorage.removeItem(name);
        } else if (name) {
            let value = window.sessionStorage.getItem(name);
            return value ? JSON.parse(value) : null;
        }
    }

    /* 组合数组,对象等类型数据 */
    extend(destination, source) {
        for (let property in source) {
            if ("Array" === Object.prototype.toString.call(source[property]).slice(8, -1)) {
                destination[property] = JSON.parse(JSON.stringify(source[property]));
            } else if ("Object" === Object.prototype.toString.call(source[property]).slice(8, -1)) {
                // 递归
                destination[property] = source[property];
                // destination[property] = this.extend([], source[property]);
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
