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

class Filter
{
    private static $elements = ['a', 'audio', 'b', 'br', 'blockquote', 'center', 'dd', 'del', 'div', 'dl', 'dt', 'em', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'i', 'img', 'li', 'ol', 'p', 'pre', 'section', 'small', 'span', 'strong', 'table', 'tbody', 'td', 'th', 'thead', 'tr', 'u', 'ul', 'video'];

    private static $attr = ['alt', 'align', 'async', 'charset', 'class', 'content', 'defer', 'height', 'href', 'id', 'name', 'rel', 'src', 'style', 'target', 'title', 'type', 'width'];

    private static $func = ['apache_setenv', 'base64_decode', 'call_user_func', 'call_user_func_array', 'chgrp', 'chown', 'chroot', 'eval', 'exec', 'file_get_contents', 'file_put_contents', 'function', 'imap_open', 'ini_alter', 'ini_restore', 'invoke', 'openlog', 'passthru', 'pcntl_alarm', 'pcntl_exec', 'pcntl_fork', 'pcntl_get_last_error', 'pcntl_getpriority', 'pcntl_setpriority', 'pcntl_signal', 'pcntl_signal_dispatch', 'pcntl_sigprocmask', 'pcntl_sigtimedwait', 'pcntl_sigwaitinfo', 'pcntl_strerror', 'pcntl_wait', 'pcntl_waitpid', 'pcntl_wexitstatus', 'pcntl_wifcontinued', 'pcntl_wifexited', 'pcntl_wifsignaled', 'pcntl_wifstopped', 'pcntl_wstopsig', 'pcntl_wtermsig', 'popen', 'popepassthru', 'proc_open', 'putenv', 'readlink', 'shell_exec', 'symlink', 'syslog', 'system', 'select', 'drop', 'delete', 'create', 'update', 'insert'];

    /**
     * 默认过滤
     * @access public
     * @static
     * @param  string|array $_data
     * @return string|array
     */
    public static function safe($_data)
    {
        if (is_string($_data) && $_data) {
            $_data = self::base($_data);
            $_data = Base64::emojiClear($_data);
            $_data = strip_tags($_data);
            $_data = htmlspecialchars($_data, ENT_QUOTES);
        } elseif (is_array($_data)) {
            foreach ($_data as $key => $value) {
                $_data[$key] = self::safe($value);
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
    public static function contentEncode($_data)
    {
        if (is_string($_data) && $_data) {
            $_data = self::base($_data);
            $_data = Base64::emojiEncode($_data);
            $_data = htmlspecialchars($_data, ENT_QUOTES);
        } elseif (is_array($_data)) {
            foreach ($_data as $key => $value) {
                $_data[$key] = self::contentEncode($value);
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
    public static function contentDecode($_data)
    {
        if (is_string($_data) && $_data) {
            $_data = htmlspecialchars_decode($_data, ENT_QUOTES);
            $_data = Base64::emojiDecode($_data);
        } elseif (is_array($_data)) {
            foreach ($_data as $key => $value) {
                $_data[$key] = self::contentDecode($value);
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
        $words = [];

        $file = root_path('extend') . 'sensitive.txt';
        if (is_file($file) && $words = file_get_contents($file)) {
            $words = self::symbol($words);
            $words = self::space($words);
            $words = preg_replace('/--\s?.*?\s+/i', '', $words);
            $words = explode(',', $words);
        }

        sort($words);
        $words = array_map(function ($value) {
            return strtolower(trim($value));
        }, $words);
        $words = array_filter($words);
        $words = array_unique($words);

        $length = [];
        foreach ($words as $key => $value) {
            $length[$key] = mb_strlen($value, 'utf-8');
        }
        array_multisort($length, SORT_DESC, $words);

        $length = 200;
        $num = 0;
        while ($pattern = array_slice($words, $length * $num, $length)) {
            $num++;
            $_str = preg_replace('/' . implode('|', $pattern) . '/i', '&#42;', $_str);
        }

        return $_str;
    }

    /**
     * 过滤非汉字英文与数字
     * @access public
     * @static
     * @param  string $_str
     * @return string
     */
    public static function non_chs_alpha(string &$_str): string
    {
        $_str = (string) preg_replace_callback('/[^\x{4e00}-\x{9fa5}a-zA-Z\d ]+/u', function () {
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
        $_str = self::html_attr($_str);
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
        $regex = '/' . implode('\(|', self::$func) . '/si';

        $_str = (string) preg_replace_callback($regex, function ($matches) {
            return str_replace('(', '&nbsp;(', trim($matches[0]));
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
            '/<\?php.*?\?>/si',
            '/<\?.*?\?>/si',
            '/<\?php/si',
            '/<\?/s',
            '/\?>/s',
        ], '', $_str);

        libxml_disable_entity_loader(true);

        return trim($_str);
    }

    /**
     * 过滤HTML标签属性
     * @access public
     * @static
     * @param  string $_str
     * @return string
     */
    public static function html_attr(string &$_str): string
    {
        // [ onclick="alert(1)" onload=eval(ssltest.title) data-d={1:\'12 3213\',22=2:\' dabdd\'} ]在做修改时,请保证括号内代码成功过滤!有新结构体,请追加在括号内!

        return (string) preg_replace_callback('/(<\/?[\w\d!]+)([\w\d\- ]+=[^>]*)/si', function ($attr) {
            $attr = array_map('trim', $attr);

            // 过滤json数据
            $attr[2] = preg_replace([
                '/\{+.*?\}+/si',
                '/\[+.*?\]+/si',
            ], '', $attr[2]);

            // 过滤非法属性
            $attr[2] = preg_replace_callback('/([\w\d\-]+)=[^\s]*/si', function ($single) {
                $single = array_map('trim', $single);
                if (false !== stripos($single[0], 'javascript')) {
                    return '';
                }

                if (!in_array(strtolower($single[1]), self::$attr)) {
                    return '';
                }

                return trim($single[0]);
            }, $attr[2]);
            // 清除多余空格
            $attr[2] = preg_replace('/\s+/si', ' ', $attr[2]);
            $attr[2] = trim($attr[2]);

            $attr[2] = !empty($attr[2]) ? ' ' . $attr[2] : '';
            return $attr[1] . $attr[2];
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
        if (false !== preg_match_all('/<([\w\d!]+)[^<>]*>/si', $_str, $ele)) {
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
            foreach ($ele[1] as $value) {
                if (!in_array($value, self::$elements)) {
                    $preg[] = '/<' . $value . '[^<>]*>.*?<\/' . $value . '>/si';
                    $preg[] = '/<' . $value . '[^<>]*>/si';
                }
            }

            $_str = (string) preg_replace($preg, '', $_str);
        }

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
        $_str = (string) str_ireplace(['&ensp;', '&emsp;', '&thinsp;', '&zwnj;', '&zwj;'], '&nbsp;', $_str);

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
            // '`' => '&#96;',

            // '"' => '&#34;', '\'' => '&#39;',
            // '*' => '&#42;',
        ];

        $_str = (string) str_ireplace(array_keys($pattern), array_values($pattern), $_str);

        // 过滤斜杠,反斜杠,点避免非法目录操作
        $_str = trim($_str);
        $_str = trim(ltrim($_str, '\/.'));

        return $_str;
    }
}
