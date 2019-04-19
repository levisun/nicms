<?php
/**
 *
 * 模板标签
 *
 * @package   NICMS
 * @category  extend\taglib
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */
declare (strict_types = 1);

namespace taglib;

class Tags
{

    public static function script(string $_content): string
    {
        $_content = preg_replace([
            // '/(\/\/)(.*?)(\n|\r)/si',
            // '/(\n|\r|\f)+/si',
            '/( ){2,}/si'
        ], '', $_content);
        return $_content;
    }
}
