<?php

/**
 *
 * 应用公共文件
 *
 * @package   NICMS
 * @category  app
 * @author    失眠小枕头 [312630173@qq.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

use think\Response;
use think\exception\HttpResponseException;
use think\facade\Cookie;
use think\facade\Request;
use think\facade\Route;
use app\common\library\Filter;
use app\common\model\ApiApp as ModelApiApp;

if (!function_exists('to_unicode')) {
    /**
     * 汉字转unicode
     * @param  string $_str
     * @return string
     */
    function to_unicode(string $_str): string
    {
        return (string) preg_replace_callback('/./u', function (array $matches) {
            if (3 <= strlen($matches[0])) {
                $matches[0] = trim(json_encode($matches[0]), '"');
                $matches[0] = (string) preg_replace_callback('/\\\u([0-9a-f]{4})/si', function ($chs) {
                    return '\x{' . $chs[1] . '}';
                }, $matches[0]);
            }
            return $matches[0];
        }, $_str);
    }
}

if (!function_exists('format_date')) {
    /**
     * 格式化日期
     * @param  int    $_hits
     * @return string
     */
    function format_date(int $_timestamp): string
    {
        $_timestamp = time() - $_timestamp;
        $units = [
            ['秒前', 60],
            ['分钟前', 60],
            ['小时前', 24],
            ['天前', 30],
            ['月前', 12],
            ['年前', (int) date('Y')]
        ];
        $i = 0;
        while ($_timestamp >= $units[$i][1]) {
            $_timestamp /= $units[$i][1];
            if (isset($units[$i + 1])) {
                $i++;
            }
        }

        return round($_timestamp, 0) . $units[$i][0];
    }
}

if (!function_exists('format_hits')) {
    /**
     * 格式化浏览与评论量
     * @param  int    $_hits
     * @param  string $_delimiter 分隔符
     * @return string
     */
    function format_hits(int $_hits, string $_delimiter = ''): string
    {
        if ($_hits >= 10000) {
            $_hits /= 10000;
            $units = 'M+';
        } elseif ($_hits >= 1000) {
            $_hits /= 1000;
            $units = 'K+';
        } else {
            $units = '';
        }
        return round($_hits, 2) . $_delimiter . $units;
    }
}

if (!function_exists('format_size')) {
    /**
     * 格式化文件大小单位
     * @param  int    $_file_size
     * @param  string $_delimiter 分隔符
     * @return string
     */
    function format_size(int $_file_size, string $_delimiter = ''): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
        for ($i = 0; $_file_size >= 1024 && $i < 5; $i++) {
            $_file_size /= 1024;
        }
        return round($_file_size, 2) . $_delimiter . $units[$i];
    }
}

if (!function_exists('request_cache_key')) {
    /**
     * 请求缓存KEY
     * @param  string $_key
     * @return string
     */
    function request_cache_key(string $_key): string
    {
        $_key .= app('http')->getName() . app('lang')->getLangSet();

        if ('api' !== app('http')->getName()) {
            $_key .= request()->isMobile() ? 'mobile' : 'pc';
        }

        return md5($_key);
    }
}

if (!function_exists('only_execute')) {
    /**
     * 非阻塞模式并发运行
     * @param  string       $_lock     锁定文件
     * @param  false|string $_time     执行周期
     * @param  callable     $_callback
     * @return void
     */
    function only_execute(string $_lock, $_time, callable $_callback): void
    {
        $path = runtime_path('lock');
        is_dir($path) or mkdir($path, 0755, true);

        if (!is_file($path . $_lock)) {
            file_put_contents($path . $_lock, 'runtime:' . date('Y-m-d H:i:s'));
            $_time = false;
        }

        clearstatcache();
        if (false === $_time || filemtime($path . $_lock) <= strtotime($_time)) {
            if ($resource = @fopen($path . $_lock, 'w+')) {
                if (flock($resource, LOCK_EX | LOCK_NB)) {
                    fwrite($resource, 'runtime:' . date('Y-m-d H:i:s'));

                    call_user_func_array($_callback, [$resource, $path . $_lock]);

                    flock($resource, LOCK_UN);
                }
                fclose($resource);
            }
        }
    }
}

