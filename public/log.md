~~~
composer create-project topthink/think nicms 6.0.*-dev
composer update topthink/framework
~~~

~~~
19-04-04
A. 重构 app\library\Session 方法,支持TP6 Session
B. 修改 app\library\Backup 备份文件保存路径错误问题.
C. 重新定义 app\libraryGarbage 垃圾文件保留时长.
D. 网站信息中添加版权信息.
E. app\library\Template 新增setTheme() setReplace()方法.
~~~

~~~
19-04-03
A. 密钥改为自主定义不再使用系统生成,避免程序换环境用户信息和加密信息不能正常使用并解密的问题.
~~~
