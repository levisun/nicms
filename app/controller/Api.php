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
            $ip = Ip::info($ip);
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
        if (Request::isGet() && $file = Request::param('file', false)) {
            $de_file = Base64::decrypt($file);
            if (preg_match('/^([\-_\\/A-Za-z0-9]+)(\.)([A-Za-z]{3})$/u', $de_file)) {
                $de_file = trim($de_file, '/');
                $de_file = app()->getRootPath() . 'public' . DIRECTORY_SEPARATOR .
                        'uploads' . DIRECTORY_SEPARATOR .
                        str_replace('/', DIRECTORY_SEPARATOR, $de_file);

                clearstatcache();
                $ext = ['gif', 'jpg', 'png', 'zip', 'rar'];
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
                // $this->error('file not found');
            }
        }

        $log = '[API] IP:' . Request::ip() . PHP_EOL . '下载文件: ' . $file;
        $log .= isset($de_file) ? PHP_EOL . '本地地址: ' . $de_file : '';
        Log::record($log, 'alert')->save();

        die('<script type="text/javascript"></script>');
    }
}
