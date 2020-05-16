<?php

declare(strict_types=1);

namespace view\taglib;

use view\Taglib;

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
