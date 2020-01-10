<?php

/**
 *
 * 控制层
 * 跳转API
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

use think\Response;
use app\common\controller\Async;

class Go extends Async
{

    public function index()
    {
        if ($this->isReferer()) {
            if ($url = $this->request->param('url', false)) {
                return Response::create(base64_decode($url), 'redirect', 302);
            }
        }

        return miss(404);
    }
}
