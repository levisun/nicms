<?php

/**
 *
 * Emoji
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

class Emoji
{

    /**
     * 原形转换为String
     * @access public
     * @param  string $_str
     * @return string
     */
    public function encode($_str): string
    {
        return json_decode(preg_replace_callback('/(\\\u[ed][0-9a-f]{3})/si', function ($matches) {
            return '[EMOJI:' . base64_encode($matches[0]) . ']';
        }, json_encode($_str)));
    }

    /**
     * 字符串转换为原形
     * @access public
     * @param  string $_str
     * @return string
     */
    public function decode($_str): string
    {
        return json_decode(preg_replace_callback('/(\[EMOJI:[A-Za-z0-9]{8}\])/', function ($matches) {
            return base64_decode(str_replace(['[EMOJI:', ']'], '', $matches[0]));
        }, json_encode($_str)));
    }

    /**
     * 字符串清清理
     * @access public
     * @param  string $_str
     * @return string
     */
    public function clear(string $_str): string
    {
        return preg_replace_callback('/./u', function (array $matches) {
            return strlen($matches[0]) >= 4 ? '' : $matches[0];
        }, $_str);
    }
}
