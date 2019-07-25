<?php

/**
 *
 * 维护事件
 * 清除过期缓存和日志等
 * 生成网站地图
 * 数据库维护
 *
 * @package   NICMS
 * @category  app\middleware
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace app\event;

use think\App;
use app\library\Accesslog;
use app\library\DataMaintenance;
use app\library\ReGarbage;
use app\library\Sitemap;

class Maintain
{
    /**
     * 应用实例
     * @var \think\App
     */
    protected $app;

    /**
     * Cache实例
     * @var \think\Cache
     */
    protected $cache;

    /**
     * request实例
     * @var \think\Request
     */
    protected $request;

    public function handle(App $_app)
    {
        $this->app     = $_app;
        $this->cache   = $this->app->cache;
        $this->request = $this->app->request;

        // CMS请求
        if ('cms' === $this->request->controller(true)) {
            // 生成访问日志
            (new Accesslog)->record();
            // 生成网站地图
            1 === mt_rand(1, 9) and (new Sitemap)->create();
        }

        if ('api' !== $this->request->controller(true) && 1 === mt_rand(1, 9)) {
            // 数据库优化|修复
            (new DataMaintenance)->autoOptimize();
            // 数据库备份
            (new DataMaintenance)->autoBackup();
            // 垃圾信息维护
            (new ReGarbage)->run();
        }

        if (true === $this->app->isDebug()) {
            // 开启调试清空请求缓存
            // $this->app->cache->clear();
        } else {
            // 生成效率文件
            \think\facade\Console::call('optimize:route');
            \think\facade\Console::call('optimize:schema');
        }
    }
}
