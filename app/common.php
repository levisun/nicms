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
use think\facade\Config;
use think\facade\Cookie;
use think\facade\Request;
use think\facade\Route;
use think\facade\Session;
use app\common\library\Base64;
use app\common\library\Filter;
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

if (!function_exists('filepath_decode')) {
    /**
     * 获得文件地址(解密)
     * @param  string $_file
     * @param  bool   $_abs
     * @return string
     */
    function filepath_decode(string $_file, bool $_abs = false): string
    {
        $salt = date('Y') . Request::ip() . Request::rootDomain() . Request::server('HTTP_USER_AGENT');
        $salt = md5($salt);

        $_file = $_file ? Base64::decrypt($_file, $salt) : '';

        if ($_file && false !== preg_match('/^[a-zA-Z0-9_\/\\\]+\.[a-zA-Z0-9]{2,4}$/u', $_file)) {
            $_file = Filter::safe($_file);
            $_file = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $_file);

            $path = Config::get('filesystem.disks.public.root') . DIRECTORY_SEPARATOR;
            $path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);

            if (is_file($path . $_file)) {
                return $_abs ? $path . $_file : $_file;
            }
        }
        return '';
    }
}

if (!function_exists('filepath_encode')) {
    /**
     * 获得文件地址(加密)
     * @param  string $_file
     * @param  bool   $_abs
     * @return string
     */
    function filepath_encode(string $_file, bool $_abs = false): string
    {
        $_file = Filter::safe($_file);
        $_file = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $_file);

        $path = Config::get('filesystem.disks.public.root') . DIRECTORY_SEPARATOR;
        $path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);

        $salt = date('Y') . Request::ip() . Request::rootDomain() . Request::server('HTTP_USER_AGENT');
        $salt = md5($salt);

        if (is_file($path . $_file)) {
            return $_abs ? Base64::encrypt($path . $_file, $salt) : Base64::encrypt($_file, $salt);
        }

        return '';
    }
}

