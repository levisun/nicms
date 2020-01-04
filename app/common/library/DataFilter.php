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
            $_data = trim($_data);
            $_data = self::safe($_data);
            $_data = self::funSymbol($_data);
            $_data = self::enter($_data);
            $_data = strip_tags($_data);
            $_data = (new Emoji)->clear($_data);
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
            $_data = trim($_data);
            $_data = self::safe($_data);
            $_data = self::funSymbol($_data);
            $_data = self::enter($_data);
            $_data = self::element($_data);
            $_data = (new Emoji)->encode($_data);
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
    public static function decode($_data)
    {
        if (is_string($_data)) {
            $_data = trim($_data);
            $_data = htmlspecialchars_decode($_data, ENT_QUOTES);
            $_data = (new Emoji)->decode($_data);
            $_data = self::element($_data);
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
        $pattern = '/[~!@#$%^&\*()_+-={}|\[\]\\\:";\\\'<>\?,.\/]/si';
        $_data = preg_replace($pattern, '', $_data);
        $pattern = [
            '·', '~', '！', '@', '#', '￥', '%', '…', '&', '*', '（', '）', '—', '—', '+', '-', '=', '｛', '｝', '|', '【', '】', '、', '：', '“', '；', '‘', '《', '》', '？', '，', '。', '、', '’', '”',
            '〖', '〗', '『', '』', '「', '」', '〈', '〉', '〔', '〕', '｜', '〃', '～', '々', '…', '—', '·', 'ˉ', 'ˇ', '¨',
            '┌', '┍', '┎', '┏', '┐', '┑', '┒', '┓', '─', '┄', '┈', '├', '┝', '┞', '┟', '┠', '┡', '┢', '┣', '│', '┆', '┊', '┬', '┭', '┮', '┯', '┰', '┱', '┲', '┳', '┼', '┽', '┾', '┿', '╀', '╁', '╂', '╃', '└', '┕', '┖', '┗', '┘', '┙', '┚', '┛', '━', '┅', '┉', '┤', '┥', '┦', '┧', '┨', '┩', '┪', '┫', '┃', '┇', '┋', '┴', '┵', '┶', '┷', '┸', '┹', '┺', '┻', '╄', '╅', '╆', '╇', '╈', '╉', '╊', '╋',
            '§', '№', '☆', '★', '○', '●', '◎', '◇', '◆', '□', '■', '△', '▲', '※', '→', '←', '↑', '↓', '〓', '＃', '＆', '＠', '＼', '＾', '＿', '￣', '―', '♂', '♀',
        ];
        $_data = str_replace($pattern, '', $_data);

        @ini_set('memory_limit', '256M');
        $path = app()->getRootPath() . 'vendor/lizhichao/word/Data/dict.json';
        define('_VIC_WORD_DICT_PATH_', $path);

        $fc = new \Lizhichao\Word\VicWord('json');
        $_data = $fc->getAutoWord($_data);
        unset($fc);
        foreach ($_data as $key => $value) {
            if (1 < mb_strlen($value[0], 'utf-8')) {
                $_data[$key] = $value[0];
            } else {
                unset($_data[$key]);
            }
        }
        $_data = array_unique($_data);
        sort($_data);
        if ($_length) {
            $_data = array_slice($_data, 0, $_length);
        }
        return $_data;
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
        $allowable_tags = '<a><audio><b><br><center><dd><del><div><dl><dt><em><h1><h2><h3><h4><h5><h6><i><img><li><ol><p><pre><section><small><span><strong><table><tbody><td><th><thead><tr><u><ul><video>';
        $_str = strip_tags($_str, $allowable_tags);

        return preg_replace_callback('/<(\/)?([a-zA-Z1-6]+)(.*?)>/si', function ($matches) {
            $matches[3] = preg_replace_callback('/([a-zA-Z0-9-_]+)=(.*?)( )/si', function ($ema) {
                $ema[1] = strtolower($ema[1]);
                $ema[2] = trim($ema[2], '"\'');
                $ema[2] = trim($ema[2]);
                $ema[2] = str_replace(['"', '\'', '<', '>'], '', $ema[2]);

                // 过滤外链
                if ('href' == $ema[1] && false !== stripos($ema[2], Request::rootDomain())) {
                    return ' ' . $ema[1] . '="' . $ema[2] . '"';
                }

                // 过滤非法属性
                $attr = ['src', 'alt', 'title', 'target', 'rel', 'height', 'width', 'align'];
                if (!in_array($ema[1], $attr)) {
                    return;
                } elseif ($ema[2]) {
                    return ' ' . $ema[1] . '="' . $ema[2] . '"';
                } else {
                    return;
                }
            }, $matches[3] . ' ');

            return '<' . trim($matches[1]) . trim($matches[2]) . rtrim($matches[3]) . '>';
        }, $_str);
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
            // 过滤空格回车制表符等
            '~>\s+<~'        => '><',
            '~>\s+~'         => '>',
            '~\s+<~'         => '<',
            '/(\s+\n|\r)/si' => '',
            '/( ){2,}/si'    => ' ',
            '/(_){2,}/si'    => '',
            '/(-){2,}/si'    => '',
            '/(=){2,}/si'    => '',
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
    private static function funSymbol(string &$_str): string
    {
        $pattern = [
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
            'shell_exec'           => 'sh&#101;ll_exec',
            'system'               => 'sy&#115;tem',

            'select'               => '&#115;elect',
            'drop'                 => 'dro&#112;',
            'delete'               => 'd&#101;lete',
            'create'               => 'cr#101;ate',
            'update'               => 'updat#101;',
            'insert'               => 'ins#101;rt',

            // '/(\()/si'                   => '&#40;',
            // '/(\))/si'                   => '&#41;',

            '*' => '&lowast;', '`' => '&acute;', '￥' => '&yen;', '™' => '&trade;', '®' => '&reg;', '©' => '&copy;',

            '０' => '0', '１' => '1', '２' => '2', '３' => '3', '４' => '4', '５' => '5', '６' => '6', '７' => '7', '８' => '8', '９' => '9',
            'Ａ' => 'A', 'Ｂ' => 'B', 'Ｃ' => 'C', 'Ｄ' => 'D', 'Ｅ' => 'E', 'Ｆ' => 'F', 'Ｇ' => 'G', 'Ｈ' => 'H', 'Ｉ' => 'I', 'Ｊ' => 'J', 'Ｋ' => 'K', 'Ｌ' => 'L', 'Ｍ' => 'M', 'Ｎ' => 'N', 'Ｏ' => 'O', 'Ｐ' => 'P', 'Ｑ' => 'Q', 'Ｒ' => 'R', 'Ｓ' => 'S', 'Ｔ' => 'T', 'Ｕ' => 'U', 'Ｖ' => 'V', 'Ｗ' => 'W', 'Ｘ' => 'X', 'Ｙ' => 'Y', 'Ｚ' => 'Z',
            'ａ' => 'a', 'ｂ' => 'b', 'ｃ' => 'c', 'ｄ' => 'd', 'ｅ' => 'e', 'ｆ' => 'f', 'ｇ' => 'g', 'ｈ' => 'h', 'ｉ' => 'i', 'ｊ' => 'j', 'ｋ' => 'k', 'ｌ' => 'l', 'ｍ' => 'm', 'ｎ' => 'n', 'ｏ' => 'o', 'ｐ' => 'p', 'ｑ' => 'q', 'ｒ' => 'r', 'ｓ' => 's', 'ｔ' => 't', 'ｕ' => 'u', 'ｖ' => 'v', 'ｗ' => 'w', 'ｘ' => 'x', 'ｙ' => 'y', 'ｚ' => 'z',
            '（' => '(', '）' => ')', '〔' => '[', '〕' => ']', '【' => '[', '】' => ']', '〖' => '[', '〗' => ']', '｛' => '{', '｝' => '}', '％' => '%', '＋' => '+', '—' => '-', '－' => '-', '～' => '~', '：' => ':', '？' => '?', '！' => '!', '‖' => '|', '　' => ' ',
            '｜' => '|', '〃' => '"',

            // '”' => '&quot;', '“' => '&quot;',  '’' => '&acute;', '‘' => '&acute;',
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

        return (string) preg_replace([
            // XSS跨站脚本攻击
            // '/on([a-zA-Z0-9]+)([ ]*?=[ ]*?)(["\'])(.*?)(["\'])/si',
            '/on([a-zA-Z0-9 ]+)=([ a-zA-Z0-9_("\']+)(["\');]+)/si',

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
            '/<head.*?>(.*?)<\/head.*?>/si',
            '/<body.*?>(.*?)<\/body.*?>/si',                '/<(\/?body.*?)>/si',
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
