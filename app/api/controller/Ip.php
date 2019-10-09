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

class Ip extends Async
{
    /**
     * 控制器中间件
     * @var array
     */
    protected $middleware = [
        // 全局请求缓存
        \app\common\middleware\CheckRequestCache::class,
    ];

    public function index()
    {
        if ($ip = $this->request->param('ip', false)) {
            if (false !== filter_var($ip, FILTER_VALIDATE_IP)) {
                $this->validate();
                $ip = \app\common\library\Ip::info($ip);
                $this->openCache(true)->success('IP INFO', $ip);
            }
        } else {
            $this->error('错误请求', 40001);
        }
    }
}
