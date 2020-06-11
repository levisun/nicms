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
use think\facade\Cookie;
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

if (!function_exists('word')) {
    /**
     * 分词
     * @param  string $_text
     * @param  int    $_length 返回词语数量
     * @return array
     */
    function word(string $_text, string $_sort = '', int $_length = 0): array
    {
        $words = [];

        // 过滤其他字符
        if ($_text = DataFilter::chs_alpha($_text)) {
            @ini_set('memory_limit', '128M');
            // 词库
            define('_VIC_WORD_DICT_PATH_', root_path('vendor/lizhichao/word/Data') . 'dict.json');
            $fc = new VicWord('json');
            $words = $fc->getAutoWord($_text);
            unset($fc);
            foreach ($words as $key => $value) {
                if ($value[0] = trim($value[0])) {
                    $words[$key] = [
                        'length' => mb_strlen($value[0], 'utf-8'),
                        'word'   => $value[0],
                    ];
                } else {
                    unset($words[$key]);
                }
            }

            // 排序
            if ($_sort) {
                $_sort = strtoupper($_sort) === 'ASC' ? SORT_ASC : SORT_DESC;
                $words = array_unique($words, SORT_REGULAR);    // 过滤重复数据
                array_multisort(array_column($words, 'length'), $_sort, $words);
            }

            // 如果设定长度,返回对应长度数组
            if ($_length) {
                $words = array_slice($words, 0, $_length);
            }
        }

        return $words;
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
        $path = runtime_path('lock');
        is_dir($path) or mkdir($path, 0755, true);

        is_file($path . $_lock) or file_put_contents($path . $_lock, '');

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

if (!function_exists('app_secret')) {
    /**
     * APP密钥
     * @return string
     */
    function app_secret(int $_app_id): void
    {
        $app_secret = '';
        if ($_app_id > 1000000) {
            $_app_id -= 1000000;
            $app_secret = ModelApiApp::where([
                ['id', '=', $_app_id]
            ])->cache('app secret' . $_app_id)->value('secret', '');
        }
        $key = date('Ymd') . Request::ip() . Request::rootDomain() . Request::server('HTTP_USER_AGENT');
        $app_secret = sha1($app_secret . $key);

        Cookie::has('client_token') or Cookie::set('client_token', $app_secret, ['httponly' => false]);
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
        // 密钥
        $key = date('Ymd') . Request::ip() . Request::rootDomain() . Request::server('HTTP_USER_AGENT');
        $key = sha1(Base64::encrypt($key));

        $token = (new Builder)
            // 签发者
            ->issuedBy(Request::rootDomain())
            // 接收者
            ->permittedFor(parse_url(Request::url(true), PHP_URL_HOST))
            // 身份标识(SessionID)
            ->identifiedBy(Base64::encrypt(Session::getId(false)), false)
            // 签发时间
            ->issuedAt(Request::time())
            // Configures the time that the token can be used (nbf claim)
            ->canOnlyBeUsedAfter(Request::time() + 60)
            // 签发过期时间
            ->expiresAt(Request::time() + 28800)
            // 客户端ID
            ->withClaim('uid', Base64::client_id())
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
        $content = '';
        $file = public_path() . $_code . '.html';
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

if (!function_exists('public_path')) {
    /**
     * 获取web根目录
     *
     * @param string $path
     * @return string
     */
    function public_path($_path = '')
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
     * @param string $path
     * @return string
     */
    function runtime_path($_path = '')
    {
        $_path = trim($_path, '\/');
        $_path = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $_path);
        $_path = $_path ? $_path . DIRECTORY_SEPARATOR : '';
        return app()->getRootPath() . 'runtime' . DIRECTORY_SEPARATOR . $_path;
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
