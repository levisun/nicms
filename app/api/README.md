API说明文档
===============

## 使用方法

~~~
模板中必须使用{tags:head /}标签添加头部
~~~

## API请求系统参数

|参数|类型|必填|作用|示例|
|Accept|header|yes|api版本与返回数据类型|Accept:application/vnd.niphp.v1.0.1+json|
|Authorization|header|yes|API权限校验,JWT生成校验|Authorization: Bearer xxxx.xxx.xx|
|__token__|string|POST yes|表单令牌|__token__=xxxx|
|appid|int|yes|appid|appid=100002|
|method|string|yes|接口名|method=article.category.query|
|sign_type|string|yes|签名类型|sign_type=md5|
|timestamp|int|yes|请求时间戳|timestamp=1578667111|


## code对照表

> `特殊错误` 当返回为HTTP500或404时表示在运行method方法存在错误,请在具体method方法中排除错误

> `20001` Header authorization 认证信息为空

> `20002` Header authorization 认证校验失败

> `20003` Header authorization SessionID 校验失败

> `20004` Header accept 校验或解析失败

> `20005` Header accept 域名校验或解析失败

> `20006` Header accept 解析版本与数据类型失败

> `20007` Header accept 校验返回数据类型失败

> `21001` param appid 解析错误

> `21002` param appid 校验错误

> `22001` param sign_type 校验错误

> `22002` param sign 为空或格式错误

> `22003` param sign 校验错误

> `24001` param token 表单令牌校验错误

> `25001` param method API方法为空或格式错误

> `25002-25003` param method API方法不存在

> `26001` param method API方法权限错误

> `27001` server HTTP_REFERER 请求来源错误

> `27002` server POST GET FILES 请求参数错误

> `27003` session client_token 请求客户端令牌为空或错误

> `30001` validate API方法数据校验器不存在

> `30002` validate API方法数据校验错误

> `31001` uploadFile 上传文件为空

> `31002` uploadFile 上传文件错误


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
