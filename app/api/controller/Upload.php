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

use app\api\controller\Async;

class Upload extends Async
{
    /**
     * 控制器中间件
     * @var array
     */
    protected $middleware = [
        // 全局请求缓存
        // \app\common\middleware\CheckRequestCache::class,
    ];

    public function index()
    {
        if (empty($_FILES)) {
            $this->error('错误请求', 40009);
        }

        $result = $this->validate('post', true)->run();
        $this->openCache(false)->success($result['msg'], $result['data'], $result['code']);
    }
}
