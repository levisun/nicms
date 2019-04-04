layui.define('jquery', function(exports){
    var jQuery = layui.jquery;
    var obj = {
        apiUrl: 'book/api.html',

        book_details: function (_params) {
            this.pjax({
                url: this.apiUrl,
                data: {
                    method: 'book_details',
                    id: _params.id
                },
                success: function(result){

                }
            });
        },

        book_index: function (_params) {
            this.pjax({
                url: this.apiUrl,
                data: {
                    method: 'book_index',
                    id: _params.id
                },
                success: function(result){

                }
            });
        },

        book_info: function (_params) {
            this.pjax({
                url: this.apiUrl,
                data: {
                    method: 'book_info',
                    id: _params.id
                },
                success: function(result){

                }
            });
        },

        book_list: function (_params) {
            this.pjax({
                url: this.apiUrl,
                data: {
                    method: 'book_list',
                    gender: _params.gender,     // 性别
                    type: _params.type,         // 按照不同的类型获取分类下的书籍(hot, new, reputation, over)
                    major: _params.major,       // 父分类
                    minor: _params.minor,       // 子分类
                    start: _params.start,       // 起始位置
                    limit: 20                   // 每页数量
                },
                success: function(result){

                }
            });
        },

        category: function () {
            this.pjax({
                url: this.apiUrl,
                data: {
                    method: 'category'
                },
                success: function(result){

                }
            });
        },

        pjax: function (_params) {
            var defaults = {
                push: false,                        // 添加历史记录
                replace: false,                     // 替换历史记录
                scrollTo: false,                    // 是否回到顶部 可定义顶部像素
                requestUrl: window.location.href,   // 重写地址
                type: 'GET',
                contentType: 'application/x-www-form-urlencoded'
            };
            _params = jQuery.extend(true, defaults, _params);

            // 设置头部
            _params.beforeSend = function (xhr) {
                // xhr.setRequestHeader('Host', 'api.zhuishushenqi.com');
                // xhr.setRequestHeader('Authorization', NICMS.api.authorization);
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
        },

        timestamp: function(){
            var timestamp = Date.parse(new Date());
            return timestamp / 1000;
        }
    };

    exports('book', obj);
});
