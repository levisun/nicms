<?php
/**
 *
 * 加密类
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

use think\exception\HttpException;
use think\facade\Config;

class Base64
{

    /**
     * 密码加密
     * 不可逆
     * @access public
     * @static
     * @param  string $_str
     * @param  string $_salt
     * @param  string $_type
     * @return string
     */
    public static function password(string $_str, string $_salt = '', string $_type = 'md5'): string
    {
        if (function_exists($_type)) {
            // 第一次加密
            $_str = hash_hmac('sha1', trim($_str), $_salt);

            // 第二次加密
            $_str = hash_hmac('sha1', $_str . $_salt, $_str);

            // 第三次加密
            return call_user_func($_type, $_salt . $_str . $_type);
        } else {
            throw new HttpException(502, '参数错误');
        }
    }

    /**
     * 生成旗标
     * @param  string      $_str
     * @param  int|integer $_length
     * @return string
     */
    public static function flag($_str = '', int $_length = 7)
    {
        $_str = (string)$_str;
        $_str = $_str ? sha1($_str) : sha1(Config::get('app.secretkey'));
        $_length = $_length > 40 ? 40 : $_length;
        return substr(sha1($_str), 0, $_length);
    }

    /**
     * 数据加密
     * @access public
     * @static
     * @param  mixed  $_data      加密前的数据
     * @param  string $_secretkey 密钥
     * @return mixed              加密后的数据
     */
    public static function encrypt($_data, string $_secretkey = '')
    {
        if (is_array($_data)) {
            foreach ($_data as $key => $value) {
                $_data[self::encrypt($key, $_secretkey)] = self::encrypt($value, $_secretkey);
                unset($_data[$key]);
            }
            return $_data;
        } elseif (is_null($_data) || is_bool($_data)) {
            return $_data;
        } else {
            $_secretkey = $_secretkey ? $_secretkey : Config::get('app.secretkey');
            $_secretkey = hash_hmac('sha1', $_secretkey, md5($_secretkey));
            $iv = substr(md5($_secretkey), 0, openssl_cipher_iv_length('AES-256-CBC'));
            return base64_encode(openssl_encrypt((string)$_data, 'AES-256-CBC', $_secretkey, OPENSSL_RAW_DATA, $iv));
        }
    }

    /**
     * 数据解密
     * @access public
     * @static
     * @param  mixed  $_data      加密前的数据
     * @param  string $_secretkey 密钥
     * @return mixed              加密后的数据
     */
    public static function decrypt($_data, string $_secretkey = '')
    {
        if (is_array($_data)) {
            foreach ($_data as $key => $value) {
                $_data[self::decrypt($key, $_secretkey)] = self::decrypt($value, $_secretkey);
                unset($_data[$key]);
            }
            return $_data;
        } elseif (is_null($_data) || is_bool($_data)) {
            return $_data;
        } else {
            $_secretkey = $_secretkey ? $_secretkey : Config::get('app.secretkey');
            $_secretkey = hash_hmac('sha1', $_secretkey, md5($_secretkey));
            $iv = substr(md5($_secretkey), 0, openssl_cipher_iv_length('AES-256-CBC'));
            return openssl_decrypt(base64_decode($_data), 'AES-256-CBC', $_secretkey, OPENSSL_RAW_DATA, $iv);
        }
    }
}
