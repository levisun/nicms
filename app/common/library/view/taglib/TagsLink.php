<?php

/**
 *
 * 友情链接标签
 *
 * @package   NICMS
 * @category  app\common\library\view\taglib
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace app\common\library\view\taglib;

use app\common\library\view\Taglib;

class TagsLink extends Taglib
{

    public function closed(): string
    {
        $parseStr  = '<?php $link = app(\'\app\cms\logic\link\Catalog\')->query();';
        $parseStr .= '$link = !empty($link[\'data\'][\'list\']) ? $link[\'data\'][\'list\'] : [];';
        $parseStr .= 'foreach ($link as $key => $item): ?>';
        return $parseStr;
    }

    public function end(): string
    {
        return '<?php endforeach; ?>';
    }
}
