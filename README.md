nicms1.x 未完成项目,正在拼命中...
===============

[![PHP Version](https://img.shields.io/badge/php-%3E%3D7.1-8892BF.svg)](http://www.php.net/)
[![996.icu](https://img.shields.io/badge/link-996.icu-red.svg)](https://996.icu)
[![LICENSE](https://img.shields.io/badge/license-Anti%20996-blue.svg)](https://github.com/996icu/996.ICU/blob/master/LICENSE)

## 运行环境

> `PHP7.3+` `Mysql` `PDO` `Apache` `Nginx`

## 主要特性

> 基于[ThinkPHP6+](https://github.com/top-think/framework) 框架开发

> 基于[RBAC]验证的权限

> 基于[AdminLTE](https://adminlte.io) 二次开发

> 基于[Bootstrap](http://getbootstrap.com) 开发，自适应手机、平板、PC

> 基于[jQuery](http://jquery.com)

> 基于[CKEditor](https://ckeditor.com) 开发

> 基于[wangEditor](http://www.wangeditor.com) 开发

> 多语言支持 未完成

> 第三方登录支持(QQ、微信、微博) 未完成


## 安装方式

~~~
composer install

ThinkPHP6
composer create-project topthink/think nicms
composer update topthink/framework

依赖
composer require topthink/think-multi-app
composer require topthink/think-image
composer require topthink/think-captcha
composer require topthink/think-view
composer require lcobucci/jwt
composer require lizhichao/word
composer require symfony/browser-kit
composer require symfony/http-client
composer require phpoffice/phpspreadsheet



composer require michelf/php-markdown

composer require phpoffice/phpword


composer require overtrue/pinyin
composer require overtrue/wechat
composer require overtrue/easy-sms
composer require overtrue/socialite
composer require phpmailer/phpmailer
composer require anerg2046/sns_auth
~~~

## composer 命令

> `outdated` 检测已安装的包是否有新版本

> `update` 更新包

> `require` 安装包

> `remove` 移除包

## 版权信息

版权所有Copyright © 2013-2020 by [失眠小枕头](https://github.com/levisun/nicms)

All rights reserved。

更新日志 [changelog](changelog.md)

更多细节参阅 [LICENSE](LICENSE)
