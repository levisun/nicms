<?php
/**
 *
 * 控制层
 * Api
 *
 * @package   NICMS
 * @category  app\controller
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */
declare (strict_types = 1);

namespace app\controller;

use think\Response;
use think\exception\HttpResponseException;
use think\facade\Env;
use think\facade\Log;
use think\facade\Request;
use app\library\Async;
use app\library\Base64;
use app\library\Ip;

class Api extends Async
{

    /**
     * 查询接口
     * @access public
     * @param  string $module API分层名
     * @return void
     */
    public function query(string $module = 'cms')
    {
        if (Request::server('HTTP_REFERER', false) && Request::isGet() && $module) {
            $this->setModule($module)->run();
        } else {
            $this->error('request error');
        }
    }

    /**
     * 操作接口
     * @access public
     * @param  string $name API分层名
     * @return void
     */
    public function handle(string $module = 'cms')
    {
        if (Request::server('HTTP_REFERER', false) && Request::isPost() && $module) {
            $this->setModule($module)->run();
        } else {
            $this->error('request error');
        }
    }

    /**
     * 上传接口
     * @access public
     * @param
     * @return void
     */
    public function upload(string $module = 'cms')
    {
        if (Request::server('HTTP_REFERER', false) && Request::isPost() && $module && !empty($_FILES)) {
            $this->setModule($module)->run();
        } else {
            $this->error('request error');
        }
    }

    /**
     * IP地址信息接口
     * @access public
     * @param
     * @return void
     */
    public function ip()
    {
        if (Request::isGet() && $ip = Request::param('ip', false)) {
            $ip = (new Ip)->info($ip);
            $this->success('success', $ip);
        } else {
            $this->error('request error');
        }
    }

    /**
     * 下载接口
     * @access public
     * @param
     * @return void
     */
    public function download()
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

        die('<script type="text/javascript"></script>');
    }
}