if (!function_exists('words')) {
    /**
     * 分词
     * @param  string $_text
     * @param  int    $_length 返回词语数量
     * @return array
     */
    function words(string $_text, int $_length = 0, string $_sort = ''): array
    {
        $words = [];

        // 过滤其他字符
        if ($_text = Filter::chs_alpha($_text)) {
            // 提取日期词语
            $date = [];
            $_text = (string) preg_replace_callback('/([\d]+[\x{4e00}-\x{9fa5}]{1})/u', function ($matches) use (&$date) {
                $matches = array_map('trim', $matches);
                if (false !== mb_strpos($matches[1], '年', 0, 'utf-8')) {
                    $date[] = $matches[1];
                } elseif (false !== mb_strpos($matches[1], '月', 0, 'utf-8')) {
                    $date[] = $matches[1];
                } elseif (false !== mb_strpos($matches[1], '日', 0, 'utf-8')) {
                    $date[] = $matches[1];
                } elseif (false !== mb_strpos($matches[1], '时', 0, 'utf-8')) {
                    $date[] = $matches[1];
                } elseif (false !== mb_strpos($matches[1], '分', 0, 'utf-8')) {
                    $date[] = $matches[1];
                } elseif (false !== mb_strpos($matches[1], '秒', 0, 'utf-8')) {
                    $date[] = $matches[1];
                } else {
                    return mb_substr($matches[1], mb_strlen($matches['1'], 'utf-8') - 1);
                }
                return '';
            }, $_text);



            @ini_set('memory_limit', '128M');
            $fc = new VicWord();
            $words = $fc->getAutoWord($_text);
            unset($fc);

            $length = [];
            foreach ($words as $key => $value) {
                $value[0] = trim($value[0]);
                $length[] = mb_strlen($value[0], 'utf-8');
                $words[$key] = $value[0];
            }
            $words = array_merge($words, $date);

            // 排序
            if ($_sort) {
                $_sort = strtoupper($_sort) === 'ASC' ? SORT_ASC : SORT_DESC;
                array_multisort($length, $_sort, $words);
            }

            unset($length);

            // 过滤重复数据或空数据
            $words = array_unique($words);
            $words = array_filter($words);

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
     * APP密钥
     * @return string
     */
    function app_secret(): string
    {
        $app_name = app('http')->getName();
        $api_app = ModelApiApp::field('id, secret')
            ->where([
                ['name', '=', $app_name],
                ['status', '=', 1]
            ])
            ->cache('app secret' . $app_name)
            ->find();
        if ($api_app && $api_app = $api_app->toArray()) {
            $key = date('Ymd') . Request::ip() . Request::rootDomain() . Request::server('HTTP_USER_AGENT');
            $app_secret = sha1($api_app['secret'] . $key);
            Cookie::set('XSRF_TOKEN', $app_secret, ['httponly' => false]);

            return '<meta name="csrf-appid" content="' . ($api_app['id'] + 1000000) . '" />';
        }

        return '';
    }
}

if (!function_exists('authorization')) {
    /**
     * API授权字符串
     * @return string
     */
    function authorization(): void
    {
        // 密钥
        $key = date('Ymd') . Request::ip() . Request::rootDomain() . Request::server('HTTP_USER_AGENT');
        $key = sha1(Base64::encrypt($key));

        $authorization = (new Builder)
            // 签发者
            ->issuedBy(Request::rootDomain())
            // 接收者
            ->permittedFor(Request::host())
            // 身份标识(SessionID)
            ->identifiedBy(Base64::encrypt(Session::getId(false)), false)
            // 签发时间
            ->issuedAt(Request::time())
            // 令牌使用时间
            ->canOnlyBeUsedAfter(Request::time() + 2880)
            // 签发过期时间
            ->expiresAt(Request::time() + 28800)
            // 客户端ID
            ->withClaim('uid', client_id())
            // 生成token
            ->getToken(new Sha256, new Key($key));

        $authorization = (string) $authorization;

        Cookie::set('XSRF_AUTHORIZATION', $authorization, ['httponly' => false]);
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
            $token .= number_format(microtime(true) - app()->getBeginTime(), 3);
            $token .= number_format((memory_get_usage() - app()->getBeginMem()) / 1048576, 3);

            $token = hash_hmac('sha256', $token, uniqid($token, true));
            $token = sha1(uniqid($token, true));

            Cookie::set('CID', $token, ['httponly' => false]);
        }

        return $token;
    }
}

if (!function_exists('miss')) {
    /**
     * miss
     * @param  int  $_code
     * @param  bool $_redirect
     * @param  bool $_abort
     * @return Response
     */
    function miss(int $_code, bool $_redirect = true, bool $_abort = false)
    {
        $file = public_path('static') . $_code . '.html';
        $content = is_file($file)
            ? file_get_contents($file)
            : '<!DOCTYPE html><html lang="zh-cn"><head><meta charset="UTF-8"><meta name="robots" content="none" /><meta name="renderer" content="webkit" /><meta name="force-rendering" content="webkit" /><meta name="viewport"content="width=device-width,initial-scale=1,maximum-scale=1,minimum-scale=1,user-scalable=no" /><meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" /><title>' . $_code . '</title><style type="text/css">*{padding:0;margin:0}body{background:#fff;font-family:"Century Gothic","Microsoft yahei";color:#333;font-size:18px}section{text-align:center;margin-top:50px}h2,h3{font-weight:normal;margin-bottom:12px;margin-right:12px;display:inline-block}</style></head><body><section><h2 class="miss">o(╥﹏╥)o ' . $_code . '</h2></section></body></html>';

        $return_url = '<script type="text/javascript">setTimeout(function(){location.href = "//' . Request::rootDomain() . '";},3000);</script>';
        $content = true === $_redirect ? str_replace('</body>', $return_url . '</body>', $content) : $content;

        if ($_abort === true) {
            throw new HttpResponseException(Response::create($content, 'html', $_code));
        }

        return $content;
    }
}

if (!function_exists('public_path')) {
    /**
     * 获取web根目录
     *
     * @param string $path
     * @return string
     */
    function public_path($_path = ''): string
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
    function runtime_path($_path = ''): string
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
     * @param string $path
     * @return string
     */
    function root_path($_path = ''): string
    {
        $_path = trim($_path, '\/');
        $_path = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $_path);
        $_path = $_path ? $_path . DIRECTORY_SEPARATOR : '';
        return app()->getRootPath() . $_path;
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
