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

class Download extends Async
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
        if ($this->request->isGet() && $file = $this->request->param('file', false)) {
            (new \app\common\library\Download)->file($file);
        } else {
            echo '错误请求';
            exit();
        }
    }
}
