<?php

/**
 *
 * 面包屑标签
 *
 * @package   NICMS
 * @category  app\common\library\view\taglib
 * @author    失眠小枕头 [312630173@qq.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace app\common\library\view\taglib;

use app\common\library\view\Taglib;

class TagsBreadcrumb extends Taglib
{

    public function closed(): string
    {
        $parseStr  = '<?php $breadcrumb = app(\'\app\cms\logic\nav\Breadcrumb\')->query();';
        $parseStr .= '$breadcrumb = !empty($breadcrumb[\'data\']) ? $breadcrumb[\'data\'] : [];';
        $parseStr .= 'foreach ($breadcrumb as $key => $item): ?>';
        return $parseStr;
    }

    public function end(): string
    {
        return '<?php endforeach; ?>';
    }
}