if (!function_exists('is_wechat')) {
    /**
     * 是否微信请求
     * @return boolean
     */
    function is_wechat(): bool
    {
        return false !== stripos(Request::server('HTTP_USER_AGENT'), 'MicroMessenger') ? true : false;
    }
}

if (!function_exists('app_secret')) {
    /**
     * APPID与密钥
     * @param  string $_app_name 应用名
     * @return array
     */
    function app_secret(string $_app_name = ''): array
    {
        $_app_name = $_app_name ?: app('http')->getName();
        $api_app = ModelApiApp::field('id, secret')
            ->where('name', '=', $_app_name)
            ->where('status', '=', 1)
            ->cache('app secret' . $_app_name)
            ->find();
        if ($api_app && $api_app = $api_app->toArray()) {
            $api_app['id'] += 1000000;
            return $api_app;
        } else {
            return [];
        }
    }
}

if (!function_exists('client_id')) {
    /**
     * 客户端唯一ID
     * 请勿在API或logic层中调用
     * @return string
     */
    function client_id(): string
    {
        if (!Cookie::has('CID') || !$token = Cookie::get('CID')) {
            $token  = Request::server('HTTP_USER_AGENT');
            $token .= sha1(__DIR__);
            $token .= bindec(Request::ip2bin(Request::ip()));
            $token .= date('YmdHis');
            $token .= Request::time(true);
            $token .= mt_rand(1000, 9999) . mt_rand(1000, 9999);
            $token .= microtime(true) . app()->getBeginTime();
            $token .= memory_get_usage() . app()->getBeginMem();

            $token = hash_hmac('sha256', $token, uniqid($token, true));
            $token = md5(uniqid($token, true));

            Cookie::set('CID', $token, ['domain' => Request::host(), 'httponly' => false]);
        }

        return $token;
    }
}

