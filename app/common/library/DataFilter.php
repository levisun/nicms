<?php

/**
 *
 * 数据安全过滤类
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

use app\common\library\Emoji;

class DataFilter
{

    /**
     * 默认过滤
     * @access public
     * @static
     * @param  string|array $_data
     * @return string|array
     */
    public static function filter($_data)
    {
        if (is_string($_data)) {
            $_data = trim($_data, " \/,._-\t\n\r\0\x0B");
            $_data = (new Emoji)->clear($_data);
            $_data = self::safe($_data);
            $_data = self::fun($_data);
            $_data = self::enter($_data);
            $_data = strip_tags($_data);
            $_data = htmlspecialchars($_data, ENT_QUOTES);
        } elseif (is_array($_data)) {
            foreach ($_data as $key => $value) {
                $_data[$key] = self::filter($value);
            }
        }
        return $_data;
    }

    /**
     * 内容过滤
     * @access public
     * @static
     * @param  string|array $_data
     * @return string|array
     */
    public static function content($_data)
    {
        if (is_string($_data)) {
            $_data = trim($_data, " \/,._-\t\n\r\0\x0B");
            $_data = (new Emoji)->encode($_data);
            $_data = self::safe($_data);
            $_data = self::fun($_data);
            // 过滤标签上的信息
            $_data = preg_replace_callback('/([a-zA-Z0-9-_]+)=("|\')(.*?)("|\')/si', function ($matches) {
                if (in_array($matches[1], ['href', 'src', 'atr', 'title'])) {
                    return $matches[0];
                } else {
                    return;
                }
            }, $_data);
            // 过滤非法标签
            $_data = preg_replace_callback('/<([a-zA-Z0-9\/]+)(.*?)>/si', function ($matches) {
                $matches[1] = trim($matches[1]);
                $element = [
                    'a', '/a', 'audio', '/audio', 'b', '/b', 'br', 'br/', 'center', '/center', 'dd', '/dd', 'del', '/del', 'div', '/div', 'dl', '/dl', 'dt', '/dt', 'em', '/em', 'h1', '/h1', 'h2', '/h2', 'h3', '/h3', 'h4', '/h4', 'h5', '/h5', 'h6', '/h6', 'i', '/i', 'img', 'li', '/li', 'ol', '/ol', 'p', '/p', 'pre', '/pre', 'small', '/small', 'strong', '/strong', 'table', '/table', 'tbody', '/tbody', 'td', '/td', 'th', '/th', 'thead', '/thead', 'tr', '/tr', 'ul', '/ul', 'video', '/video',
                ];
                if (in_array($matches[1], $element)) {
                    return $matches[0];
                } else {
                    return;
                }
            }, $_data);

            $_data = self::enter($_data);
            $_data = htmlspecialchars($_data, ENT_QUOTES);
        } elseif (is_array($_data)) {
            foreach ($_data as $key => $value) {
                $_data[$key] = self::content($value);
            }
        }
        return $_data;
    }

    /**
     * 还原内容
     * @access public
     * @static
     * @param  string|array $_data
     * @return string|array
     */
    public static function deContent($_data)
    {
        if (is_string($_data)) {
            $_data = trim($_data, " \/,._-\t\n\r\0\x0B");
            $_data = htmlspecialchars_decode($_data, ENT_QUOTES);
            $_data = (new Emoji)->decode($_data);
            $_data = self::safe($_data);
            // $_data = self::fun($_data);
        } elseif (is_array($_data)) {
            foreach ($_data as $key => $value) {
                $_data[$key] = self::deContent($value);
            }
        }
        return $_data;
    }

    /**
     * 字符过滤
     * @access public
     * @static
     * @param  string|array $_data
     * @return string|array
     */
    public static function string($_data)
    {
        if (is_string($_data)) {
            $_data = trim($_data, " \/,._-\t\n\r\0\x0B");
            $_data = (new Emoji)->clear($_data);
            $_data = self::safe($_data);
            // $_data = self::fun($_data);
        } elseif (is_array($_data)) {
            foreach ($_data as $key => $value) {
                $_data[$key] = self::string($value);
            }
        }
        return $_data;
    }

    /**
     * 过滤回车
     * @access private
     * @static
     * @param  string $_str
     * @return string
     */
    private static function enter(string &$_str): string
    {
        $pattern = [
            '~>\s+<~'       => '><',
            '~>(\s+\n|\r)~' => '>',
            '/( ){2,}/si'   => ' ',
            '/( )+</si'     => '<',
            '/( )+>/si'     => '>',

            // '/(<!--)(.*?)(-->)/si' => '',
            // '/\/\*(.*?)\*\//si'    => '',
        ];
        return (string) preg_replace(array_keys($pattern), array_values($pattern), $_str);
    }

    /**
     * 过滤PHP危害函数方法
     * @access private
     * @static
     * @param  string $_str
     * @return string
     */
    private static function fun(string &$_str): string
    {
        $pattern = [
            '/(base64_decode)/si'        => 'ba&#115;e64_decode',
            '/(call_user_func_array)/si' => 'cal&#108;_user_func_array',
            '/(call_user_func)/si'       => 'cal&#108;_user_func',
            '/(chown)/si'                => 'ch&#111;wn',
            '/(eval)/si'                 => 'ev&#97;l',
            '/(exec)/si'                 => 'ex&#101;c',
            '/(passthru)/si'             => 'pa&#115;sthru',
            '/(phpinfo)/si'              => 'ph&#112;info',
            '/(proc_open)/si'            => 'pr&#111;c_open',
            '/(popen)/si'                => 'po&#112;en',
            '/(shell_exec)/si'           => 'sh&#101;ll_exec',
            '/(system)/si'               => 'sy&#115;tem',

            // '/(select)/si'               => '&#115;elect',
            '/(drop)/si'                 => 'dro&#112;',
            '/(delete)/si'               => 'd&#101;lete',
            '/(create)/si'               => 'cr#101;ate',
            '/(update)/si'               => 'updat#101;',
            '/(insert)/si'               => 'ins#101;rt',

            // '/(\()/si'                   => '&#40;',
            // '/(\))/si'                   => '&#41;',
        ];
        return (string) preg_replace(array_keys($pattern), array_values($pattern), $_str);
    }

    /**
     * 安全过滤
     * XSS跨站脚本攻击
     * XXE XML 实体扩展攻击
     * @access private
     * @static
     * @param  string $_str
     * @return string
     */
    private static function safe(string &$_str): string
    {
        libxml_disable_entity_loader(true);

        return (string) preg_replace([
            // XSS跨站脚本攻击
            // '/on([a-zA-Z0-9]+)([ ]*?=[ ]*?)(["\'])(.*?)(["\'])/si',
            // '/on([a-zA-Z0-9]+)(=)(.*?)/si',
            // '/on([ a-zA-Z0-9=_()"\']+)/si',
            '/on([a-zA-Z0-9]+)([ a-zA-Z0-9=_()"\']+)/si',


            // '/on([ a-zA-Z0-9=_()\\\\"\'\/]+)/si',

            // '/(id|class|style)=["|\'](.*?)["|\']/si',
            // '/(id|class|style)=([a-zA-Z0-9_\-]+)/si',
            '/(javascript:)(.*?)(\))/si',
            '/<javascript.*?>(.*?)<\/javascript.*?>/si',    '/<(\/?javascript.*?)>/si',
            '/<script.*?>(.*?)<\/script.*?>/si',            '/<(\/?script.*?)>/si',
            '/<applet.*?>(.*?)<\/applet.*?>/si',            '/<(\/?applet.*?)>/si',
            '/<vbscript.*?>(.*?)<\/vbscript.*?>/si',        '/<(\/?vbscript.*?)>/si',
            '/<expression.*?>(.*?)<\/expression.*?>/si',    '/<(\/?expression.*?)>/si',

            // XXE XML 实体扩展攻击
            '/<html.*?>(.*?)<\/html.*?>/si',                '/<(\/?html.*?)>/si',
            '/<title.*?>(.*?)<\/title.*?>/si',              '/<(\/?title.*?)>/si',
            '/<(\/?head)>/si',
            '/<(\/?body)>/si',
            /* '/<head.*?>(.*?)<\/head.*?>/si',
            '/<body.*?>(.*?)<\/body.*?>/si',                '/<(\/?body.*?)>/si', */
            '/<style.*?>(.*?)<\/style.*?>/si',              '/<(\/?style.*?)>/si',
            '/<iframe.*?>(.*?)<\/iframe.*?>/si',            '/<(\/?iframe.*?)>/si',
            '/<frame.*?>(.*?)<\/frame.*?>/si',              '/<(\/?frame.*?)>/si',
            '/<frameset.*?>(.*?)<\/frameset.*?>/si',        '/<(\/?frameset.*?)>/si',
            '/<object.*?>(.*?)<\/object.*?>/si',            '/<(\/?object.*?)>/si',
            '/<xml.*?>(.*?)<\/xml.*?>/si',                  '/<(\/?xml.*?)>/si',
            '/<blink.*?>(.*?)<\/blink.*?>/si',              '/<(\/?blink.*?)>/si',
            '/<link.*?>(.*?)<\/link.*?>/si',                '/<(\/?link.*?)>/si',
            '/<embed.*?>(.*?)<\/embed.*?>/si',              '/<(\/?embed.*?)>/si',
            '/<ilayer.*?>(.*?)<\/ilayer.*?>/si',            '/<(\/?ilayer.*?)>/si',
            '/<layer.*?>(.*?)<\/layer.*?>/si',              '/<(\/?layer.*?)>/si',
            '/<bgsound.*?>(.*?)<\/bgsound.*?>/si',          '/<(\/?bgsound.*?)>/si',
            '/<base.*?\/?>/si',
            '/<meta.*?\/?>/si',

            '/<\?php/si',
            '/<\?/si',
        ], '', $_str);
    }
}
