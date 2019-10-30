<?php

/**
 *
 * 控制层
 * Api
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
use app\common\library\Download as Down;

class Download extends Async
{

    public function index()
    {
        if ($this->request->isGet() && $file = $this->request->param('file', false)) {
            (new Down)->file($file);
        } else {
            $this->error('错误请求', 40001);
        }
    }
}
