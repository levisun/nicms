<?php

/**
 *
 * 数据安全过滤类
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

use app\common\library\Base64;
use app\common\library\tools\File;

class Filter
{
    private static $elements = ['a', 'audio', 'article', 'b', 'br', 'blockquote', 'center', 'dd', 'del', 'div', 'dl', 'dt', 'em', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'i', 'img', 'li', 'ol', 'p', 'pre', 'section', 'small', 'span', 'strong', 'table', 'tbody', 'td', 'th', 'thead', 'tr', 'u', 'ul', 'video', 'font',];

    private static $attr = ['alt', 'align', 'async', 'charset', 'class', 'content', 'defer', 'height', 'href', 'id', 'name', 'rel', 'src', 'style', 'target', 'title', 'type', 'width', 'rowspan', 'colspan'];

    /**
     * 严格过滤
     * @access public
     * @static
     * @param  string|array $_data
     * @return string|array
     */
    public static function strict($_data)
    {
        if (is_array($_data)) {
            foreach ($_data as $key => $value) {
                $_data[$key] = self::strict($value);
            }
        } else {
            $_data = self::base($_data);
            $_data = Base64::emojiClear($_data);
            $_data = strip_tags($_data);
            $_data = htmlspecialchars($_data, ENT_QUOTES);
            $_data = preg_replace('/&amp;#(\d+)/u', '&#$1', $_data);
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
    public static function htmlEncode($_data)
    {
        if (is_array($_data)) {
            foreach ($_data as $key => $value) {
                $_data[$key] = self::htmlEncode($value);
            }
        } else {
            $_data = self::base($_data);
            $_data = Base64::emojiEncode($_data);
            $_data = htmlspecialchars($_data, ENT_QUOTES);
        }
        return $_data;
    }

    /**
     * 内容解码
     * @access public
     * @static
     * @param  string|array $_data
     * @param  bool $_strict 严格解码(敏感词过滤与图片地址, 注意:不要在后台开启)
     * @return string|array
     */
    public static function htmlDecode($_data, bool $_strict = false)
    {
        if (is_array($_data)) {
            foreach ($_data as $key => $value) {
                $_data[$key] = self::htmlDecode($value, $_strict);
            }
        } else {
            $_data = htmlspecialchars_decode($_data, ENT_QUOTES);
            $_data = Base64::emojiDecode($_data);
            if ($_strict) {
                $_data = self::sensitive($_data);
                $_data = preg_replace_callback('/(src=["\']+)([^<>]+)(["\']+)/si', function ($matches) {
                    return $matches[2]
                        ? 'src="' . File::imgUrl($matches[2]) . '"'
                        : '';
                }, $_data);
            }
        }
        return $_data;
    }

    /**
     * 过滤敏感词
     * @access public
     * @static
     * @param  string $_str
     * @return string
     */
    public static function sensitive(string &$_str): string
    {
        $filename = root_path('extend') . 'sensitive.txt';
        if (!is_file($filename)) {
            return $_str;
        }

        $file = fopen($filename, 'r');
        while (!feof($file) && $words = fgets($file)) {
            if (!$words = trim($words)) {
                continue;
            }

            if (0 === strpos($words, '--')) {
                continue;
            }

            if (!$words = self::base($words)) {
                continue;
            }

            $words = explode(',', $words);
            $words = array_filter($words);
            $words = array_unique($words);

            $length = [];
            foreach ($words as $key => $value) {
                $length[$key] = mb_strlen($value, 'utf-8');

                $words[$key] = (string) preg_replace_callback('/./u', function (array $matches) {
                    $matches[0] = trim(json_encode($matches[0]), '"');
                    $matches[0] = (string) preg_replace_callback('/\\\u([0-9a-f]{4})/si', function ($chs) {
                        return '\x{' . $chs[1] . '}';
                    }, $matches[0]);
                    $matches[0] = '|' !== $matches[0] ? $matches[0] . '\s*' : $matches[0];
                    return $matches[0];
                }, $value);
            }
            array_multisort($length, SORT_DESC, $words);

            $num = 0;
            while ($regex = array_slice($words, 100 * $num, 100)) {
                $num++;
                $regex = implode('|', $regex);
                $_str = (string) preg_replace_callback('/' . $regex . '/u', function ($matches) {
                    $matches[0] = (string) preg_replace('/ +/u', '', $matches[0]);
                    $star = '';
                    for ($i = 0; $i < mb_strlen($matches[0], 'utf-8'); $i++) {
                        $star .= '&#42;';
                    }
                    return $star;
                }, $_str);
            }
        }
        fclose($file);

        return trim($_str);
    }

    /**
     * 过滤非汉字英文与数字
     * @access public
     * @static
     * @param  string $_str
     * @return string
     */
    public static function nonChsAlpha(string $_str): string
    {
        $_str = htmlspecialchars_decode($_str, ENT_QUOTES);
        // ASCII
        $_str = preg_replace('/&#?[\w\d]+;/i', '', $_str);
        $_str = strip_tags($_str);
        $_str = (string) preg_replace('/[^\x{4e00}-\x{9fa5}a-zA-Z0-9 ]+/uis', '', $_str);
        // 连续三个重复的字符
        $_str = (string) preg_replace('/(.)\1{2,}/u', '$1', $_str);
        // 重复符号
        $_str = (string) preg_replace('/([^\x{4e00}-\x{9fa5}a-zA-Z0-9 ])\1/u', '$1', $_str);
        return trim($_str);
    }

    /**
     * 基本过滤
     * @access public
     * @static
     * @param  string $_str
     * @return string
     */
    public static function base(string &$_str): string
    {
        $_str = htmlspecialchars_decode($_str, ENT_QUOTES);
        $_str = self::symbol($_str);
        $_str = self::space($_str);
        $_str = self::html($_str);
        $_str = self::htmlAttr($_str);
        $_str = self::php($_str);
        $_str = self::fun($_str);

        return trim($_str);
    }

    /**
     * 过滤危险函数(方法)
     * @access public
     * @static
     * @param  string $_str
     * @return string
     */
    public static function fun(string &$_str): string
    {
        $_str = (string) preg_replace('/([\w\d]+)\(/uis', '$1&nbsp;(', $_str);
        $_str = (string) preg_replace('/(create|insert|delete|update|select|drop)+ +/uis', '$1&nbsp;', $_str);
        $_str = str_replace(['(', ')'], ['&#40;', '&#41;'], $_str);
        return trim($_str);
    }

    /**
     * 过滤PHP代码
     * @access public
     * @static
     * @param  string $_str
     * @return string
     */
    public static function php(string &$_str): string
    {
        $_str = (string) preg_replace([
            '/<\?php.*?\?>/uis',
            '/<\?.*?\?>/uis',
            '/<\?php/uis',
            '/<\?/us',
            '/\?>/us',
        ], '', $_str);

        libxml_disable_entity_loader(true);

        return trim($_str);
    }

    /**
     * 过滤HTML标签属性
     * @access public
     * @static
     * @param  string $_str
     * @param  bool   $_strict
     * @return string
     */
    public static function htmlAttr(string &$_str): string
    {
        // 做修改时,请保证括号内代码成功过滤!有新结构体,请追加在括号内!
        // [ onclick="alert(1)" onload=eval(ssltest.title) data-d={1:\'12 3213\',22=2:\' dabdd\'} ]

        // 剔除JS代码中的标签
        $_str = preg_replace('/[\'"]+<\/?\w+[^<>]*>[\'"]+/uis', '', $_str);

        $dom = new \DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);
        $_str = htmlspecialchars_decode($_str, ENT_QUOTES);
        $_str = preg_replace('/<\/?body[^<>]*>/is', '', $_str);
        $_str = '<body>' . $_str . '</body>';
        $dom->loadHTML('<meta charset="UTF-8">' . $_str);
        libxml_clear_errors();

        $xpath = new \DOMXPath($dom);
        $nodes = $xpath->query('//*[@*]');
        foreach ($nodes as $node) {
            $attributes = [];
            foreach ($node->attributes as $attr) {
                if (!in_array(strtolower($attr->nodeName), self::$attr)) {
                    $attributes[] = $attr->nodeName;
                }
                if (false !== stripos(strtolower($node->getAttribute($attr->nodeName)), 'javascript')) {
                    $attributes[] = $attr->nodeName;
                }
            }
            foreach ($attributes as $name) {
                $node->removeAttribute($name);
            }
        }

        $nodes = $xpath->query('//body');
        foreach ($nodes as $node) {
            $node = $dom->saveHTML($node);
            $_str = preg_replace('/<\/?body>/is', '', $node);
        }

        return $_str;
    }

    /**
     * 过滤HTML标签
     * @access public
     * @static
     * @param  string $_str
     * @return string
     */
    public static function html(string &$_str): string
    {
        // 剔除JS代码中的标签
        $_str = preg_replace('/[\'"]+<\/?\w+[^<>]*>[\'"]+/uis', '', $_str);
        // 剔除html注释
        $_str = preg_replace('/<\!\-\-[^<>]+\-\->/s', '', $_str);

        $dom = new \DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);
        $_str = htmlspecialchars_decode($_str, ENT_QUOTES);
        $_str = preg_replace('/<\/?body[^<>]*>/is', '', $_str);
        $_str = '<body>' . $_str . '</body>';
        $dom->loadHTML('<meta charset="UTF-8">' . $_str);
        libxml_clear_errors();

        $xpath = new \DOMXPath($dom);
        $nodes = $xpath->query('//*');

        // 删除标签
        foreach ($nodes as $node) {
            if (!in_array($node->nodeName, array_merge(['html', 'body'], self::$elements))) {
                $node->parentNode->removeChild($node);
            }
        }

        $nodes = $xpath->query('//body');
        foreach ($nodes as $node) {
            $node = $dom->saveHTML($node);
            $_str = preg_replace('/<\/?body>/is', '', $node);
        }

        $_str = preg_replace([
            '/<img\s*>/uis',
            '/<[\w]+>\s*<\/[\w]+>/uis'
        ], '', $_str);

        return $_str;
    }

    /**
     * 过滤空格回车制表符等
     * @access public
     * @static
     * @param  string $_str
     * @return string
     */
    public static function space(string &$_str): string
    {
        // 不间断空格\u00a0,主要用在office中,让一个单词在结尾处不会换行显示,快捷键ctrl+shift+space
        // 半角空格(英文符号)\u0020,代码中常用的
        // 全角空格(中文符号)\u3000,中文文章中使用
        $_str = preg_replace('/[\x{00a0}\x{0020}\x{3000}\x{feff}]/uis', ' ', $_str);

        $_str = (string) str_ireplace(['&ensp;', '&emsp;', '&thinsp;', '&zwnj;', '&zwj;', '&#160;', '&nbsp;'], ' ', $_str);

        $pattern = [
            '/>[\r\n]+</' => '><',
            '/>[\r\n]+/'  => '>',
            '/[\r\n]+</'  => '<',
            '/[\r\n]+/s'  => ' ',
            // '/ +/si'      => ' ',
        ];

        $_str = (string) preg_replace(array_keys($pattern), array_values($pattern), $_str);

        $_str = preg_replace_callback('/<[^<>]+>/', function ($ele) {
            return preg_replace('/ +/', ' ', $ele[0]);
        }, $_str);

        return trim($_str);
    }

    /**
     * 全角字符转半角
     * @access public
     * @static
     * @param  string $_str
     * @return string
     */
    public static function symbol(string &$_str): string
    {
        $_str = (string) preg_replace_callback('/[\x{3000}\x{ff01}-\x{ff5f}]/uis', function ($chs) {
            $chs = trim(json_encode($chs[0]), '"');
            $chs = str_replace('\u', '', $chs);
            $chs = hexdec($chs);
            return $chs === 12288 ? chr(32) : chr($chs - 65248);
        }, $_str);

        $pattern = [
            // 全角字符转半角字符
            '〔' => '[', '〕' => ']', '【' => '[', '】' => ']', '〖' => '[', '〗' => ']', '‖' => '|', '〃' => '"',
            // 特殊字符转HTML实体
            '￥' => '&yen;', '™' => '&trade;', '®' => '&reg;', '©' => '&copy;', '`' => '&acute;',
            '~' => '&#152;',

            // '*' => '&#42;',
            // '_' => '&#95;',
            // '"' => '&#34;', '\'' => '&#39;',
        ];

        $_str = (string) str_ireplace(array_keys($pattern), array_values($pattern), $_str);

        // 过滤斜杠,反斜杠,点避免非法目录操作
        $_str = trim($_str);
        $_str = trim(ltrim($_str, '\/.'));

        return $_str;
    }
}
