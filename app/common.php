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

use think\facade\Config;
use think\facade\Route;
use think\facade\Session;
use app\common\library\Base64;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Signer\Key;
use Lcobucci\JWT\Signer\Hmac\Sha256;


if (!function_exists('client_mac')) {
    /**
     * 客户端网卡物理MAC值
     * @return string
     */
    function client_mac()
    {
        $os = strtolower(PHP_OS);
        if (!in_array($os, ['unix', 'solaris', 'aix'])) {
            if ('linux' == $os) {
                @exec('ifconfig -a', $result);
            } else {
                @exec('ipconfig /all', $result);
                if (!$result) {
                    $ipconfig = DIRECTORY_SEPARATOR . 'system32' . DIRECTORY_SEPARATOR . 'ipconfig.exe';
                    if (is_file($_SERVER['WINDIR'] . $ipconfig)) {
                        @exec($_SERVER['WINDIR'] . $ipconfig . " /all", $result);
                    } else {
                        @exec($_SERVER['WINDIR'] . DIRECTORY_SEPARATOR . 'system' . DIRECTORY_SEPARATOR . 'ipconfig.exe /all', $result);
                    }
                }
            }

            foreach ($result as $value) {
                if (preg_match('/([0-9a-f]{2}[\:\-]{1}){5}[0-9a-f]{2}/i', $value, $mac)) {
                    return $mac[0];
                }
            }
        }
    }
}

if (!function_exists('remove_img')) {
    /**
     * 删除图片
     * @param  string $_img 图片路径
     * @return bool
     */
    function remove_img(string $_img): bool
    {
        // 网络图片直接返回
        if (false !== stripos($_img, 'http')) {
            return true;
        }

        $path = app()->getRootPath() . Config::get('filesystem.disks.public.visibility') . DIRECTORY_SEPARATOR;
        $_img = str_replace('/', DIRECTORY_SEPARATOR, ltrim($_img, '/'));
        $ext = '.' . pathinfo($_img, PATHINFO_EXTENSION);
        $_img = str_replace($ext, '_skl' . $ext, $_img);

        if (is_file($path . $_img)) {
            for ($i = 1; $i <= 8; $i++) {
                $size = $i * 100;
                $thumb = str_replace($ext, '_' . $size . $ext, $_img);
                if (is_file($path . $thumb)) {
                    @unlink($path . $thumb);
                }
            }
            @unlink($path . $_img);
        }

        return true;
    }
}

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

if (!function_exists('is_wechat')) {
    /**
     * 是否微信请求
     * @return boolean
     */
    function is_wechat(): bool
    {
        return false !== strpos(app('request')->server('HTTP_USER_AGENT'), 'MicroMessenger') ? true : false;
    }
}

if (!function_exists('create_authorization')) {
    /**
     * API授权字符串
     * @return string
     */
    function create_authorization(): string
    {
        $time = app('request')->time();
        $jti  = Base64::encrypt(Session::getId(false));
        $uid  = Session::has('client_token') ? Session::get('client_token') : md5(app('request')->ip());

        $key  = app('request')->ip();
        $key .= $key . app('request')->rootDomain();
        $key .= $key . app('request')->server('HTTP_USER_AGENT');
        $key = md5(Base64::encrypt($key));

        $token = (new Builder)
            ->issuedBy(app('request')->rootDomain())                // Configures the issuer (iss claim)
            ->permittedFor(parse_url(app('request')->url(true), PHP_URL_HOST))   // Configures the audience (aud claim)
            ->identifiedBy($jti, false)                             // Configures the id (jti claim), replicating as a header item
            ->issuedAt($time)                                       // Configures the time that the token was issue (iat claim)
            ->canOnlyBeUsedAfter($time + 60)                        // Configures the time that the token can be used (nbf claim)
            ->expiresAt($time + 28800)                              // Configures the expiration time of the token (exp claim)
            ->withClaim('uid', $uid)                                // Configures a new claim, called "uid"
            ->getToken(new Sha256, new Key($key));                  // Retrieves the generated token

        return 'Bearer ' . (string) $token;
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
