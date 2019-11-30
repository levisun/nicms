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
        // return jQuery(_element).serializeArray();
        // var form_data = {};
        // jQuery.each(array, function (i, field) {
        //     // form_data.push({field.name field.value});
        //     // form_data[field.name] = field.value;
        // });


        // for (var index in array) {
        //     var name = array[index]['name'];
        //     var value = array[index]['value'];
        //     if (name) {
        //         form_data[name] = value;
        //     }
        // }
        // return form_data;
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

        let newkey = [];
        for (let index in _params.data) {
            console.log(index);
        }
        var newkey = Object.keys(_params.data).sort();
        var sign = '';
        for (var i = 0; i < newkey.length; i++) {
            console.log(newkey[i]);
            sign += newkey[i] + '=' + _params.data[newkey[i]] + '&';
        }
        sign = sign.substr(0, sign.length - 1);
        sign += jQuery('meta[name="csrf-appsecret"]').attr('content');
        console.log(sign);
        _params.data.append('sign', md5(sign));

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

        if ('POST' == _params.type || 'post' == _params.type) {
            _params.data.push({ name: '__token__', value: jQuery('meta[name="csrf-token"]').attr('content') });
        }

        // 生成签名
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
        _params.data = _params.data.sort(compare);
        var sign = '';
        jQuery.each(_params.data, function (i, field) {
            sign += field.name + '=' + field.value + '&';
        });
        sign = sign.substr(0, sign.length - 1);
        sign += jQuery('meta[name="csrf-appsecret"]').attr('content');

        _params.data.push({ name: 'sign', value: md5(sign) });

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
}));
