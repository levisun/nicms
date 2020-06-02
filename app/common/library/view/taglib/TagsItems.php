<?php

/**
 *
 * 栏目页列表标签
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

class TagsItems extends Taglib
{

    public function closed(): string
    {
        $parseStr  = '<?php $result = app(\'\app\cms\logic\article\Category\')->query();';
        $parseStr .= '$result = !empty($result[\'data\']) ? $result[\'data\'] : [];
            if (!empty($result)):
                $total = $result["total"];
                $per_page = $result["per_page"];
                $current_page = $result["current_page"];
                $last_page = $result["last_page"];
                $page = $result["page"];
                $items = $result["list"];
                foreach ($items as $key => $item): ?>';
        return $parseStr;
    }

    public function end(): string
    {
        return '<?php endforeach; endif; ?>';
    }
}
