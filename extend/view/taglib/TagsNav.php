<?php

declare(strict_types=1);

namespace view\taglib;

use view\Taglib;

class TagsNav extends Taglib
{

    public function closed(): string
    {
        $type = isset($this->params['type']) ? strtolower($this->params['type']) : 'main';

        switch ($type) {
            case 'breadcrumb':
                $type = 'Breadcrumb';
                break;

            case 'foot':
                $type = 'Foot';
                break;

            case 'other':
                $type = 'Other';
                break;

            case 'sidebar':
                $type = 'Sidebar';
                break;

            case 'top':
                $type = 'Top';
                break;

            default:
                $type = 'Main';
                break;
        }

        $tpl_var = '$__TAGS_' . strtoupper($type) . '_NAV_RESULT';
        $parseStr  = '<?php ' . $tpl_var . ' = app(\'\app\cms\logic\nav\\' . $type . '\')->query();';
        $parseStr .= 'if (!is_null(' . $tpl_var . '[\'data\'])):';
        $parseStr .= '' . $tpl_var . ' = ' . $tpl_var . '[\'data\'];';
        $parseStr .= 'foreach (' . $tpl_var . ' as $key => $nav): ?>';
        return $parseStr;
    }

    public function end()
    {
        return '<?php endforeach; endif; ?>';
    }
}
