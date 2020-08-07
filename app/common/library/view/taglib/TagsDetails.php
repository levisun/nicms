<?php

/**
 *
 * 内容页详情标签
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

class TagsDetails extends Taglib
{

    public function alone(): string
    {
        $parseStr  = '<?php $details = app(\'\app\cms\logic\article\Details\')->query();';
        $parseStr .= 'if (empty($details[\'data\'])): miss(404, true, true); endif;';
        $parseStr .= '$details = !empty($details[\'data\']) ? $details[\'data\'] : []; ?>';
        return $parseStr;
    }
}
