$traceurRuntime.ModuleStore.getAnonymousModule(function () {
    "use strict";
    var tools = function () {
        function tools() { }
        return ($traceurRuntime.createClass)(tools, {
            ajax: function (options) {
                options = this.extend({
                    type: "GET",
                    url: null,
                    async: true,
                    cache: true,
                    username: null,
                    password: null,
                    timeout: 60000,
                    header: [],
                    data: null,
                    responseType: "json",
                    push: false,
                    replace: false,
                    requestUrl: null,
                    success: function () { },
                    error: function () { }
                }, options);
                if ("get" === options.type.toLowerCase() && options.data) {
                    var query = "";
                    for (var key in options.data) {
                        if (Object.hasOwnProperty.call(options.data, key)) {
                            var element = options.data[key];
                            query += "&" + key + "=" + encodeURIComponent(element);
                        }
                    }
                    query = query.substr(1);
                    options.data = null;
                    options.url += -1 === options.url.indexOf("?") ? "?" + query : query;
                }
                if (false === options.cache) {
                    options.url += "_" + this.timestamp();
                }
                var xhr = new window.XMLHttpRequest();
                xhr.timeout = options.timeout;
                xhr.open(options.type, options.url, options.async, options.username, options.password);
                if (options.responseType) {
                    xhr.overrideMimeType(options.responseType);
                }
                if (options.mimeType && xhr.overrideMimeType) {
                    xhr.overrideMimeType(options.mimeType);
                }
                for (var index in options.header) {
                    if ("Object" === Object.prototype.toString.call(options.header[index]).slice(8, -1)) {
                        xhr.setRequestHeader(options.header[index].name.toLowerCase(), options.header[index].value);
                    } else {
                        xhr.setRequestHeader(index.toLowerCase(), options.header[index]);
                    }
                }
                xhr.addEventListener("load", function () {
                    try {
                        if (4 == xhr.readyState && 400 > xhr.status) {
                            if (options.requestUrl) {
                                window.history.pushState(null, document.title, options.requestUrl);
                            }
                            var result = "json" === options.responseType ? JSON.parse(xhr.response) : xhr.response;
                            options.success(result);
                        } else {
                            options.error(xhr.response);
                        }
                    } catch (error) { }
                });
                xhr.send(options.data);
            },
            isMobile: function () {
                return !!navigator.userAgent.match(/(blackberry|configuration\/cldc|hp |hp-|htc |htc_|htc-|iemobile|kindle|midp|mmp|motorola|mobile|nokia|opera mini|opera |Googlebot-Mobile|YahooSeeker\/M1A1-R2D2|android|iphone|ipod|mobi|palm|palmos|pocket|portalmmm|ppc;|smartphone|sonyericsson|sqh|spv|symbian|treo|up.browser|up.link|vodafone|windows ce|xda |xda_)/i);
            },
            water: function (imgPath, content, elementId) {
                var fontSize = arguments[3] !== (void 0) ? arguments[3] : "20px";
                var x = arguments[4] !== (void 0) ? arguments[4] : 10;
                var y = arguments[5] !== (void 0) ? arguments[5] : 10;
                var img = new Image();
                img.src = imgPath;
                img.crossOrigin = "*";
                img.onload = function () {
                    var canvas = document.createElement("canvas");
                    canvas.width = img.width;
                    canvas.height = img.height;
                    var ctx = canvas.getContext('2d');
                    ctx.drawImage(img, 0, 0, img.width, img.height);
                    ctx.textAlign = "left";
                    ctx.textBaseline = "middle";
                    ctx.font = fontSize + " Microsoft Yahei";
                    ctx.fillStyle = "rgba(255, 0, 0, 1)";
                    ctx.fillText(content, x, y);
                    document.getElementById(elementId).src = canvas.toDataURL();
                };
            },
            copy: function (content) {
                var message = arguments[1] !== (void 0) ? arguments[1] : "copy success";
                var aux = document.createElement("input");
                aux.setAttribute("value", content);
                document.body.appendChild(aux);
                aux.select();
                document.execCommand("copy");
                document.body.removeChild(aux);
                alert(message);
            },
            storage: function (name) {
                var value = arguments[1] !== (void 0) ? arguments[1] : "";
                if (name && value) {
                    window.localStorage.setItem(name, JSON.stringify(value));
                } else if (name && null === value) {
                    window.localStorage.removeItem(name);
                } else if (name) {
                    var value$__2 = window.localStorage.getItem(name);
                    return value$__2 ? JSON.parse(value$__2) : null;
                }
            },
            session: function (name) {
                var value = arguments[1] !== (void 0) ? arguments[1] : "";
                if (name && value) {
                    window.sessionStorage.setItem(name, JSON.stringify(value));
                } else if (name && null === value) {
                    window.sessionStorage.removeItem(name);
                } else if (name) {
                    var value$__3 = window.sessionStorage.getItem(name);
                    return value$__3 ? JSON.parse(value$__3) : null;
                }
            },
            cookie: function (name) {
                var value = arguments[1] !== (void 0) ? arguments[1] : "";
                var expire = arguments[2] !== (void 0) ? arguments[2] : 0;
                var path = arguments[3] !== (void 0) ? arguments[3] : "/";
                var sameSite = arguments[4] !== (void 0) ? arguments[4] : "lax";
                var domain = arguments[5] !== (void 0) ? arguments[5] : "";
                var cookie = "";
                if (name && value) {
                    cookie = name + "=" + value + ";";
                    cookie += expire ? "expires=" + this.timestamp() + expire + ";" : "";
                    cookie += "path=" + path + ";";
                    cookie += "SameSite=" + sameSite + ";";
                    cookie += domain ? domain : "." + this.rootDomain();
                    document.cookie = cookie;
                } else if (name && 0 > expire) {
                    cookie = name + "=" + null + ";";
                    cookie += "expires=-1440;";
                    cookie += "path=" + path + ";";
                    cookie += "SameSite=" + sameSite + ";";
                    cookie += domain ? domain : "." + this.rootDomain();
                    document.cookie = cookie;
                } else if (name) {
                    name += "=";
                    var decodedCookie = decodeURIComponent(document.cookie);
                    var ca = decodedCookie.split(';');
                    for (var i = 0; i < ca.length; i++) {
                        var c = ca[i];
                        while (c.charAt(0) == ' ') {
                            c = c.substring(1);
                        }
                        if (c.indexOf(name) == 0) {
                            return "null" == c.substring(name.length, c.length) ? null : c.substring(name.length, c.length);
                        }
                    }
                    return '';
                }
            },
            date: function () {
                var format = arguments[0] !== (void 0) ? arguments[0] : "y-m-d h:i:s";
                var date = new Date();
                format = format.toLowerCase();
                format = format.replace(/y/i, date.getFullYear());
                format = format.replace(/m/i, 10 < date.getMonth() ? date.getMonth() + 1 : "0" + (date.getMonth() + 1));
                format = format.replace(/d/i, 10 < date.getDate() ? date.getDate() : "0" + date.getDate());
                format = format.replace(/h/i, 10 < date.getHours() ? date.getHours() : "0" + date.getHours());
                format = format.replace(/i/i, 10 < date.getMinutes() ? date.getMinutes() : "0" + date.getMinutes());
                format = format.replace(/s/i, 10 < date.getSeconds() ? date.getSeconds() : "0" + date.getSeconds());
                return format;
            },
            timestamp: function () {
                return Date.parse(new Date()) / 1000;
            },
            pathname: function () {
                var result = window.location.pathname.substr(1);
                if (result.indexOf(".") > 0) {
                    result = result.substr(0, result.lastIndexOf("."));
                }
                return result.split("/");
            },
            get: function (name) {
                var reg = new RegExp("(^|&)" + name + "=([^&]*)(&|$)", "i");
                var result = window.location.search.substr(1).match(reg);
                if (result != null) {
                    return decodeURIComponent(result[2]);
                }
                ;
                return null;
            },
            url: function () {
                var type = arguments[0] !== (void 0) ? arguments[0] : "href";
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
            domain: function () {
                return window.location.host;
            },
            rootDomain: function () {
                return window.location.host.substr(window.location.host.indexOf(".") + 1);
            },
            extend: function (destination, source) {
                for (var property in source) {
                    if ("Array" === Object.prototype.toString.call(source[property]).slice(8, -1)) {
                        destination[property] = JSON.parse(JSON.stringify(source[property]));
                    } else if ("Object" === Object.prototype.toString.call(source[property]).slice(8, -1)) {
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
        }, {});
    }();
    return {};
});
//# sourceURL=traceured.js
