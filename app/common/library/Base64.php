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
     * @param  string $_str
     * @param  string $_salt
     * @return string
     */
    public static function createPassword(string $_str, string $_salt = ''): string
    {
        return password_hash($_str . $_salt, PASSWORD_BCRYPT, ['cost' => 11]);
    }

    /**
     * 客户端唯一ID
     * @param
     * @return string
     */
    public static function client_id(): string
    {
        $client_id  = date('dYm') . '.';
        $client_id .= bindec(app('request')->ip2bin(app('request')->ip())) . '.';
        $client_id .= date('sHi');
        $client_id .= number_format(microtime(true) - app()->getBeginTime(), 3) . '.';
        $client_id .= app('request')->time(true);
        $client_id .= number_format((memory_get_usage() - app()->getBeginMem()) / 1024 / 1024, 3);

        // return $client_id;

        return md5(uniqid($client_id, true));
    }

    /**
     * 生成旗标
     * @param  string      $_str
     * @param  int|integer $_length
     * @return string
     */
    public static function flag($_str = '', int $_length = 7): string
    {
        $_str = (string) $_str;
        $_str = trim($_str);
        $_str = hash_hmac('sha1', $_str, app('config')->get('app.secretkey'));
        $_length = $_length > 40 ? 40 : $_length;
        return substr($_str, 0, $_length);
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
            $secretkey = md5(__DIR__ . app('request')->header('user_agent') . $_salt);
            $secretkey = hash_hmac('sha256', $secretkey, app('config')->get('app.secretkey', __DIR__));
            $iv = substr(sha1($secretkey), 0, openssl_cipher_iv_length('AES-256-CBC'));
            $_data = base64_encode(openssl_encrypt((string) $_data, 'AES-256-CBC', $secretkey, OPENSSL_RAW_DATA, $iv));
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
            $secretkey = md5(__DIR__ . app('request')->header('user_agent') . $_salt);
            $secretkey = hash_hmac('sha256', $secretkey, app('config')->get('app.secretkey', __DIR__));
            $iv = substr(sha1($secretkey), 0, openssl_cipher_iv_length('AES-256-CBC'));
            $_data = openssl_decrypt(base64_decode($_data), 'AES-256-CBC', $secretkey, OPENSSL_RAW_DATA, $iv);
        } elseif (is_array($_data)) {
            foreach ($_data as $key => $value) {
                $_data[$key] = self::decrypt($value);
            }
        }

        return $_data;
    }
}
