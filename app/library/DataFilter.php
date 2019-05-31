<?php
/**
 *
 * 数据安全过滤类
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

class DataFilter
{

    /**
     * 默认过滤
     * @access public
     * @param  mixed $_data
     * @return mixed
     */
    public static function defalut($_data)
    {
        if (is_array($_data)) {
            foreach ($_data as $key => $value) {
                $_data[$key] = self::defalut($value);
            }
            return $_data;
        } elseif (is_string($_data)) {
            $_data = self::safe($_data);
            $_data = self::enter($_data);
            $_data = self::fun($_data);
            $_data = strip_tags($_data);
            return $_data;
        }
    }

    /**
     * 内容过滤
     * @access public
     * @param  string $_str
     * @return string
     */
    public static function content(string $_str): string
    {
        $_str = self::safe($_str);
        $_str = self::enter($_str);
        $_str = self::fun($_str);
        $_str = htmlspecialchars($_str);
        return $_str;
    }

    /**
     * 字符过滤
     * @access public
     * @param  string $_str
     * @return string
     */
    public static function string(string $_str): string
    {
        $_str = self::safe($_str);
        $_str = self::enter($_str);
        $_str = self::fun($_str);
        return $_str;
    }

    /**
     * 过滤PHP危害函数方法
     * @access private
     * @param  string $_str
     * @return string
     */
    private static function fun(string $_str): string
    {
        $pattern = [
            '/(base64_decode)/si'        => 'ba&#115;e64_decode',
            '/(call_user_func_array)/si' => 'cal&#108;_user_func_array',
            '/(chown)/si'                => 'ch&#111;wn',
            '/(eval)/si'                 => 'ev&#97;l',
            '/(exec)/si'                 => 'ex&#101;c',
            '/(passthru)/si'             => 'pa&#115;sthru',
            '/(phpinfo)/si'              => 'ph&#112;info',
            '/(proc_open)/si'            => 'pr&#111;c_open',
            '/(popen)/si'                => 'po&#112;en',
            '/(shell_exec)/si'           => 'sh&#101;ll_exec',
            '/(system)/si'               => 'sy&#115;tem',

            // '/(\()/si'                   => '&#40;',
            // '/(\))/si'                   => '&#41;',
        ];
        return preg_replace(array_keys($pattern), array_values($pattern), $_str);
    }

    /**
     * 过滤回车
     * @access private
     * @param  string $_str
     * @return string
     */
    private static function enter(string $_str): string
    {
        $pattern = [
            '/>(\n|\r|\f)+/'       => '>',
            '/(\n|\r|\f)+</'       => '<',
            // '/(<!--)(.*?)(-->)/si' => '',
            // '/\/\*(.*?)\*\//si'    => '',

            // '/(\n|\r|\f)+\}/si' => '}',
            // '/\}(\n|\r|\f)+/si' => '}',
            // '/\{(\n|\r|\f)+/si' => '{',
            // '/;(\n|\r|\f)+/si'  => ';',
            // '/,(\n|\r|\f)+/si'  => ',',
            // '/\)(\n|\r|\f)+/si' => ')',
        ];
        return preg_replace(array_keys($pattern), array_values($pattern), $_str);
    }

    /**
     * 安全过滤
     * XSS跨站脚本攻击
     * XXE XML 实体扩展攻击
     * @access private
     * @param  string $_str
     * @return string
     */
    private static function safe(string $_str): string
    {
        return preg_replace([
            // XSS跨站脚本攻击
            '/on([a-zA-Z0-9]+)([ ]*?=[ ]*?)["|\'](.*?)["|\']/si',
            '/(javascript:)(.*?)(\))/si',
            '/<javascript.*?>(.*?)<\/javascript.*?>/si',
            '/<script.*?>(.*?)<\/script.*?>/si',
            '/<applet.*?>(.*?)<\/applet.*?>/si',
            '/<vbscript.*?>(.*?)<\/vbscript.*?>/si',
            '/<expression.*?>(.*?)<\/expression.*?>/si',

            // XXE XML 实体扩展攻击
            /* '/<html.*?>(.*?)<\/html.*?>/si',
            '/<head.*?>(.*?)<\/head.*?>/si',
            '/<title.*?>(.*?)<\/title.*?>/si',
            '/<body.*?>(.*?)<\/body.*?>/si', */
            '/<style.*?>(.*?)<\/style.*?>/si',
            '/<iframe.*?>(.*?)<\/iframe.*?>/si',
            '/<frame.*?>(.*?)<\/frame.*?>/si',
            '/<frameset.*?>(.*?)<\/frameset.*?>/si',
            '/<object.*?>(.*?)<\/object.*?>/si',
            '/<xml.*?>(.*?)<\/xml.*?>/si',
            '/<blink.*?>(.*?)<\/blink.*?>/si',
            '/<link.*?>(.*?)<\/link.*?>/si',
            '/<embed.*?>(.*?)<\/embed.*?>/si',
            '/<ilayer.*?>(.*?)<\/ilayer.*?>/si',
            '/<layer.*?>(.*?)<\/layer.*?>/si',
            '/<bgsound.*?>(.*?)<\/bgsound.*?>/si',
            '/<base.*?\/?>/si',
            '/<meta.*?\/?>/si',

            '/<\?php/si',
            '/<\?/si',
            '/( ){2,}/si',
        ], '', $_str);
    }
}
