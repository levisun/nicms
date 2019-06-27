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
declare (strict_types = 1);

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
     * Config实例
     * @var \think\Config
     */
    protected $config;

    /**
     * Env实例
     * @var \think\Env
     */
    protected $env;

    /**
     * request实例
     * @var \think\Request
     */
    protected $request;

    public function handle(App $_app)
    {
        $this->app     = $_app;
        $this->cache   = $this->app->cache;
        $this->config  = $this->app->config;
        $this->env     = $this->app->env;
        $this->request = $this->app->request;

        if (!in_array($this->request->controller(true), [$this->env->get('admin.entry'), 'api'])) {
            (new Accesslog)->record();      // 生成访问日志
            (new Sitemap)->create();        // 生成网站地图
        }

        if (!in_array($this->request->controller(true), ['api'])) {
            // 优化修复数据库表
            if (0 === date('Ymd') % 10) {
                $lock = app()->getRuntimePath() . 'lock' . DIRECTORY_SEPARATOR . 'dmor.lock';
                if (!is_file($lock)) {
                    file_put_contents($lock, date('Y-m-d H:i:s'));
                }
                $date = (string)(date('Ymd') - 10);
                clearstatcache();
                if (filemtime($lock) <= strtotime($date)) {
                    if ($fp = @fopen($lock, 'w+')) {
                        if (flock($fp, LOCK_EX | LOCK_NB)) {
                            ignore_user_abort(true);
                            (new DataMaintenance)->optimize();  // 优化表
                            (new DataMaintenance)->repair();    // 修复表
                            ignore_user_abort(false);

                            fwrite($fp, '优化|修复数据' . date('Y-m-d H:i:s'));
                            flock($fp, LOCK_UN);
                        }
                        fclose($fp);
                    }
                }
            }
            // 清除过期缓存和日志等
            elseif (1 === rand(1, 299)) {
                (new ReGarbage)->run();
            }
            // 自动备份数据库
            elseif (1 === rand(1, 299)) {
                (new DataMaintenance)->autoBackup();
            }
        }

        // 开启调试清空请求缓存
        if (true === $this->config->get('app.debug')) {
            // $this->cache->clear();
        }
    }
}
