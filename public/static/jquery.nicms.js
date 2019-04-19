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

    /**
     * 异步请求
     */
    jQuery.pjax = function (_params) {
        var defaults = {
            push: false,                        // 添加历史记录
            replace: false,                     // 替换历史记录
            requestUrl: window.location.href,   // 重写地址
            type: 'GET',
            contentType: 'application/x-www-form-urlencoded'
        };
        _params = jQuery.extend(true, defaults, _params);

        _params.data.sign = jQuery.sign({
            method: _params.data.method
        });

        // 设置头部
        _params.beforeSend = function (xhr) {
            xhr.setRequestHeader('Accept', 'application/vnd.' + NICMS.api.root + '.v' + NICMS.api.version + '+json');
            xhr.setRequestHeader('Authorization', NICMS.api.authorization);
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
    jQuery.timestamp = function(){
        var timestamp = Date.parse(new Date());
        return timestamp / 1000;
    };

    /**
     * 签名
     */
    jQuery.sign = function(_params){
        // 先用Object内置类的keys方法获取要排序对象的属性名，再利用Array原型上的sort方法对获取的属性名进行排序，newkey是一个数组
        var newkey = Object.keys(_params).sort();

        // 创建一个新的对象，用于存放排好序的键值对
        var newObj = {};
        for(var i = 0; i < newkey.length; i++) {
            // 遍历newkey数组
            newObj[newkey[i]] = _params[newkey[i]];
            // 向新创建的对象中按照排好的顺序依次增加键值对
        }

        var sign = '';
        for (var index in newObj) {
            sign += index + '=' + newObj[index] + '&';
        }
        sign = sign.substr(0, sign.length - 1);
        sign = md5(sign);

        return sign;
    };
}));
