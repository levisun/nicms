<?php
/**
 *
 * @package   NiPHPCMS
 * @category  net\oauth\
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @version   CVS: $Id: Http.php v1.0.1 $
 * @link      http://www.NiPHP.com
 * @since     2017/01/03
 */
namespace net\oauth;

class Http
{
    private static function init()
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        return $ch;
    }

    private static function exec($ch)
    {
        $rsp = curl_exec($ch);
        if ($rsp !== false) {
            curl_close($ch);
            return $rsp;
        } else {
            $error = curl_errno($ch);
            curl_close($ch);
            throw new Exception("curl出错，错误码:$error");
        }
    }

    public static function request($url, $data, $method)
    {
        $method = strtolower($method);
        return self::$method($url, $data);
    }

    public static function get($url, $data)
    {
        $ch  = self::init();
        $url = rtrim($url, '?');
        $url .= '?' . http_build_query($data);
        curl_setopt($ch, CURLOPT_URL, $url);
        return self::exec($ch);
    }

    public static function post($url, $data)
    {
        $ch = self::init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        return self::exec($ch);
    }

    public static function postXml($url, $xml)
    {
        $ch = self::init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        return self::exec($ch);
    }
}