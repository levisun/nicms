<?php

/**
 *
 * 侧导航标签
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

class TagsSidebar extends Taglib
{

    public function alone(): string
    {
        $parseStr  = '<?php $sidebar = app(\'\app\cms\logic\nav\Sidebar\')->query();';
        $parseStr .= '$sidebar = !is_null($sidebar[\'data\']) ? $sidebar[\'data\'] : []; ?>';
        return $parseStr;
    }
}
