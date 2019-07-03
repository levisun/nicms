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
            (new Accesslog)->record();      // 生成访问日志
            (new Sitemap)->create();        // 生成网站地图
        }

        // 数据库优化|修复
        $this->DBOptimize();
        // 数据库备份
        $this->DBBackup();
        // 垃圾信息维护
        $this->garbage();

        // 开启调试清空请求缓存
        if ($this->app->isDebug()) {
            $this->app->cache->clear();
        }
    }

    /**
     * 数据库优化|修复
     * @access protected
     * @param
     * @return void
     */
    protected function DBOptimize(): void
    {
        if ('api' !== $this->request->controller(true) && 0 === strtotime(date('Ymd')) % 7) {
            $lock = app()->getRuntimePath() . 'db_op.lock';
            if (!is_file($lock)) {
                file_put_contents($lock, date('Y-m-d H:i:s'));
            }
            clearstatcache();
            if (filemtime($lock) <= strtotime((string) (date('Ymd') - 7))) {
                if ($fp = @fopen($lock, 'w+')) {
                    if (flock($fp, LOCK_EX | LOCK_NB)) {
                        ignore_user_abort(true);

                        (new DataMaintenance)->optimize();  // 优化表
                        (new DataMaintenance)->repair();    // 修复表

                        fwrite($fp, '优化|修复数据' . date('Y-m-d H:i:s'));
                        flock($fp, LOCK_UN);

                        ignore_user_abort(false);
                    }
                    fclose($fp);
                }
            }
        }
    }

    /**
     * 数据库备份
     * @access protected
     * @param
     * @return void
     */
    protected function DBBackup(): void
    {
        if ('api' !== $this->request->controller(true) && 1 === rand(1, 299)) {
            (new DataMaintenance)->autoBackup();
        }
    }

    /**
     * 垃圾信息维护
     * @access protected
     * @param
     * @return void
     */
    protected function garbage(): void
    {
        if ('api' !== $this->request->controller(true) && 1 === rand(1, 299)) {
            (new ReGarbage)->run();
        }
    }
}
