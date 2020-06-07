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
    private static $elements = ['a', 'audio', 'b', 'br', 'blockquote', 'center', 'dd', 'del', 'div', 'dl', 'dt', 'em', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'i', 'img', 'li', 'ol', 'p', 'pre', 'section', 'small', 'span', 'strong', 'table', 'tbody', 'td', 'th', 'thead', 'tr', 'u', 'ul', 'video'];

    private static $attr = ['alt', 'align', 'class', 'height', 'href', 'id', 'rel', 'src', 'style', 'target', 'title', 'width'];

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
            $_data = self::symbol($_data);
            $_data = Emoji::clear($_data);
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
            $_data = self::symbol($_data);
            $_data = Emoji::encode($_data);
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
            $_data = Emoji::decode($_data);
        } elseif (is_array($_data)) {
            foreach ($_data as $key => $value) {
                $_data[$key] = self::decode($value);
            }
        }
        return $_data;
    }

    /**
     * 过滤非汉字英文与数字
     * @access public
     * @static
     * @param  string $_data
     * @return string
     */
    public static function chs_alpha(string $_data): string
    {
        $_data = self::filter($_data);
        $_data = self::decode($_data);

        $str = '';
        // 匹配中英文与数字
        preg_replace_callback('/[\x{4e00}-\x{9fa5}a-zA-Z0-9 ]+/u', function ($matches) use (&$str) {
            $str .= $matches[0];
        }, $_data);

        // 过滤多余空格
        return preg_replace('/( ){2,}/', ' ', trim($str));
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
            '~>\s+<~'     => '><',
            '~>\s+~'      => '>',
            '~\s+<~'      => '<',
            '/( ){2,}/si' => ' ',
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

            // 危险函数(方法)
            'base64_decode'        => 'base64_decode&nbsp;',
            'call_user_func_array' => 'call_user_func_array&nbsp;',
            'call_user_func'       => 'call_user_func&nbsp;',
            'chown'                => 'chown&nbsp;',
            'eval'                 => 'eval&nbsp;',
            'exec'                 => 'exec&nbsp;',
            'file_get_contents'    => 'file_get_contents&nbsp;',
            'file_put_contents'    => 'file_put_contents&nbsp;',
            'invoke'               => 'invoke&nbsp;',
            'passthru'             => 'passthru&nbsp;',
            'phpinfo'              => 'phpinfo&nbsp;',
            'proc_open'            => 'proc_open&nbsp;',
            'popen'                => 'popen&nbsp;',
            'sleep'                => 'sleep&nbsp;',
            'shell_exec'           => 'shell_exec&nbsp;',
            'system'               => 'system&nbsp;',
            '__destruct'           => '__destruct&nbsp;',
            '.php'                 => '-php&nbsp;',

            'select' => 'select&nbsp;',
            'drop'   => 'drop&nbsp;',
            'delete' => 'delete&nbsp;',
            'create' => 'create&nbsp;',
            'update' => 'update&nbsp;',
            'insert' => 'insert&nbsp;',
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

        if (preg_match_all('/<([a-zA-Z0-9!]+).*?>/si', $_str, $ele)) {
            $ele[1] = array_filter($ele[1]);
            $ele[1] = array_unique($ele[1]);
            $ele[1] = array_map('trim', $ele[1]);
            $preg = [];
            foreach ($ele[1] as $value) {
                if (!in_array($value, self::$elements)) {
                    $preg[] = '/<' . $value . '.*?>.*?<\/' . $value . '>/si';
                    $preg[] = '/<' . $value . '.*?\/?>/si';
                }
            }
            $_str = preg_replace($preg, '', $_str);
        }



        // 过滤闭合标签
        $_str = preg_replace_callback('/<([a-zA-Z0-9]+).*?>.*?<\/.*?>/si', function ($matches) {
            $matches = array_map('strtolower', $matches);
            $matches = array_map('trim', $matches);
            $matches[1] = trim($matches[1], '/ ');

            if (in_array($matches[1], self::$elements)) {
                return $matches[0];
            } else {
                // print_r($matches);
            }
        }, $_str);



        // 过滤不闭合标签
        $_str = preg_replace_callback('/<([a-zA-Z0-9]+).*?\/?>/si', function ($matches) {
            $matches = array_map('strtolower', $matches);
            $matches = array_map('trim', $matches);
            $matches[1] = trim($matches[1], '/ ');

            if (in_array($matches[1], self::$elements)) {
                return $matches[0];
            }
        }, $_str);



        // 过滤属性和JS事件
        // [ onclick="alert(1)" onload=eval(ssltest.title) ]在做修改时,请保证括号内代码成功过滤!有新结构体,请追加在括号内!
        $_str = preg_replace_callback('/(<\/?[a-zA-Z0-9]+)(.*?)(\/?>)/si', function ($matches) {
            $matches = array_map('strtolower', $matches);
            $matches = array_map('trim', $matches);
            $matches[2] = preg_replace('/[ ]{1,}([^\w]+)/si', '$1', $matches[2]) . ' ';
            // $matches[2] = preg_replace_callback('/(.*?)=(.*?) /si', function ($ele_attr) {
            //     $ele_attr = array_map('strtolower', $ele_attr);
            //     $ele_attr = array_map('trim', $ele_attr);
            //     if (in_array($ele_attr[1], self::$attr) && false === stripos($ele_attr[2], 'javascript')) {
            //         return $ele_attr[0] . ' ';
            //     }
            // }, $matches[2]);
            $matches[2] = trim($matches[2]);
            $matches[2] = $matches[2] ? ' ' . $matches[2] : '';
            return $matches[1] . $matches[2] . $matches[3];
        }, $_str);



        // 过滤代码
        $_str = preg_replace([
            // 过滤HTML注释
            '/<\!\-\-.*?\-\->/s',

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
            '/(_){4,}/s',
            '/(-){4,}/s',
            '/(=){4,}/s',
        ], '', $_str);



        // 过滤斜杠,反斜杠,点避免非法目录操作
        $_str = trim($_str);
        $_str = trim(trim($_str, ',_-'));
        $_str = trim(ltrim($_str, '\/.'));

        return $_str;
    }
}
