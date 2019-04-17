<?php
/**
 *
 * API接口层
 * 数据库备份
 *
 * @package   NICMS
 * @category  app\logic\admin\extend
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */
declare (strict_types = 1);

namespace app\logic\admin\extend;

use think\facade\Config;
use think\facade\Lang;
use think\facade\Request;
use app\logic\admin\Base;

class Databack extends Base
{

    public function query()
    {
        $file = (array) glob(app()->getRuntimePath() . 'backup' . DIRECTORY_SEPARATOR . '*');
    }
}
