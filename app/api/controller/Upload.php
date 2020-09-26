<?php

/**
 *
 * 控制层
 * 文件上传API
 *
 * @package   NICMS
 * @category  app\api\controller
 * @author    失眠小枕头 [312630173@qq.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace app\api\controller;

use app\common\library\api\Async;

class Upload extends Async
{

    public function index()
    {
        if ($result = $this->run()) {
            // 请勿开启缓存
            return $this->cache(false)->response(
                $result['msg'],
                $result['data'],
                $result['code']
            );
        }

        return miss(404, false);
    }
}
