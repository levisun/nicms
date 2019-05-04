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
    private $fileName = null;
    private $timestamp = null;

    /**
     * 构造方法
     * @access public
     * @param  string $_input_name
     * @return void
     */
    public function __construct()
    {}

    /**
     * 下载地址
     * @access public
     * @param  string $_file
     * @return string
     */
    public function url(string $_file, bool $_login = false, int $_level = 0): string
    {
        if ($_login && !session('?user_auth_key')) {
            return 'javascript:alert(\'login\')';
        } elseif ($_level && !session('?user_auth_key') && session('user_level') != $_level) {
            return 'javascript:alert(\'level\')';
        } else {
            return Config::get('app.api_host') . '/download.do?file=' . Base64::encrypt($_file) . '&timestamp=' . time();
        }
    }

    /**
     * 文件下载
     * @access public
     * @param
     * @return void
     */
    public function file()
    {
        $this->fileName = Request::param('file', false);
        $this->timestamp = (int) Request::param('timestamp/f', 0);
        $this->timestamp = date('Ymd', $this->timestamp);

        if ($this->fileName && $this->timestamp == date('Ymd')) {
            $this->fileName = Base64::decrypt($this->fileName);
            if (preg_match('/^([\-_\\/A-Za-z0-9]+)(\.)([A-Za-z]{3,4})$/u', $this->fileName)) {
                $this->fileName = preg_replace('/([\/\\\]){2,}/si', DIRECTORY_SEPARATOR, $this->fileName);
                $this->fileName = app()->getRootPath() . 'public' . DIRECTORY_SEPARATOR .
                                    'uploads' . DIRECTORY_SEPARATOR . $this->fileName;
                $ext = explode(',', Env::get('app.upload_type', 'gif,jpg,png,zip,rar'));

                clearstatcache();
                if (is_file($this->fileName) && in_array(pathinfo($this->fileName, PATHINFO_EXTENSION), $ext)) {
                    return
                    Response::create($this->fileName, 'file')
                    ->name(md5($this->fileName . time()))
                    ->isContent(false)
                    ->expire(180);
                } else {
                    echo 'file not found';
                }
            } else {
                echo 'file not found';
            }
        } else {
            echo 'file not found';
        }

        $log  = '[API] 下载文件:' . Request::param('file', 'null');
        $log .= ' 本地地址:' . $this->fileName;
        $log .= PHP_EOL . 'PARAM:' . json_encode(Request::param('', '', 'trim'), JSON_UNESCAPED_UNICODE);
        Log::record($log, 'alert')->save();

        return false;
    }
}
