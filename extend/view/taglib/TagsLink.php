<?php

declare(strict_types=1);

namespace view\taglib;

use view\Taglib;

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
