<?php

/**
 *
 * 控制层
 * 查询API
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

use app\api\logic\Async;

class Query extends Async
{

    public function index()
    {
        if ($this->request->isGet() && $result = $this->exec()) {
            // 请勿开启缓存
            // 如要开启缓存请在方法中单独定义
            return $this->response(
                $result['msg'],
                $result['data'],
                $result['code']
            );
        }

        return miss(404);
    }
}
