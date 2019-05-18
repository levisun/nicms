<?php
/**
 *
 * 上传类
 *
 * @package   NICMS
 * @category  app\library
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */
declare (strict_types = 1);

namespace app\library;

use think\Response;
use think\exception\HttpResponseException;
use think\facade\Config;
use think\facade\Env;
use think\facade\Log;
use think\facade\Request;
use app\library\Base64;

class Download
{

    /**
     * 构造方法
     * @access public
     * @param  string $_input_name
     * @return void
     */
    public function __construct()
    { }

    /**
     * 下载地址
     * @access public
     * @param  string $_file
     * @return string
     */
    public function url(string $_file): string
    {
        $_file = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $_file);
        $_file = preg_replace_callback(['/[\\\\\/]+/u', '/[\.]{2,}/u'], function (array $matches) {
            return DIRECTORY_SEPARATOR;
        }, $_file);
        $_file = trim($_file, DIRECTORY_SEPARATOR);

        $file_name = app()->getRootPath() . 'public' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . $_file;
        if (is_file($file_name)) {
            return Config::get('app.api_host') . '/download.do?file=' . urlencode(Base64::encrypt($_file, Request::ip() . date('Ymd'))) . '&timestamp=' . time();
        } else {
            return url('404');
        }
    }

    /**
     * 文件下载
     * @access public
     * @param
     * @return
     */
    public function file(string $_file)
    {
        if ($_file && $file_name = Base64::decrypt($_file, Request::ip() . date('Ymd'))) {
            if (preg_match('/^[A-Za-z0-9_\-\/\\\\]+\.[A-Za-z]{3,4}$/u', $file_name)) {
                // 删除多余"/|\"
                $file_name = preg_replace('/([\/\\\]){2,}/si', DIRECTORY_SEPARATOR, $file_name);

                $file_name = app()->getRootPath() . 'public' . DIRECTORY_SEPARATOR .
                    'uploads' . DIRECTORY_SEPARATOR . $file_name;

                $ext = explode(',', Config::get('app.upload_type', 'gif,jpg,png,zip,rar'));

                clearstatcache();
                if (is_file($file_name) && in_array(pathinfo($file_name, PATHINFO_EXTENSION), $ext)) {
                    return Response::create($file_name, 'file')
                        ->name(md5(pathinfo($file_name, PATHINFO_BASENAME) . date('Ymd')))
                        ->isContent(false)
                        ->expire(180);
                }
            }
        }

        $log  = '[API] 下载文件:' . Request::param('file', 'null');
        $log .= ' 本地地址:' . $file_name . PHP_EOL;
        $log .= 'PARAM:' . json_encode(Request::param('', '', 'trim'), JSON_UNESCAPED_UNICODE);
        Log::record($log, 'alert')->save();

        $error = '<style type="text/css">*{padding:0; margin:0;}body{background:#fff; font-family:"Century Gothic","Microsoft yahei"; color:#333;font-size:18px;}section{text-align:center;margin-top: 50px;}h2,h3{font-weight:normal;margin-bottom:12px;margin-right:12px;display:inline-block;}</style><title>404</title><section><h2>404</h2><h3>Oops! Page not found.</h3></section>';
        return Response::create($error, '', 404);


        return false;
    }
}
