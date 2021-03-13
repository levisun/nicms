(function (factory) {
    if (typeof define === 'function' && define.amd) {
        // AMD
        define(['jquery'], factory);
    } else if (typeof exports === 'object') {
        // CommonJS
        factory(require('jquery'));
    } else {
        // Browser globals
        factory(jQuery);
    }
}(function (jQuery) {

    jQuery.scrollMore = function (_flag, _callable) {
        var bool = "scroll-more-" + _flag + "-bool";
        jQuery("body").attr(bool, "true");

        jQuery(window).scroll(function () {
            var is = jQuery("body").attr(bool);
            if (is == "true" && jQuery(window).scrollTop() >= (jQuery(document).height() - jQuery(window).height()) - 100) {
                jQuery("body").attr(bool, "false");
                setTimeout(function () {
                    jQuery("body").attr(bool, "true");
                }, 1500);
                _callable();
            }
        });
    }

    jQuery.uiToast = function (_tips, _time = 1.5) {
        var html = '<style type="text/css">.ui-toast-mask{position:fixed;top:0;left:0;right:0;bottom:0;z-index:99;background:rgba(0,0,0,.2);}.ui-toast-tips{transform:translateZ(0) translateY(-500%);background:rgba(0,0,0,.7);color:#fff;font-size:14px;width:30%;line-height:1.5em;margin:0 auto;box-sizing:border-box;padding:10px;text-align:center;border-radius:4px;z-index:100;}</style><div class="ui-toast-mask"></div><div class="ui-toast-tips">' + _tips + "</div>";
        jQuery('body').append(html);

        setTimeout(function () {
            jQuery('.ui-toast-mask').remove();
            jQuery('.ui-toast-tips').remove();
        }, _time * 1000);
    };

    /**
     * 上传
     */
    jQuery.upload = function (_params) {
        var defaults = {
            push: false,                        // 添加历史记录
            replace: false,                     // 替换历史记录
            scrollTo: false,                    // 是否回到顶部 可定义顶部像素
            requestUrl: window.location.href,   // 重写地址
            type: 'POST',
            async: false,
            cache: false,
            processData: false,
            // contentType: 'application/x-www-form-urlencoded',
            contentType: false,
            data: []
        };

        _params = jQuery.extend(true, defaults, _params);

        _params.data.append('timestamp', jQuery.timestamp());
        _params.data.append('appid', NICMS.app_id);

        if ('POST' == _params.type || 'post' == _params.type) {
            _params.data.append('__token__', jQuery.get_cookie('CSRF_TOKEN'));
        }

        if (jQuery.get_cookie('USER_TOKEN')) {
            _params.data.append('token', jQuery.get_cookie('USER_TOKEN'));
        }

        let newData = [];
        let items = _params.data.entries();
        while (item = items.next()) {
            if (item.done) {
                break;
            }
            newData.push({ name: item.value[0], value: item.value[1] });
        }
        _params.data.append('sign', jQuery.sign(newData));

        // 设置头部
        _params.beforeSend = function (xhr) {
            xhr.setRequestHeader('Accept', 'application/vnd.' + NICMS.app_name + '.v' + NICMS.api_version + '+json');
            xhr.setRequestHeader('Authorization', 'Bearer ' + window.atob(window.sessionStorage.getItem('XSRF_AUTHORIZATION')));
        }

        _params.complete = function (xhr) {
            if ('undefined' !== typeof (xhr.responseText)) {
                var result = JSON.parse(xhr.responseText);
                if ('undefined' !== typeof (result.csrf_token)) {
                    jQuery.set_cookie('CSRF_TOKEN', result.csrf_token);
                }
            }
        }

        var xhr = jQuery.ajax(_params);

        if (xhr.readyState > 0) {
            // 添加历史记录
            if (_params.push === true) {
                window.history.pushState(null, document.title, _params.requestUrl);
            }

            // 替换历史记录
            else if (_params.replace === true) {
                window.history.replaceState(null, document.title, _params.requestUrl);
            }
        }

        return xhr;
    };

    /**
     * 异步请求
     */
    jQuery.pjax = function (_params) {
        var defaults = {
            push: false,                        // 添加历史记录
            replace: false,                     // 替换历史记录
            scrollTo: false,                    // 是否回到顶部 可定义顶部像素
            requestUrl: window.location.href,   // 重写地址
            type: 'GET',
            contentType: 'application/x-www-form-urlencoded',
            data: []
        };

        _params = jQuery.extend(true, defaults, _params);

        _params.data.push({ name: 'appid', value: NICMS.app_id });
        _params.data.push({ name: 'sign_type', value: 'md5' });

        if ('POST' == _params.type || 'post' == _params.type) {
            _params.data.push({ name: 'timestamp', value: jQuery.timestamp() });
            _params.data.push({ name: '__token__', value: jQuery.get_cookie('CSRF_TOKEN') });
        }

        if (jQuery.get_cookie('USER_TOKEN')) {
            _params.data.push({ name: 'token', value: jQuery.get_cookie('USER_TOKEN') });
        }

        _params.data.push({ name: 'sign', value: jQuery.sign(_params.data) });

        // 设置头部
        _params.beforeSend = function (xhr) {
            xhr.setRequestHeader('Accept', 'application/vnd.' + NICMS.app_name + '.v' + NICMS.api_version + '+json');
            xhr.setRequestHeader('Authorization', 'Bearer ' + window.atob(window.sessionStorage.getItem('XSRF_AUTHORIZATION')));
        }

        _params.complete = function (xhr) {
            if ('undefined' !== typeof (xhr.responseText)) {
                var result = JSON.parse(xhr.responseText);
                if ('undefined' !== typeof (result.csrf_token)) {
                    jQuery.set_cookie('CSRF_TOKEN', result.csrf_token);
                }
            }
        }

        var xhr = jQuery.ajax(_params);

        if (xhr.readyState > 0) {
            // 添加历史记录
            if (_params.push === true) {
                window.history.pushState(null, document.title, _params.requestUrl);
            }

            // 替换历史记录
            else if (_params.replace === true) {
                window.history.replaceState(null, document.title, _params.requestUrl);
            }
        }

        return xhr;
    };

    /**
     * 时间戳
     */
    jQuery.timestamp = function () {
        var timestamp = Date.parse(new Date());
        return timestamp / 1000;
    };

    /**
     * 生成签名
     */
    jQuery.sign = function (_data) {
        var compare = function (obj1, obj2) {
            var val1 = obj1.name;
            var val2 = obj2.name;
            if (val1 < val2) {
                return -1;
            } else if (val1 > val2) {
                return 1;
            } else {
                return 0;
            }
        }
        _data = _data.sort(compare);
        _data = _data.filter(function (item, index, self) {
            return self.indexOf(item) == index;
        });

        var sign = '';
        jQuery.each(_data, function (i, field) {
            // console.log(typeof (field.value));
            if (
                field.name.indexOf('[') == -1 &&
                'function' != typeof (field.value) &&
                'object' != typeof (field.value) &&
                'undefined' != typeof (field.value) &&
                '' !== field.value) {
                sign += field.name + '=' + field.value + '&';
            }
        });
        sign = sign.substr(0, sign.length - 1);
        sign += window.sessionStorage.getItem('XSRF_TOKEN');
        // console.log(sign);

        return md5(sign);
    };

    /**
     * 获得当前URL地址
     */
    jQuery.url = function () {
        return "\/\/" + window.location.host + window.location.pathname + window.location.search;
    };

    /**
     * 判断是否IOS端访问
     */
    jQuery.is_ios = function () {
        return !!navigator.userAgent.match(/(Mac OS)/i);
    };

    /**
     * 判断是否安卓端访问
     */
    jQuery.is_android = function () {
        return !!navigator.userAgent.match(/(Android)/i);
    };

    /**
     * 判断是否移动端访问
     */
    jQuery.is_mobile = function () {
        return !!navigator.userAgent.match(/(blackberry|configuration\/cldc|hp |hp-|htc |htc_|htc-|iemobile|kindle|midp|mmp|motorola|mobile|nokia|opera mini|opera |Googlebot-Mobile|YahooSeeker\/M1A1-R2D2|android|iphone|ipod|mobi|palm|palmos|pocket|portalmmm|ppc;|smartphone|sonyericsson|sqh|spv|symbian|treo|up.browser|up.link|vodafone|windows ce|xda |xda_)/i);
    };

    /**
     * 判断是否微信端访问
     */
    jQuery.is_wechat = function () {
        return !!navigator.userAgent.match(/(MicroMessenger)/i);
    };

    /**
     * 获得GET值
     */
    jQuery.get_url_query = function (name) {
        let reg = new RegExp("(^|&)" + name + "=([^&]*)(&|$)");

        //search,查询？后面的参数，并匹配正则
        let result = window.location.search ? window.location.search.substr(1).match(reg) : null;
        if (result != null) {
            return decodeURIComponent(result[2]);
        } else {
            return null;
        }
    };

    /**
     * 设置浏览器本地存储
     */
    jQuery.set_storage = function (name, value) {
        window.localStorage.setItem(name, JSON.stringify(value));
    };

    /**
     * 获得浏览器本地存储
     */
    jQuery.get_storage = function (name, devalue = '') {
        let value = window.localStorage.getItem(name);
        return value ? JSON.parse(value) : devalue;
    };

    /**
     * 删除浏览器本地存储
     */
    jQuery.remove_storage = function (name) {
        window.localStorage.removeItem(name);
    };

    /**
     * 清空浏览器本地存储
     */
    jQuery.clear_storage = function () {
        window.localStorage.clear();
    };

    /**
     * 设置COOKIE
     */
    jQuery.set_cookie = function (name, value, expire = 1, domain = '') {
        var d = new Date();
        d.setTime(d.getTime() + (expire * 24 * 60 * 60 * 1000));
        // domain = domain ? domain : '.' + window.location.host.substr(window.location.host.indexOf('.') + 1);
        domain = domain ? domain : window.location.host;
        document.cookie = name + '=' + value + ';expires=' + d.toUTCString() + ';path=/;SameSite=lax;domain=' + domain;

    };

    /**
     * 获得COOKIE
     */
    jQuery.get_cookie = function (name) {
        name += '=';
        var decodedCookie = decodeURIComponent(document.cookie);
        var ca = decodedCookie.split(';');
        for (var i = 0; i < ca.length; i++) {
            var c = ca[i];
            while (c.charAt(0) == ' ') {
                c = c.substring(1);
            }
            if (c.indexOf(name) == 0) {
                return c.substring(name.length, c.length);
            }
        }
        return '';
    }

    /**
     * 删除COOKIE
     */
    jQuery.remove_cookie = function (name) {
        var domain = '.' + window.location.host.substr(window.location.host.indexOf('.') + 1);
        document.cookie = name + '=;expires=-1440;path=/;SameSite=lax;domain=' + domain;
    };

    jQuery.in_array = function (needle, haystack) {
        for (var index in haystack) {
            if (needle == haystack[index]) {
                return true;
            }
        }
        return false;
    };

    /**
     * 过滤
     */
    jQuery.filter_str = function (str) {
        var pattern = new RegExp("[^a-zA-Z0-9\u4e00-\u9fa5 ]+");
        var specialStr = '';
        for (let i = 0; i < str.length; i++) {
            specialStr += str.substr(i, 1).replace(pattern, '');
        }
        return encodeURI(specialStr);
    }
}));
