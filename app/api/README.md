API说明文档
===============

## 使用方法

~~~
模板中必须使用`{tags:head /}`标签添加头部,否则API请求无法通过.
~~~

## API接口

> `//api.xxx.com/download.do` `GET请求` 下载接口

> `//api.xxx.com/handle.do` `POST请求` 操作接口

> `//api.xxx.com/ip.do` `GET请求` IP地址信息

> `//api.xxx.com/query.do` `GET请求` 查询接口

> `//api.xxx.com/record.do` `GET请求` 访问日志接口

> `//api.xxx.com/upload.do` `POST请求 $_FEILS变量` 上传接口

> `//api.xxx.com/verify/img.do` `GET请求` 验证码接口

> `//api.xxx.com/verify/sms.do` `GET请求` 验证码接口

> `//api.xxx.com/verify/sms_check.do` `POST请求` 校验验证码接口

## code对照表

> `特殊错误` 当返回为HTTP500或404时表示在运行method方法存在错误,请在具体method方法中排除错误

> `20001` Header authorization 认证信息为空

> `20002` Header authorization 认证校验失败

> `20003` Header authorization SessionID 校验失败

> `20004` Header accept 校验或解析失败

> `20005` Header accept 域名校验或解析失败

> `20006` Header accept 解析版本与返回数据类型失败

> `20007` Header accept 校验返回数据类型失败

> `21001` 参数 appid 解析错误

> `21002` 参数 appid 校验错误

> `22001` 参数 sign_type 校验错误(只支持md5与sha1)

> `22002` 参数 sign 为空或格式错误

> `22003` 参数 sign 校验错误

> `24001` server HTTP_REFERER 请求来源错误

> `24002` 参数 token 表单令牌校验错误

> `25001` 参数 method API方法为空或格式错误

> `25002-25003` 参数 method API方法不存在

> `26001` 参数 method API方法权限错误


## 签名算法

~~~
第一步,发送或接收的数据数名ASCII码从小到大排序.
主意:
如果参数的值为空不参与签名;
如果参数的值为数组不参与签名;
参数名区分大小写;

第二步,拼接成字符串
使用URL键值对的格式（即key1=value1&key2=value2…）拼接成字符串
[
    appid: 100002,
    method: 'xxx.xxx.xxx',
    timestamp: 1577350835,
    sign_type: 'md5',
]
appid=100002&method=xxx.xxx.xxx&timestamp=1577350835&sign_type=md5

第三步,拼接APPSECRET
appid=100002&method=xxx.xxx.xxx&timestamp=1577350835&sign_type=md5APPSECRET

第四步,根据sign_type进行字符串加密
注意签名类型只支持md5和sha1两种
md5('appid=100002&method=xxx.xxx.xxx&timestamp=1577350835&sign_type=md5APPSECRET')
~~~
