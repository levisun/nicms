<?php

version_compare(PHP_VERSION, '7.1.0', '>=') or die('系统需要PHP7.1+版本! 当前PHP版本:' . PHP_VERSION . '.');
version_compare(app()->version(), '6.0.0RC4', '>=') or die('系统需要ThinkPHP 6.0+版本! 当前ThinkPHP版本:' . app()->version() . '.');
extension_loaded('pdo') or die('请开启 pdo 模块!');
extension_loaded('pdo_mysql') or die('请开启 pdo_mysql 模块!');
class_exists('ZipArchive') or die('空间不支持 ZipArchive 方法,系统备份功能无法使用.');
function_exists('file_put_contents') or die('空间不支持 file_put_contents 函数,系统无法写文件.');
function_exists('fopen') or die('空间不支持 fopen 函数,系统无法读写文件.');
get_extension_funcs('gd') or die('空间不支持 gd 模块,图片水印和缩略生成功能无法使用.');

echo __DIR__;
