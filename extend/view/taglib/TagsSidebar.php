<?php

/**
 *
 * 侧导航标签
 *
 * @package   NICMS
 * @category  view\taglib
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace view\taglib;

use view\Taglib;

class TagsSidebar extends Taglib
{

    public function alone(): string
    {
        $parseStr  = '<?php $sidebar = app(\'\app\cms\logic\nav\Sidebar\')->query();';
        $parseStr .= '$sidebar = !is_null($sidebar[\'data\']) ? $sidebar[\'data\'] : []; ?>';
        return $parseStr;
    }
}
