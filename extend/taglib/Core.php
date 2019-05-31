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

class Core
{
    public static function script(array $_params): string
    {
        $type = isset($_params['type']) ? $_params['type'] : 'text/javascript';
        $_params['content'] = str_replace(
            [
                ' = ', ' == ', ' === ', ' + ', ': '
            ],
            [
                '=', '==', '===', '+', ':'
            ],
            $_params['content']
        );
        $_params['content'] .= '<!-- 1321321 -->/* 3213213  321321321
        321321321  321321
        */';
        return '<script type="' . $type . '">' . $_params['content'] . '</script>';
    }
}