if (!function_exists('miss')) {
    /**
     * miss
     * @param  int  $_code
     * @param  bool $_redirect 重定向
     * @param  bool $_abort    抛出
     * @return Response
     */
    function miss($_code, bool $_redirect = false, bool $_abort = false)
    {
        if (500 > $_code) {
            // 请求参数
            $params = Request::param()
                ? Request::except(['password', 'sign', '__token__', 'timestamp', 'sign_type', 'appid'])
                : [];
            $params = array_filter($params);
            $params = !empty($params) ? json_encode($params, JSON_UNESCAPED_UNICODE) : '';

            trace('MISS ' . $_code . ' ' . Request::ip() . ' ' . Request::method(true) . ' ' . Request::url(true), 'warning');
            trace('MISS ' . $_code . ' ' . Request::ip() . ' ' . $params, 'warning');
        }

        $content = '<!DOCTYPE html><html lang="' . app('lang')->getLangSet() . '"><head><meta charset="UTF-8"><meta name="robots" content="none" /><meta name="renderer" content="webkit" /><meta name="force-rendering" content="webkit" /><meta name="viewport"content="width=device-width,initial-scale=1,maximum-scale=1,minimum-scale=1,user-scalable=no" /><meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" /><title>' . http_code($_code) . '</title><style type="text/css">*{padding:0;margin:0}body{background:#fff;font-family:"Century Gothic","Microsoft yahei";color:#333;font-size:18px}section{text-align:center;margin-top:50px}h2,h3{font-weight:normal;margin-bottom:12px;margin-right:12px;display:inline-block}</style></head><body><section><h2 class="miss">o(╥﹏╥)o ' . http_code($_code) . '</h2></section></body></html>';

        if (is_file(public_path() . intval($_code) . '.html')) {
            $content = file_get_contents(public_path() . intval($_code) . '.html');
        }

        if (true === $_redirect) {
            $return_url = '<script type="text/javascript">setTimeout(function(){location.href = "//' . Request::rootDomain() . '";},3000);</script>';
            $content = false !== strpos($content, '</body>')
                ? str_replace('</body>', $return_url . '</body>', $content)
                : $return_url;
        }

        // $content = Filter::symbol($content);
        $content = Filter::space($content);
        // $content = Filter::php($content);
        // $content = Filter::fun($content);

        $content = $content . '<!-- ' . date('Y-m-d H:i:s') . ' -->';

        $resource = Response::create($content, 'html', 200)
            ->allowCache(true)
            ->cacheControl('max-age=1440,must-revalidate')
            ->lastModified(gmdate('D, d M Y H:i:s') . ' GMT')
            ->expires(gmdate('D, d M Y H:i:s', time() + 1440) . ' GMT');

        ob_start('ob_gzhandler');

        if ($_abort === true) {
            throw new HttpResponseException($resource);
        }

        return $resource;
    }
}
if (!function_exists('http_code')) {
    /**
     * http code
     * @param  int $_code
     * @return string
     */
    function http_code($_code): string
    {
        $httpCodeMsg = [
            100 => 'Continue', 101 => 'Switching Protocol', 103 => 'Early Hints',
            200 => 'OK', 201 => 'Created', 202 => 'Accepted', 203 => 'Non-Authoritative Information', 204 => 'No Content', 205 => 'Reset Content', 206 => 'Partial Content',
            300 => 'Multiple Choice', 300 => 'Accepted', 301 => 'Moved Permanently', 302 => 'Found', 303 => 'See Other', 304 => 'Not Modified', 307 => 'Temporary Redirect', 308 => 'Permanent Redirect',
            400 => 'Bad Request', 401 => 'Unauthorized', 403 => 'Forbidden', 404 => 'Not Found', 405 => 'Method Not Allowed', 406 => 'Not Acceptable', 407 => 'Proxy Authentication Required', 408 => 'Request Timeout', 409 => 'Conflict', 410 => 'Gone', 411 => 'Length Required', 412 => 'Precondition Failed', 413 => 'Payload Too Large', 414 => 'URI Too Long', 415 => 'Unsupported Media Type', 416 => 'Range Not Satisfiable', 417 => 'Expectation Failed', 418 => 'I&#039;m a teapot', 426 => 'Upgrade Required', 428 => 'Precondition Required', 429 => 'Too Many Requests', 431 => 'Request Header Fields Too Large', 451 => 'Unavailable For Legal Reasons',
            500 => 'Internal Server Error', 501 => 'Not Implemented', 502 => 'Bad Gateway', 503 => 'Service Unavailable', 504 => 'Gateway Timeout', 505 => 'HTTP Version Not Supported', 506 => 'Variant Also Negotiates', 510 => 'Not Extended', 511 => 'Network Authentication Required',
        ];

        return isset($httpCodeMsg[$_code]) ? $_code . ' ' . $httpCodeMsg[$_code] : (string) $_code;
    }
}

if (!function_exists('url')) {
    /**
     * Url生成
     * @param  string $_url  路由地址
     * @param  array  $_vars 变量
     * @return string
     */
    function url(string $_url = '', array $_vars = []): string
    {
        $_url = $_url ? '/' . trim($_url, '\/.') : '';
        return (string) Route::buildUrl($_url, $_vars)->suffix(true)->domain(false);
    }
}

if (!function_exists('public_path')) {
    /**
     * 获取web根目录
     *
     * @param  string $_path
     * @return string
     */
    function public_path(string $_path = ''): string
    {
        $_path = trim($_path, '\/');
        $_path = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $_path);
        $_path = $_path ? $_path . DIRECTORY_SEPARATOR : '';
        return app()->getRootPath() . 'public' . DIRECTORY_SEPARATOR . $_path;
    }
}

if (!function_exists('runtime_path')) {
    /**
     * 获取应用运行时目录
     *
     * @param  string $_path
     * @return string
     */
    function runtime_path(string $_path = ''): string
    {
        $_path = trim($_path, '\/');
        $_path = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $_path);
        $_path = $_path ? $_path . DIRECTORY_SEPARATOR : '';
        return app()->getRootPath() . 'runtime' . DIRECTORY_SEPARATOR . $_path;
    }
}

if (!function_exists('root_path')) {
    /**
     * 获取项目根目录
     *
     * @param  string $_path
     * @return string
     */
    function root_path(string $_path = ''): string
    {
        $_path = trim($_path, '\/');
        $_path = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $_path);
        $_path = $_path ? $_path . DIRECTORY_SEPARATOR : '';
        return app()->getRootPath() . $_path;
    }
}
