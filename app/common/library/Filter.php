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
    private static $elements = ['a', 'audio', 'article', 'b', 'br', 'blockquote', 'center', 'dd', 'del', 'div', 'dl', 'dt', 'em', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'i', 'img', 'li', 'ol', 'p', 'pre', 'section', 'small', 'span', 'strong', 'table', 'tbody', 'td', 'th', 'thead', 'tr', 'u', 'ul', 'video'];

    private static $attr = ['alt', 'align', 'async', 'charset', 'class', 'content', 'defer', 'height', 'href', 'id', 'name', 'rel', 'src', 'style', 'target', 'title', 'type', 'width', 'rowspan'];

    private static $func = ['apache_setenv', 'base64_decode', 'call_user_func', 'call_user_func_array', 'chgrp', 'chown', 'chroot', 'eval', 'exec', 'file_get_contents', 'file_put_contents', 'function', 'imap_open', 'ini_alter', 'ini_restore', 'invoke', 'openlog', 'passthru', 'pcntl_alarm', 'pcntl_exec', 'pcntl_fork', 'pcntl_get_last_error', 'pcntl_getpriority', 'pcntl_setpriority', 'pcntl_signal', 'pcntl_signal_dispatch', 'pcntl_sigprocmask', 'pcntl_sigtimedwait', 'pcntl_sigwaitinfo', 'pcntl_strerror', 'pcntl_wait', 'pcntl_waitpid', 'pcntl_wexitstatus', 'pcntl_wifcontinued', 'pcntl_wifexited', 'pcntl_wifsignaled', 'pcntl_wifstopped', 'pcntl_wstopsig', 'pcntl_wtermsig', 'popen', 'popepassthru', 'proc_open', 'putenv', 'readlink', 'shell_exec', 'symlink', 'syslog', 'system', 'select', 'drop', 'delete', 'create', 'insert'];

    // , 'update'

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
                $_data = preg_replace_callback('/(src=["\']+)([a-zA-Z0-9&=#,_:?.\/]+)(["\']+)/si', function ($matches) {
                    return $matches[2]
                        ? 'src="' . File::pathToUrl($matches[2]) . '"'
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
            $words = trim($words);

            if (0 === strpos($words, '--')) {
                continue;
            }

            $words = self::base($words);
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
                $_str = (string) preg_replace_callback('/' . $regex . '/u', function () {
                    return '&#42;&#42;';
                }, $_str);
            }
        }
        fclose($file);

        return $_str;
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
        $_str = str_replace('&nbsp;', '', $_str);
        $_str = strip_tags($_str);

        $_str = (string) preg_replace_callback('/[^\x{4e00}-\x{9fa5}a-zA-Z0-9 ]+/uis', function () {
            return '';
        }, trim($_str));

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
        $regex = '/' . implode('\s*\(|', self::$func) . '/uis';
        $_str = (string) preg_replace_callback($regex, function ($matches) {
            return $matches[0] . '&nbsp;';
        }, $_str);

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

        $regex = '/<(\/?[\w!]+)([\w\-\s]+=[^>]*)>/uis';
        return (string) preg_replace_callback($regex, function ($attr) {
            $attr = array_map('trim', $attr);

            // 过滤json数据
            $attr[2] = preg_replace([
                '/\[.*\]/uis',
                '/\{.*\}/uis',
            ], 'JSON', $attr[2]);

            // 过滤非法属性
            $attr[2] = str_replace(['" ', '\' '], ['"&', '\'&'], $attr[2]);
            $attr[2] = preg_replace_callback('/([\w\-]+)=[^&]+/uis', function ($single) {
                $single = array_map('trim', $single);
                if (false !== stripos($single[0], 'javascript')) {
                    return '';
                } elseif (!in_array(strtolower($single[1]), self::$attr)) {
                    return '';
                } else {
                    return trim($single[0]);
                }
            }, $attr[2]);

            $attr[2] = preg_replace('/[\s&]+/uis', ' ', trim($attr[2]));
            $attr[2] = !empty($attr[2]) ? ' ' . trim($attr[2]) : '';
            return '<' . $attr[1] . $attr[2] . '>';
        }, $_str);
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
        // 过滤非法标签
        if (false !== preg_match_all('/<([\w!]+)[^<>]*>/ui', $_str, $ele)) {
            $ele[1] = array_map(function ($value) {
                $value = strtolower($value);
                $value = trim($value);
                return $value;
            }, $ele[1]);
            $ele[1] = array_filter($ele[1]);
            $ele[1] = array_unique($ele[1]);

            $length = [];
            foreach ($ele[1] as $value) {
                $length[] = strlen($value);
            }

            array_multisort($length, SORT_DESC, $ele[1]);

            $preg = [];
            $preg[] = '/[\'"]+<[\w]+[^<>]*>[\'"]+/uis';
            $preg[] = '/<!--.*?-->/uis';
            foreach ($ele[1] as $value) {
                if (!in_array($value, self::$elements)) {
                    $preg[] = '/<' . $value . '[^<>]*>.*?<\/' . $value . '>/uis';
                    $preg[] = '/<\/?' . $value . '[^<>]*>/ui';
                }
            }

            $_str = (string) preg_replace($preg, '', $_str);
        }

        $_str = preg_replace('/<img\s*>/uis', '', $_str);
        $_str = preg_replace('/<[\w]+>\s*<\/[\w]+>/uis', '', $_str);

        return trim($_str);
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
        $_str = (string) json_decode(str_ireplace(['\u00a0', '\u0020', '\u3000', '\ufeff'], ' ', json_encode($_str)));
        $_str = (string) str_ireplace(['&ensp;', '&emsp;', '&thinsp;', '&zwnj;', '&zwj;', '&#160;', '&nbsp;'], ' ', $_str);

        $pattern = [
            '/<\!--[^<>]+-->/s' => '',
            '/>\s+</'           => '><',
            '/>\s+/'            => '>',
            '/\s+</'            => '<',
            '/\s+/s'            => ' ',
            '/ +/si'            => ' ',
        ];

        $_str = (string) preg_replace(array_keys($pattern), array_values($pattern), $_str);

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
        $pattern = [
            // 全角字符转半角字符
            '０' => '0', '１' => '1', '２' => '2', '３' => '3', '４' => '4', '５' => '5', '６' => '6', '７' => '7', '８' => '8', '９' => '9',
            'Ａ' => 'A', 'Ｂ' => 'B', 'Ｃ' => 'C', 'Ｄ' => 'D', 'Ｅ' => 'E', 'Ｆ' => 'F', 'Ｇ' => 'G', 'Ｈ' => 'H', 'Ｉ' => 'I', 'Ｊ' => 'J', 'Ｋ' => 'K', 'Ｌ' => 'L', 'Ｍ' => 'M', 'Ｎ' => 'N', 'Ｏ' => 'O', 'Ｐ' => 'P', 'Ｑ' => 'Q', 'Ｒ' => 'R', 'Ｓ' => 'S', 'Ｔ' => 'T', 'Ｕ' => 'U', 'Ｖ' => 'V', 'Ｗ' => 'W', 'Ｘ' => 'X', 'Ｙ' => 'Y', 'Ｚ' => 'Z',
            'ａ' => 'a', 'ｂ' => 'b', 'ｃ' => 'c', 'ｄ' => 'd', 'ｅ' => 'e', 'ｆ' => 'f', 'ｇ' => 'g', 'ｈ' => 'h', 'ｉ' => 'i', 'ｊ' => 'j', 'ｋ' => 'k', 'ｌ' => 'l', 'ｍ' => 'm', 'ｎ' => 'n', 'ｏ' => 'o', 'ｐ' => 'p', 'ｑ' => 'q', 'ｒ' => 'r', 'ｓ' => 's', 'ｔ' => 't', 'ｕ' => 'u', 'ｖ' => 'v', 'ｗ' => 'w', 'ｘ' => 'x', 'ｙ' => 'y', 'ｚ' => 'z',
            '（' => '(', '）' => ')', '〔' => '[', '〕' => ']', '【' => '[', '】' => ']', '〖' => '[', '〗' => ']', '｛' => '{', '｝' => '}', '％' => '%', '＋' => '+', '—' => '-', '－' => '-', '～' => '~', '：' => ':', '？' => '?', '！' => '!', '‖' => '|', '　' => ' ',
            '｜' => '|', '〃' => '"',

            // 特殊字符转HTML实体
            '￥' => '&yen;', '™' => '&trade;', '®' => '&reg;', '©' => '&copy;', '`' => '&acute;',
            '(' => '&#40;', ')' => '&#41;',

            // '*' => '&#42;',

            // '_' => '&#95;',
            // '`' => '&#96;',

            // '"' => '&#34;', '\'' => '&#39;',
        ];

        $_str = (string) str_ireplace(array_keys($pattern), array_values($pattern), $_str);

        // 过滤斜杠,反斜杠,点避免非法目录操作
        $_str = trim($_str);
        $_str = trim(ltrim($_str, '\/.'));

        return $_str;
    }
}
