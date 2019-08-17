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

    jQuery.getForm = function (_element) {
        var array = jQuery(_element).serializeArray();
        var form_data = {};
        for (var index in array) {
            var name = array[index]['name'];
            var value = array[index]['value'];
            if (name) {
                form_data[name] = value;
            }
        }
        return form_data;
    };

    jQuery.uiToast = function (_tips, _time = 1.5) {
        var html = '<style type="text/css">.ui-toast-mask{position:fixed;top:0;left:0;right:0;bottom:0;z-index:99;background:rgba(0,0,0,.2);}.ui-toast-tips{position: fixed;top:35%;left:40%;transform:translateZ(0) translateY(-100%);background:rgba(0,0,0,.7);color:#fff;font-size:14px;width:30%;line-height: 1.5em;margin:0 auto;box-sizing border-box;padding:10px;text-align:center;border-radius:4px;z-index:100;}</style><div class="ui-toast-mask"></div><div class="ui-toast-tips">' + _tips + "</div>";
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
        var data = _params.data;
        var timestamp = jQuery.timestamp();
        _params.data = new FormData(document.getElementById(_params.file));
        _params.data.append('appid', NICMS.api.appid);
        _params.data.append('timestamp', timestamp);
        _params.data.append('__token__', jQuery('meta[name="csrf-token"]').attr('content'));
        for (var index in data) {
            _params.data.append(index, data[index]);
        }
        _params.data.append('sign', jQuery.sign({
            appid: NICMS.api.appid,
            timestamp: timestamp,
            method: data.method,
        }));
        _params.type = 'post';
        _params.async = false;
        _params.cache = false;
        _params.processData = false;
        _params.contentType = false;
        jQuery.pjax(_params);
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
            data: {
                appid: NICMS.api.appid,
                // timestamp: jQuery.timestamp()
            }
        };

        _params = jQuery.extend(true, defaults, _params);

        _params.data.sign = jQuery.sign(_params.data);

        if ('POST' == _params.type || 'post' == _params.type) {
            _params.data.__token__ = jQuery('meta[name="csrf-token"]').attr('content');
        }

        // 设置头部
        _params.beforeSend = function (xhr) {
            xhr.setRequestHeader('Accept', 'application/vnd.' + NICMS.api.root + '.v' + NICMS.api.version + '+json');
            xhr.setRequestHeader('Authorization', NICMS.api.authorization);
        }

        _params.complete = function (xhr) {
            var result = JSON.parse(xhr.responseText);
            if ('undefined' !== typeof (result.token)) {
                jQuery('meta[name="csrf-token"]').attr('content', result.token);
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
     * 签名
     */
    jQuery.sign = function (_params) {
        // 先用Object内置类的keys方法获取要排序对象的属性名，再利用Array原型上的sort方法对获取的属性名进行排序，newkey是一个数组
        var newkey = Object.keys(_params).sort();

        // 创建一个新的对象，用于存放排好序的键值对
        var newObj = {};
        for (var i = 0; i < newkey.length; i++) {
            // 遍历newkey数组
            newObj[newkey[i]] = _params[newkey[i]];
            // 向新创建的对象中按照排好的顺序依次增加键值对
        }

        var sign = '';
        for (var index in newObj) {
            if (index == 'appid' || index == 'sign_type' || index == 'timestamp' || index == 'method') {
                sign += index + '=' + newObj[index] + '&';
            }
        }
        sign = sign.substr(0, sign.length - 1);
        sign += NICMS.api.appsecret;
        sign = md5(sign);

        return sign;
    };
}));
