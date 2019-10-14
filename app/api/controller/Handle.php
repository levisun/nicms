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

use app\common\controller\AsyncController;

class Handle extends AsyncController
{

    public function index()
    {
        if (empty($_POST)) {
            $this->error('错误请求', 40009);
        }

        $result = $this->validate('POST')->run();
        $this->openCache(false)->success($result['msg'], $result['data'], $result['code']);
    }
}
