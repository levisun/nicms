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

use think\facade\Env;
use think\facade\Request;

class Download
{
    private $file = null;
    private $timestamp = null;

    /**
     * 构造方法
     * @access public
     * @param  string $_input_name
     * @return void
     */
    public function __construct()
    {
        $this->file = Request::param('file', false);
        $this->timestamp = (int) Request::param('timestamp/f', 0);
        $this->timestamp = date('Ymd', $this->timestamp);
        $date = date('Ymd');
    }

    public function file()
    {
        $file = Request::param('file', false);
        $timestamp = (int) Request::param('timestamp/f', 0);
        $timestamp = date('Ymd', $timestamp);
        $date = date('Ymd');

        if (Request::isGet() && $file && $timestamp == $date) {
            $de_file = Base64::decrypt($file);
            if ( preg_match('/^([\-_\\/A-Za-z0-9]+)(\.)([A-Za-z]{3,4})$/u', $de_file)) {
                $de_file = preg_replace('/([\/\\\]){2,}/si', DIRECTORY_SEPARATOR, $de_file);
                $de_file = app()->getRootPath() . 'public' . DIRECTORY_SEPARATOR .
                            'uploads' . DIRECTORY_SEPARATOR . $de_file;

                $ext = Env::get('app.upload_type', 'gif,jpg,png,zip,rar');
                $ext = explode(',', $ext);
                clearstatcache();
                if (is_file($de_file) && in_array(pathinfo($de_file,  PATHINFO_EXTENSION), $ext)) {
                    $response =
                    Response::create($de_file, 'file')
                    ->name(md5($file . time()))
                    ->isContent(false)
                    ->expire(300);
                    throw new HttpResponseException($response);
                } else {
                    echo 'file not found';
                }
            } else {
                echo 'file not found';
            }

            $log = '[API] 下载文件:' . $file;
            $log .= isset($de_file) ? ' 本地地址:' . $de_file : '';
            $log .= PHP_EOL . 'PARAM:' . json_encode(Request::param('', '', 'trim'), JSON_UNESCAPED_UNICODE);
            Log::record($log, 'alert')->save();
        }
    }
}
