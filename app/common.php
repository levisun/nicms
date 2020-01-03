<?php

/**
 *
 * 应用公共文件
 *
 * @package   NICMS
 * @category  app
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

use think\Response;
use think\facade\Request;
use think\facade\Route;
use think\facade\Session;
use app\common\library\Base64;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Signer\Key;
use Lcobucci\JWT\Signer\Hmac\Sha256;

if (!function_exists('format_size')) {
    /**
     * 格式化文件大小
     * @param  int $_file_size
     * @return string
     */
    function format_size(int $_file_size): string
    {
        if ($_file_size >= 1073741824) {
            $_file_size = round($_file_size / 1073741824 * 100) / 100 . ' GB';
        } elseif ($_file_size >= 1048576) {
            $_file_size = round($_file_size / 1048576 * 100) / 100 . ' MB';
        } elseif ($_file_size >= 1024) {
            $_file_size = round($_file_size / 1024 * 100) / 100 . ' KB';
        } else {
            $_file_size = $_file_size . ' bit';
        }

        return $_file_size;
    }
}

if (!function_exists('only_execute')) {
    /**
     * 非阻塞模式并发运行
     * @param string       $_lock     锁定文件
     * @param false|string $_time     锁定文件
     * @param callable     $_callback
     * @return void
     */
    function only_execute(string $_lock, $_time, callable $_callback): void
    {
        $path = app()->getRootPath() . 'runtime' . DIRECTORY_SEPARATOR . 'lock' . DIRECTORY_SEPARATOR;
        is_dir($path) or mkdir($path, 0755, true);

        clearstatcache();
        if (!is_file($path . $_lock) || false === $_time || filemtime($path . $_lock) <= strtotime($_time)) {
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
        return false !== strpos(Request::server('HTTP_USER_AGENT'), 'MicroMessenger') ? true : false;
    }
}

if (!function_exists('miss')) {
    /**
     * miss
     * @param  int $_code
     * @return string
     */
    function miss(int $_code, bool $_redirect = true): Response
    {
        $return_url = '';
        if (true === $_redirect) {
            $return_url = '<script type="text/javascript">setTimeout(function(){location.href = "//www.' . Request::rootDomain() . '";},30000);</script>';
        }

        $content = '<style type="text/css">*{padding:0; margin:0;}body{background:#fff;font-family:"Century Gothic","Microsoft yahei";color:#333;font-size:18px;}section{text-align:center;margin-top:50px;}h2,h3{font-weight:normal;margin-bottom:12px;margin-right:12px;display:inline-block;}</style><title>' . $_code . '</title><section><h2>o(╥﹏╥)o ' . $_code . '</h2></section>';

        $file = app()->getRootPath() . 'public' . DIRECTORY_SEPARATOR . 'theme' . DIRECTORY_SEPARATOR . $_code . '.html';
        if (is_file($file)) {
            $content = file_get_contents($file);
            $content = str_replace('{$return_url}', $return_url, $content);
        }

        return Response::create($content, 'html', $_code)
            ->header([
                'X-Powered-By'   => 'NICMS',
                'Content-Length' => strlen($content)
            ]);
    }
}

if (!function_exists('authorization_meta')) {
    /**
     * API授权字符串
     * @return string
     */
    function authorization_meta(): string
    {
        // 会话ID(SessionID)
        $jti  = Base64::encrypt(Session::getId(false));
        // 请求时间
        $time = Request::time();
        // 客户端token
        $uid  = Session::has('client_token') ? Session::get('client_token') : sha1(Request::ip());
        // 密钥
        $key = Request::ip() . Request::rootDomain() . Request::server('HTTP_USER_AGENT');
        $key = sha1(Base64::encrypt($key));

        $token = (new Builder)
            // Configures the issuer (iss claim)
            ->issuedBy(Request::rootDomain())
            // Configures the audience (aud claim)
            ->permittedFor(parse_url(Request::url(true), PHP_URL_HOST))
            // Configures the id (jti claim), replicating as a header item
            ->identifiedBy($jti, false)
            // Configures the time that the token was issue (iat claim)
            ->issuedAt($time)
            // Configures the time that the token can be used (nbf claim)
            ->canOnlyBeUsedAfter($time + 60)
            // Configures the expiration time of the token (exp claim)
            ->expiresAt($time + 28800)
            // Configures a new claim, called "uid"
            ->withClaim('uid', $uid)
            // Retrieves the generated token
            ->getToken(new Sha256, new Key($key));

        $token = 'Bearer ' . (string) $token;

        return '<meta name="csrf-authorization" content="' . $token . '" />';
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
        return (string) Route::buildUrl('/' . $_url, $_vars)->suffix(true)->domain(false);
    }
}
