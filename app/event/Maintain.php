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
            $this->app->console->call('clear', ['schema']);
        } else {
            // 生成效率文件
            $this->app->console->call('optimize:route');
            $this->app->console->call('optimize:schema');
        }

        $this->rError();
    }

    private function rError(): bool
    {
        if ('miss' !== $this->request->action(true)) {
            return true;
        }

        // 组装请求参数
        $params = array_merge($_GET, $_POST, $_FILES);
        $params = !empty($params) ? json_encode($params) : '';
        $params = $this->request->url() . $params;
        $this->app->log->record('错误访问:' . $params, 'info');

        // 请求关键词
        // $pattern = '/dist|upload|base64_decode|call_user_func|chown|eval|exec|passthru|phpinfo|proc_open|popen|shell_exec|system|php|select|update|delete|insert|create/si';
        // if (false !== preg_match_all($pattern, $params, $matches) && 0 === count($matches[0])) {
        //     return true;
        // }

        $log = app()->getRuntimePath() . 'temp' . DIRECTORY_SEPARATOR . md5($this->request->ip() . date('dH')) . '.php';
        if (!is_dir(dirname($log))) {
            mkdir(dirname($log), 0755, true);
        }

        $number = is_file($log) ? include $log : '';

        // 非阻塞模式并发
        if ($fp = @fopen($log, 'w+')) {
            if (flock($fp, LOCK_EX | LOCK_NB)) {
                $time = (int) date('dH');   // 以分钟统计请求量
                $number = !empty($number) ? (array) $number : [$time => 1];
                if (isset($number[$time]) && $number[$time] >= 9) {
                    file_put_contents($log . '.lock', date('Y-m-d H:i:s'));
                } else {
                    $number[$time] = isset($number[$time]) ? ++$number[$time] : 1;
                    $number = [$time => end($number)];
                    $data = '<?php /*' . $this->request->ip() . '::' . $this->request->subDomain() . '*/ return ' . var_export($number, true) . ';';
                    fwrite($fp, $data);
                }
                flock($fp, LOCK_UN);
            }
            fclose($fp);
        }

        return false;
    }
}
