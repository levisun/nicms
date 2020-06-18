<?php

/**
 *
 * 加密类
 *
 * @package   NICMS
 * @category  app\common\library
 * @author    失眠小枕头 [levisun.mail@gmail.com]
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
     * 客户端唯一ID
     * 请勿在API或logic层中调用
     * @access public
     * @static
     * @return string
     */
    public static function client_id(): string
    {
        if (!Cookie::has('client_id') || !$token = Cookie::get('client_id')) {
            $token  = Request::server('HTTP_USER_AGENT');
            $token .= sha1(__DIR__);
            $token .= bindec(Request::ip2bin(Request::ip()));
            $token .= date('YmdHis');
            $token .= Request::time(true);
            $token .= number_format(microtime(true) - app()->getBeginTime(), 3);
            $token .= number_format((memory_get_usage() - app()->getBeginMem()) / 1048576, 3);

            $token = hash_hmac('sha256', $token, uniqid($token, true));
            $token = sha1(uniqid($token, true));

            Cookie::set('client_id', $token, ['httponly' => false]);
        }

        return $token;
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
     * @param  mixed  $_data 加密前的数据
     * @param  string $_salt
     * @return mixed  加密后的数据
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
