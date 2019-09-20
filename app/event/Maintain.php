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

        $this->efficiency();
        $this->requestLog();

        // CMS请求
        if (!in_array($this->request->controller(true), ['api', 'admin'])) {
            // 生成访问日志
            (new Accesslog)->record();
            // 生成网站地图
            1 === mt_rand(1, 9) and (new Sitemap)->create();
        }
    }

    protected function efficiency(): void
    {
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

            $this->app->console->call('clear', ['cache']);

            $path = $this->app->getRuntimePath();
            is_file($path . 'route.php') and $this->app->console->call('clear', ['route']);

            (bool) glob($path . 'schema' . DIRECTORY_SEPARATOR . '*') and
                $this->app->console->call('clear', ['schema']);
        } else {
            // 生成效率文件
            $path = $this->app->getRuntimePath();
            is_file($path . 'route.php') or $this->app->console->call('optimize:route');

            (bool) glob($path . 'schema' . DIRECTORY_SEPARATOR . '*') or
                $this->app->console->call('optimize:schema');
        }
    }

    /**
     * 请求日志
     * @access protected
     * @param
     * @return void
     */
    protected function requestLog(): void
    {
        $pattern = '/dist|base64_decode|call_user_func|chown|eval|exec|passthru|phpinfo|proc_open|popen|shell_exec/si';
        if (0 !== preg_match($pattern, $this->request->url() . json_encode($this->request->param()))) {
            $this->logRecord('非法关键词');
        }

        $time = number_format(microtime(true) - $this->app->getBeginTime(), 3);
        if (10 <= $time) {
            $this->logRecord('超时请求:' . $time . 's');
        }

        1 === mt_rand(1, 9) and $this->logRecord('随机记录');
    }

    /**
     * 记录日志
     * @access protected
     * @param
     * @return void
     */
    protected function logRecord($_str): void
    {
        $this->app->log->record(
            '[' . $_str . ']' . $this->request->method(true) . ' ' . $this->request->ip() .
                PHP_EOL . $this->request->url(true) .
                PHP_EOL . json_encode($this->request->param()) .
                PHP_EOL,
            'info'
        );
    }
}
