<?php

/**
 *
 * 控制层
 * 访问记录API
 *
 * @package   NICMS
 * @category  app\api\controller
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace app\api\controller;

use app\common\controller\Async;
use app\common\library\AccessLog;

class Record extends Async
{

    public function index()
    {
        if ($this->isReferer(false)) {
            (new AccessLog)->record();
            return $this->cache(30)->success('record');
        }

        return miss(404);
    }
}
