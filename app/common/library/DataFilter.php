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

use think\facade\Request;
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
            $_data = self::safe($_data);
            $_data = strip_tags($_data);
            $_data = self::symbol($_data);
            $_data = Emoji::clear($_data);
            $_data = htmlspecialchars($_data, ENT_QUOTES);
        } elseif (is_array($_data)) {
            foreach ($_data as $key => $value) {
                $_data[$key] = self::filter($value);
            }
        }
        return $_data;
    }

    /**
     * 内容编码(转义)
     * @access public
     * @static
     * @param  string|array $_data
     * @return string|array
     */
    public static function encode($_data)
    {
        if (is_string($_data)) {
            $_data = self::safe($_data);
            $_data = self::element($_data);
            $_data = self::symbol($_data);
            $_data = Emoji::encode($_data);
            $_data = str_replace('<p>&nbsp;</p>', '', $_data);
            $_data = htmlspecialchars($_data, ENT_QUOTES);
        } elseif (is_array($_data)) {
            foreach ($_data as $key => $value) {
                $_data[$key] = self::encode($value);
            }
        }
        return $_data;
    }

    /**
     * 内容解码
     * @access public
     * @static
     * @param  string|array $_data
     * @return string|array
     */
    public static function decode($_data)
    {
        if (is_string($_data)) {
            $_data = htmlspecialchars_decode($_data, ENT_QUOTES);
            $_data =  Emoji::decode($_data);
        } elseif (is_array($_data)) {
            foreach ($_data as $key => $value) {
                $_data[$key] = self::decode($value);
            }
        }
        return $_data;
    }

    /**
     * 过滤并分词
     * @access public
     * @static
     * @param  string $_data
     * @return array
     */
    public static function word(string $_data, int $_length = 0): array
    {
        $_data = self::filter($_data);
        $_data = self::decode($_data);

        $str = '';
        // 匹配中文
        preg_replace_callback('/\\\u[4-9a-f]{1}[0-9a-f]{3}/si', function ($matches) use (&$str) {
            $str .= json_decode('"' . $matches[0] . '"');
        }, json_encode($_data));
        // 匹配英文与数字
        preg_replace_callback('/[a-zA-Z0-9 ]/si', function ($matches) use (&$str) {
            $str .= $matches[0];
        }, $_data);
        // 过滤多余空格
        $str = preg_replace('/( ){2,}/', ' ', trim($str));

        if ($str) {
            // 分词
            @ini_set('memory_limit', '128M');
            $path = app()->getRootPath() . 'vendor/lizhichao/word/Data/dict.json';
            define('_VIC_WORD_DICT_PATH_', $path);
            $fc = new \Lizhichao\Word\VicWord('json');
            $str = $fc->getAutoWord($str);
            unset($fc);

            // 取出有效词
            foreach ($str as $key => $value) {
                if (1 < mb_strlen($value[0], 'UTF-8')) {
                    $str[$key] = trim($value[0]);
                } else {
                    unset($str[$key]);
                }
            }
            // 过滤重复词
            $str = array_unique($str);

            // 如果设定长度,返回对应长度数组
            return $_length ? array_slice($str, 0, $_length) : $str;
        } else {
            return [];
        }
    }

    /**
     * 过滤标签
     * @access private
     * @static
     * @param  string $_str
     * @return string
     */
    private static function element(string &$_str): string
    {
        // 保留标签
        $_str = strip_tags($_str, '<a><audio><b><br><blockquote><center><dd><del><div><dl><dt><em><h1><h2><h3><h4><h5><h6><i><img><li><ol><p><pre><section><small><span><strong><table><tbody><td><th><thead><tr><u><ul><video>');

        return preg_replace_callback('/<([a-zA-Z1-6]+)(.*?)\/?>/si', function ($matches) {
            // 过滤标签属性
            $matches[2] = preg_replace_callback('/([a-zA-Z0-9-_]+)=["\']?(.*?)["\']?( )/si', function ($ema) {
                $result = '';
                $ema[1] = strtolower($ema[1]);
                // 过滤非法属性 target rel

                // 保留属性
                $attr = ['alt', 'title', 'height', 'width', 'align'];
                if (in_array($ema[1], $attr) && $ema[2]) {
                    $result = ' ' . $ema[1] . '="' . $ema[2] . '"';
                }

                // 图片属性
                elseif ('src' === $ema[1]) {
                    // 本地网络地址
                    if (false !== stripos($ema[2], Request::rootDomain())) {
                        $ema[2] = parse_url($ema[2], PHP_URL_PATH);

                        // 追加get参数
                        $ema[2] .= $ema[2] ? '?' . parse_url($ema[2], PHP_URL_QUERY) : '';
                    }

                    $result = $ema[2]
                        ? ' ' . $ema[1] . '="' . $ema[2] . '"'
                        : '';
                }

                // 超链接属性
                elseif ('href' === $ema[1]) {
                    // 本地网络地址
                    if (false !== stripos($ema[2], Request::rootDomain())) {
                        $ema[2] = rtrim($ema[2], '/');
                        $ema[2] = parse_url($ema[2], PHP_URL_PATH);

                        // 追加get参数
                        $ema[2] .= $ema[2] ? '?' . parse_url($ema[2], PHP_URL_QUERY) : '';

                        $ema[2] = $ema[2] ?: '/';

                        // 添加新窗口打开属性
                        $ema[2] = '"' . $ema[2] . '" target="_blank"';
                    }
                    // 外链
                    elseif (0 === stripos($ema[2], 'http')) {
                        $ema[2] = '"' . $ema[2] . '" rel="nofollow" target="_blank"';
                    } else {
                        $ema[2] = '"' . $ema[2] . '"';
                    }

                    $result = $ema[2]
                        ? ' ' . $ema[1] . '=' . $ema[2]
                        : '';
                }

                return $result;
            }, $matches[2] . ' ');

            return '<' . strtolower(trim($matches[1])) . rtrim($matches[2]) . '>';
        }, $_str);
    }

    /**
     * 过滤字符与回车等
     * @access private
     * @static
     * @param  string $_str
     * @return string
     */
    private static function symbol(string &$_str): string
    {
        // 过滤空格回车制表符等
        $pattern = [
            '~>\s+<~'        => '><',
            '~>\s+~'         => '>',
            '~\s+<~'         => '<',
            '/( ){2,}/si'    => ' ',
        ];
        $_str = (string) preg_replace(array_keys($pattern), array_values($pattern), $_str);

        $pattern = [
            // 全角字符转半角字符
            '０' => '0', '１' => '1', '２' => '2', '３' => '3', '４' => '4', '５' => '5', '６' => '6', '７' => '7', '８' => '8', '９' => '9',
            'Ａ' => 'A', 'Ｂ' => 'B', 'Ｃ' => 'C', 'Ｄ' => 'D', 'Ｅ' => 'E', 'Ｆ' => 'F', 'Ｇ' => 'G', 'Ｈ' => 'H', 'Ｉ' => 'I', 'Ｊ' => 'J', 'Ｋ' => 'K', 'Ｌ' => 'L', 'Ｍ' => 'M', 'Ｎ' => 'N', 'Ｏ' => 'O', 'Ｐ' => 'P', 'Ｑ' => 'Q', 'Ｒ' => 'R', 'Ｓ' => 'S', 'Ｔ' => 'T', 'Ｕ' => 'U', 'Ｖ' => 'V', 'Ｗ' => 'W', 'Ｘ' => 'X', 'Ｙ' => 'Y', 'Ｚ' => 'Z',
            'ａ' => 'a', 'ｂ' => 'b', 'ｃ' => 'c', 'ｄ' => 'd', 'ｅ' => 'e', 'ｆ' => 'f', 'ｇ' => 'g', 'ｈ' => 'h', 'ｉ' => 'i', 'ｊ' => 'j', 'ｋ' => 'k', 'ｌ' => 'l', 'ｍ' => 'm', 'ｎ' => 'n', 'ｏ' => 'o', 'ｐ' => 'p', 'ｑ' => 'q', 'ｒ' => 'r', 'ｓ' => 's', 'ｔ' => 't', 'ｕ' => 'u', 'ｖ' => 'v', 'ｗ' => 'w', 'ｘ' => 'x', 'ｙ' => 'y', 'ｚ' => 'z',
            '（' => '(', '）' => ')', '〔' => '[', '〕' => ']', '【' => '[', '】' => ']', '〖' => '[', '〗' => ']', '｛' => '{', '｝' => '}', '％' => '%', '＋' => '+', '—' => '-', '－' => '-', '～' => '~', '：' => ':', '？' => '?', '！' => '!', '‖' => '|', '　' => ' ',
            '｜' => '|', '〃' => '"',

            // 特殊字符转HTML实体
            '*' => '&lowast;', '￥' => '&yen;', '™' => '&trade;', '®' => '&reg;', '©' => '&copy;', '`' => '&acute;',
            // '“' => '&quot;', '”' => '&quot;', '‘' => '&acute;', '’' => '&acute;',

            // 危险函数(方法)
            'base64_decode'        => 'base64-decode&nbsp;',
            'call_user_func_array' => 'call-user-func-array&nbsp;',
            'call_user_func'       => 'call-user-unc&nbsp;',
            'chown'                => 'chown&nbsp;',
            'eval'                 => 'eval&nbsp;',
            'exec'                 => 'exec&nbsp;',
            'file_get_contents'    => 'file-get-contents&nbsp;',
            'file_put_contents'    => 'file-put-contents&nbsp;',
            // 'function'             => 'function&nbsp;',
            'invoke'               => 'invoke&nbsp;',
            'passthru'             => 'passthru&nbsp;',
            'phpinfo'              => 'phpinfo&nbsp;',
            'proc_open'            => 'proc-open&nbsp;',
            'popen'                => 'popen&nbsp;',
            'sleep'                => 'sleep&nbsp;',
            'shell_exec'           => 'shell-exec&nbsp;',
            'system'               => 'system&nbsp;',
            '__destruct'           => '--destruct&nbsp;',
            '.php'                 => '-php&nbsp;',

            'select' => 'select&nbsp;',
            'drop'   => 'drop&nbsp;',
            'delete' => 'delete&nbsp;',
            'create' => 'create&nbsp;',
            'update' => 'update&nbsp;',
            'insert' => 'insert&nbsp;',

            // 'select'               => '&#115;elect',
            // 'drop'                 => 'dro&#112;',
            // 'delete'               => 'd&#101;lete',
            // 'create'               => 'cr#101;ate',
            // 'update'               => 'updat#101;',
            // 'insert'               => 'ins#101;rt',
        ];

        return str_replace(array_keys($pattern), array_values($pattern), $_str);
    }

    /**
     * 过滤PHP危害函数方法
     * @access private
     * @static
     * @param  string $_str
     * @return string
     */
    private static function funSymbol(string &$_str): string
    {
        $pattern = [
            // 特殊字符转义
            'base64_decode'        => 'ba&#115;e64_decode',
            'call_user_func_array' => 'cal&#108;_user_func_array',
            'call_user_func'       => 'cal&#108;_user_func',
            'chown'                => 'ch&#111;wn',
            'eval'                 => 'ev&#97;l',
            'exec'                 => 'ex&#101;c',
            'passthru'             => 'pa&#115;sthru',
            'phpinfo'              => 'ph&#112;info',
            'proc_open'            => 'pr&#111;c_open',
            'popen'                => 'po&#112;en',
            'shell_exec'           => 'sh&#101;ll_ex&#101;c',
            'system'               => 'sy&#115;tem',

            // 'select'               => '&#115;elect',
            // 'drop'                 => 'dro&#112;',
            // 'delete'               => 'd&#101;lete',
            // 'create'               => 'cr#101;ate',
            // 'update'               => 'updat#101;',
            // 'insert'               => 'ins#101;rt',
        ];

        return str_replace(array_keys($pattern), array_values($pattern), $_str);
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

        $_str = (string) preg_replace([
            // 过滤有害HTML标签及内容
            '/<html.*?>.*?<\/html.*?>/si',   '/<(\/?html.*?)>/si',
            '/<head.*?>.*?<\/head.*?>/si',   '/<(\/?head)>/si',
            '/<body.*?>.*?<\/body.*?>/si',   '/<(\/?body.*?)>/si',
            '/<title.*?>.*?<\/title.*?>/si', '/<(\/?title.*?)>/si',
            '/<link.*?\/?>/si',
            '/<meta.*?\/?>/si',
            '/<base.*?\/?>/si',
            '/<script.*?>.*?<\/script.*?>/si', '/<(\/?script.*?)>/si',
            '/<style.*?>.*?<\/style.*?>/si',   '/<(\/?style.*?)>/si',
            '/javascript:.*?[);]+/si',

            '/<iframe.*?>.*?<\/iframe.*?>/si',     '/<(\/?iframe.*?)>/si',
            '/<frame.*?>.*?<\/frame.*?>/si',       '/<(\/?frame.*?)>/si',
            '/<frameset.*?>.*?<\/frameset.*?>/si', '/<(\/?frameset.*?)>/si',
            '/<object.*?>.*?<\/object.*?>/si',     '/<(\/?object.*?)>/si',
            '/<xml.*?>.*?<\/xml.*?>/si',           '/<(\/?xml.*?)>/si',
            '/<embed.*?>.*?<\/embed.*?>/si',       '/<(\/?embed.*?)>/si',
            '/<ilayer.*?>.*?<\/ilayer.*?>/si',     '/<(\/?ilayer.*?)>/si',
            '/<layer.*?>.*?<\/layer.*?>/si',       '/<(\/?layer.*?)>/si',
            '/<bgsound.*?>.*?<\/bgsound.*?>/si',   '/<(\/?bgsound.*?)>/si',
            '/<\!\-\-.*?\-\->/s',

            // JS脚本注入
            // '/on([a-zA-Z0-9]+)([ ]*?=[ ]*?)(["\'])(.*?)(["\'])/si',
            // '/on([a-zA-Z0-9 ]+)=([ a-zA-Z0-9_("\']+)(["\');]+)/si',

            // 过滤JS结构[ onclick="alert(1)" onload=eval(ssltest.title) ]在做修改时,请保证括号内代码成功过滤!有新结构体,请追加在括号内!
            '/on[a-zA-z0-9 ]+=["\' ]?[a-zA-Z0-9_.(]+.*?\)?["\'; ]?/si',
            // 同上[ title=alert(1578859217) ]
            '/=["\' ]?[a-zA-Z0-9_.]+\(.*?\)/si',

            // 过滤PHP代码
            '/<\?php.*?\?>/si',
            '/<\?.*?\?>/si',
            '/<\?php/si',
            '/<\?/s',
            '/\?>/s',

            // 过滤回车与重复字符
            '/(\s+\n|\r)/s',
            '/(\t|\0|\x0B)/s',
            '/( ){2,}/s',
            '/(_){2,}/s',
            '/(-){3,}/s',
            '/(=){4,}/s',
        ], '', $_str);

        // 过滤斜杠,反斜杠,点避免非法目录操作
        $_str = trim($_str);
        $_str = trim(trim($_str, ',_-'));
        $_str = trim(ltrim($_str, '\/.'));

        return $_str;
    }
}
