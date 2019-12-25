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

    jQuery.getParam = function (key) {
        var reg = new RegExp("(^|&)" + key + "=([^&]*)(&|$)");
        var result = window.location.search.substr(1).match(reg);
        return result ? decodeURIComponent(result[2]) : null;
    };

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
        _params.data.append('appid', NICMS.api.appid);

        if ('POST' == _params.type || 'post' == _params.type) {
            _params.data.append('__token__', jQuery('meta[name="csrf-token"]').attr('content'));
        }

        let newData = [];
        let items = _params.data.entries();
        while (item = items.next()) {
            if (item.done) {
                break;
            }
            if (item.value[1]) {
                newData.push({ name: item.value[0], value: item.value[1] });
            }
        }
        _params.data.append('sign', jQuery.sign(newData));

        // 设置头部
        _params.beforeSend = function (xhr) {
            xhr.setRequestHeader('Accept', 'application/vnd.' + jQuery('meta[name="csrf-root"]').attr('content') + '.v' + jQuery('meta[name="csrf-version"]').attr('content') + '+json');
            xhr.setRequestHeader('Authorization', jQuery('meta[name="csrf-authorization"]').attr('content'));
        }

        _params.complete = function (xhr) {
            if ('undefined' !== typeof (xhr.responseText)) {
                var result = JSON.parse(xhr.responseText);
                if ('undefined' !== typeof (result.token)) {
                    jQuery('meta[name="csrf-token"]').attr('content', result.token);
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

        _params.data.push({ name: 'appid', value: NICMS.api.appid });
        _params.data.push({ name: 'sign_type', value: 'md5' });

        if ('POST' == _params.type || 'post' == _params.type) {
            _params.data.push({ name: '__token__', value: jQuery('meta[name="csrf-token"]').attr('content') });
            _params.data.push({ name: 'timestamp', value: jQuery.timestamp() });
        }

        _params.data.push({ name: 'sign', value: jQuery.sign(_params.data) });

        // 设置头部
        _params.beforeSend = function (xhr) {
            xhr.setRequestHeader('Accept', 'application/vnd.' + jQuery('meta[name="csrf-root"]').attr('content') + '.v' + jQuery('meta[name="csrf-version"]').attr('content') + '+json');
            xhr.setRequestHeader('Authorization', jQuery('meta[name="csrf-authorization"]').attr('content'));
        }

        _params.complete = function (xhr) {
            if ('undefined' !== typeof (xhr.responseText)) {
                var result = JSON.parse(xhr.responseText);
                if ('undefined' !== typeof (result.token)) {
                    jQuery('meta[name="csrf-token"]').attr('content', result.token);
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
        var sign = '';
        jQuery.each(_data, function (i, field) {
            if ('function' != typeof (field.value) && 'undefined' != typeof (field.value) && '' != field.value) {
                if (field.name.indexOf('[') > 0 || 'object' == typeof (field.value)) {
                    // sign += field.name + '=Array&';
                } else {
                    sign += field.name + '=' + field.value + '&';
                }
            }
        });
        sign = sign.substr(0, sign.length - 1);
        sign += jQuery('meta[name="csrf-appsecret"]').attr('content');
        // console.log(sign);

        return md5(sign);
    };

    jQuery.set_cookie = function (cname, cvalue, exdays) {
        var d = new Date();
        d.setTime(d.getTime() + (exdays * 24 * 60 * 60 * 1000));
        var expires = "expires=" + d.toUTCString();
        document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/";
    };

    jQuery.get_cookie = function (cname) {
        var name = cname + '=';
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

    jQuery.in_array = function (needle, haystack) {
        for (var index in haystack) {
            if (needle == haystack[index]) {
                return true;
            }
        }
        return false;
    };

    jQuery.filter_str = function (str)
    {
        var pattern = new RegExp("[`~!@#$^&\\-*\"()=|{}':;,\\[\\].<>/?~！@#￥……&*（）——丨{}【】‘；：”“'。，、？%+_]");
        var specialStr = '';
        for (let i = 0; i < str.length; i++) {
            specialStr += str.substr(i, 1).replace(pattern, '');
        }
        return specialStr;
    }
}));
