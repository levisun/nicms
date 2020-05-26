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
use think\exception\HttpResponseException;
use think\facade\Request;
use think\facade\Route;
use think\facade\Session;
use app\common\library\Base64;
use app\common\library\DataFilter;
use app\common\model\ApiApp as ModelApiApp;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Signer\Key;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lizhichao\Word\VicWord;

if (!function_exists('format_hits')) {
    /**
     * 格式化浏览与评论量
     * @param  int    $_hits
     * @param  string $_delimiter 分隔符
     * @return string
     */
    function format_hits(int $_hits, string $_delimiter = ''): string
    {
        $units = ['', '', '', 'K+', 'M+', 'B+'];
        for ($i = 0; $_hits >= 10 && $i < 6; $i++) {
            $_hits /= 10;
        }
        return round($_hits, 2) . $_delimiter . $units[$i];
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

if (!function_exists('word')) {
    /**
     * 分词
     * @param  string $_str
     * @param  int    $_length 返回词语数量
     * @return array
     */
    function word(string $_str, int $_length): array
    {
        if ($_str = DataFilter::chs_alpha($_str)) {
            @ini_set('memory_limit', '128M');
            $path = app()->getRootPath() . 'vendor/lizhichao/word/Data/dict.json';
            define('_VIC_WORD_DICT_PATH_', $path);
            $fc = new VicWord('json');
            $_str = $fc->getAutoWord($_str);
            unset($fc);

            // 取出有效词
            foreach ($_str as $key => $value) {
                $value[0] = trim($value[0]);

                if (1 < mb_strlen($value[0], 'UTF-8')) {
                    $_str[$key] = trim($value[0]);
                } elseif (intval($value[0])) {
                    unset($_str[$key]);
                } else {
                    unset($_str[$key]);
                }
            }
            // 过滤重复词
            $_str = array_unique($_str);

            // 如果设定长度,返回对应长度数组
            return $_length ? array_slice($_str, 0, $_length) : $_str;
        } else {
            return [];
        }
    }
}

if (!function_exists('only_execute')) {
    /**
     * 非阻塞模式并发运行
     * @param string       $_lock     锁定文件
     * @param false|string $_time     执行周期
     * @param callable     $_callback
     * @return void
     */
    function only_execute(string $_lock, $_time, callable $_callback): void
    {
        $path = app()->getRootPath() . 'runtime' . DIRECTORY_SEPARATOR . 'lock' . DIRECTORY_SEPARATOR;
        is_dir($path) or mkdir($path, 0755, true);

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
        return false !== strpos(Request::server('HTTP_USER_AGENT'), 'MicroMessenger') ? true : false;
    }
}

if (!function_exists('app_secret_meta')) {
    /**
     * APP密钥
     * @return string
     */
    function app_secret_meta(int $_app_id): string
    {
        return '<meta name="csrf-appsecret" content="' . app_secret($_app_id) . '" />';
    }
}

if (!function_exists('app_secret')) {
    /**
     * APP密钥
     * @return string
     */
    function app_secret(int $_app_id): string
    {
        if ($_app_id > 1000000) {
            $_app_id -= 1000000;
            return ModelApiApp::where([
                ['id', '=', $_app_id]
            ])
                ->cache('APPID' . $_app_id)
                ->value('secret', '');
        }
        return '';
    }
}

if (!function_exists('authorization_meta')) {
    /**
     * API授权字符串
     * @return string
     */
    function authorization_meta(): string
    {
        return '<meta name="csrf-authorization" content="' . authorization() . '" />';
    }
}

if (!function_exists('authorization')) {
    /**
     * API授权字符串
     * @return string
     */
    function authorization(): string
    {
        // 会话ID(SessionID)
        $jti  = Base64::encrypt(Session::getId(false));
        // 请求时间
        $time = Request::time();
        // 客户端token
        $uid  = Session::has('client_id') ? Session::get('client_id') : sha1(Request::ip());
        // 密钥
        $key = Request::ip() . Request::rootDomain() . Request::server('HTTP_USER_AGENT');
        $key = sha1(Base64::encrypt($key));

        $token = (new Builder)
            // 签发者
            ->issuedBy(Request::rootDomain())
            // 接收者
            ->permittedFor(parse_url(Request::url(true), PHP_URL_HOST))
            // 身份标识(SessionID)
            ->identifiedBy($jti, false)
            // 签发时间
            ->issuedAt($time)
            // Configures the time that the token can be used (nbf claim)
            ->canOnlyBeUsedAfter($time + 60)
            // 签发过期时间
            ->expiresAt($time + 28800)
            // 客户端ID
            ->withClaim('uid', $uid)
            // 生成token
            ->getToken(new Sha256, new Key($key));

        return 'Bearer ' . (string) $token;
    }
}

if (!function_exists('miss')) {
    /**
     * miss
     * @param  int  $_code
     * @param  bool $_redirect
     * @return Response
     */
    function miss(int $_code, bool $_redirect = true, bool $_abort = false): Response
    {
        $content = '<!-- miss -->';
        $file = app()->getRootPath() . 'public' . DIRECTORY_SEPARATOR . $_code . '.html';
        if (is_file($file)) {
            $content .= file_get_contents($file);
        } else {
            $content .= '<!DOCTYPE html><html lang="zh-cn"><head><meta charset="UTF-8"><meta name="robots" content="none" /><meta name="renderer" content="webkit" /><meta name="force-rendering" content="webkit" /><meta name="viewport"content="width=device-width,initial-scale=1,maximum-scale=1,minimum-scale=1,user-scalable=no" /><meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" /><title>' . $_code . '</title><style type="text/css">*{padding:0;margin:0}body{background:#fff;font-family:"Century Gothic","Microsoft yahei";color:#333;font-size:18px}section{text-align:center;margin-top:50px}h2,h3{font-weight:normal;margin-bottom:12px;margin-right:12px;display:inline-block}</style></head><body><section><h2>o(╥﹏╥)o ' . $_code . '</h2></section>{$return_url}</body></html>';
        }

        $return_url = (true === $_redirect)
            ? '<script type="text/javascript">setTimeout(function(){location.href = "//www.' . Request::rootDomain() . '";},3000);</script>'
            : '';

        $content = str_replace('{$return_url}', $return_url, $content);
        $response = Response::create($content, 'html', $_code)
            ->header([
                'Cache-Control'  => 'max-age=1440,must-revalidate',
                'Last-Modified'  => gmdate('D, d M Y H:i:s') . ' GMT',
                'Expires'        => gmdate('D, d M Y H:i:s', time() + 1440) . ' GMT',
                'X-Powered-By'   => 'NICMS',
                'Content-Length' => strlen($content)
            ]);

        if ($_abort === true) {
            throw new HttpResponseException($response);
        }

        return $response;
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
        $_url = $_url ? '/' . $_url : '';
        return (string) Route::buildUrl($_url, $_vars)->suffix(true)->domain(false);
    }
}
