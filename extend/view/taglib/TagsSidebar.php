<?php

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
