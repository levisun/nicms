<?php

/**
 *
 * 加密类
 *
 * @package   NICMS
 * @category  app\common\library
 * @author    失眠小枕头 [312630173@qq.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace app\common\library;

use think\facade\Config;
use think\facade\Cookie;
use think\facade\Request;
use think\facade\Session;

class Base64
{

    /**
     * 验证密码
     * @access public
     * @static
     * @param  string $_password
     * @param  string $_salt
     * @param  string $_hash
     * @return mixed
     */
    public static function verifyPassword(string $_password, string $_salt, string $_hash)
    {
        if (password_verify($_password . $_salt, $_hash)) {
            if (password_needs_rehash($_hash, PASSWORD_BCRYPT, ['cost' => 11])) {
                return self::createPassword($_password . $_salt);
            } else {
                return true;
            }
        } else {
            return false;
        }
    }

    /**
     * 创建密码
     * @access public
     * @static
     * @param  string $_password
     * @param  string $_salt
     * @return string
     */
    public static function createPassword(string $_password, string $_salt = ''): string
    {
        return password_hash($_password . $_salt, PASSWORD_BCRYPT, ['cost' => 11]);
    }

    /**
     * 生成旗标
     * @access public
     * @static
     * @param  string      $_str
     * @param  int|integer $_length
     * @return string
     */
    public static function flag($_str = '', int $_length = 7): string
    {
        $_str = trim((string) $_str);
        $_str = hash_hmac('sha256', $_str, Config::get('app.secretkey'));
        $_length = $_length > 40 ? 40 : $_length;
        return substr($_str, 0, $_length);
    }

    /**
     * 16进制转10进制
     * @access public
     * @static
     * @param  string $_hex
     * @return int
     */
    public static function hexdec(string $_hex): int
    {
        return (int) hexdec($_hex) - 1000;
    }

    /**
     * 10进制转16进制
     * @access public
     * @static
     * @param  int $_dec
     * @return string
     */
    public static function dechex(int $_dec): string
    {
        return dechex($_dec + 1000);
    }

    /**
     * url62加密
     * @access public
     * @static
     * @param  int    $_number 加密前的数据
     * @return string 加密后的数据
     */
    public static function url62encode(int $_number): string
    {
        $base62 = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $encode = '';
        $_number += 1000;
        while($_number > 0) {
            if ($mod = bcmod((string) $_number, '62')) {
                $encode .= $base62[$mod];
                $_number = bcdiv((string) bcsub((string) $_number, $mod), '62');
            }
        }

        return strrev($encode);
    }

    /**
     * url62解密
     * @access public
     * @static
     * @param  string $_encode 解密前的数据
     * @return int    解密后的数据
     */
    public static function url62decode(string $_encode): int
    {
        $base62 = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $len = strlen($_encode);
        $arr = array_flip(str_split($base62));

        $number = '';
        for($i = 0; $i < $len; $i ++) {
            $number = bcadd((string) $number, bcmul((string) $arr[$_encode[$i]], bcpow('62', (string) ($len - $i - 1) )));
        }

        return (int) $number - 1000;
    }

    /**
     * 数据加密
     * @access public
     * @static
     * @param  mixed  $_data 加密前的数据
     * @param  string $_salt
     * @return mixed  加密后的数据
     */
    public static function encrypt($_data, string $_salt = '')
    {
        if (is_string($_data)) {
            $_salt = $_salt ?: Request::server('HTTP_USER_AGENT');
            $secret_key = md5(__DIR__ . $_salt);
            $secret_key = hash_hmac('sha256', $secret_key, Config::get('app.secretkey', __DIR__));
            $iv = substr(sha1($secret_key), 0, openssl_cipher_iv_length('AES-256-CBC'));
            $_data = base64_encode(openssl_encrypt((string) $_data, 'AES-256-CBC', $secret_key, OPENSSL_RAW_DATA, $iv));
        } elseif (is_array($_data)) {
            foreach ($_data as $key => $value) {
                $_data[$key] = self::decrypt($value);
            }
        }

        return $_data;
    }

    /**
     * 数据解密
     * @access public
     * @static
     * @param  mixed  $_data 解密前的数据
     * @param  string $_salt
     * @return mixed  解密后的数据
     */
    public static function decrypt($_data, string $_salt = '')
    {
        if (is_string($_data)) {
            $_salt = $_salt ?: Request::server('HTTP_USER_AGENT');
            $secret_key = md5(__DIR__ . $_salt);
            $secret_key = hash_hmac('sha256', $secret_key, Config::get('app.secretkey', __DIR__));
            $iv = substr(sha1($secret_key), 0, openssl_cipher_iv_length('AES-256-CBC'));
            $_data = openssl_decrypt(base64_decode($_data), 'AES-256-CBC', $secret_key, OPENSSL_RAW_DATA, $iv);
        } elseif (is_array($_data)) {
            foreach ($_data as $key => $value) {
                $_data[$key] = self::decrypt($value);
            }
        }

        return $_data;
    }
}
