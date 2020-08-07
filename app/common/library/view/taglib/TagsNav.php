<?php

/**
 *
 * 导航标签
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
        $parseStr .= $tpl_var . ' = !empty(' . $tpl_var . '[\'data\']) ? ' . $tpl_var . '[\'data\'] : [];';
        $parseStr .= 'foreach (' . $tpl_var . ' as $key => $nav): ?>';
        return $parseStr;
    }

    public function end(): string
    {
        return '<?php endforeach; ?>';
    }
}
