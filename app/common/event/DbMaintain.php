<?php

/**
 *
 * 应用维护
 * 清除应用垃圾
 * 数据库维护
 *
 * @package   NICMS
 * @category  app\common\event
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace app\common\event;

use app\common\library\DataManage;
use app\common\library\Sitemap;

class DbMaintain
{

    public function handle()
    {
        // 数据库优化|修复
        (new DataManage)->optimize();
        // 数据库备份
        (new DataManage)->autoBackup();

        (new Sitemap)->create();
    }
}
