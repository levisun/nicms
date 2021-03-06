<?php

/**
 *
 * 请求日志
 *
 * @package   NICMS
 * @category  app\common\event
 * @author    失眠小枕头 [312630173@qq.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace app\common\event;

use think\facade\Log;
use think\facade\Request;
use app\common\model\Visit as ModelVisit;

class RequestLog
{
    private $engine = [
        'GOOGLE'         => 'googlebot',
        'GOOGLE ADSENSE' => 'mediapartners-google',
        'BAIDU'          => 'baiduspider',
        'MSN'            => 'msnbot',
        'YODAO'          => 'yodaobot',
        'YAHOO'          => 'yahoo! slurp;',
        'Yahoo China'    => 'yahoo! slurp china;',
        'IASK'           => 'iaskspider',
        'SOGOU WEB'      => 'sogou web spider',
        'SOGOU PUSH'     => 'sogou push spider',
        'YISOU'          => 'yisouspider',
    ];

    public function handle()
    {
        $this->spider();
        $this->api();
        $this->log();

        if (1 === mt_rand(1, 100)) {
            ModelVisit::where('date', '<', strtotime('-7 days'))->limit(10)->delete();
        }
    }

    /**
     * 搜索引擎蜘蛛日志
     * @access public
     * @return void
     */
    public function spider(): void
    {
        $user_agent = strtolower(Request::server('HTTP_USER_AGENT'));
        $spider = false;
        foreach ($this->engine as $key => $value) {
            if (0 !== preg_match('/(' . $value . ')/si', $user_agent)) {
                $spider = $key;
                continue;
            }
        }

        if ($spider) {
            $has = ModelVisit::where('name', '=', $spider)
                ->where('date', '=', strtotime(date('Y-m-d')))
                ->value('name');
            if ($has) {
                ModelVisit::where('name', '=', $spider)
                    ->where('date', '=', strtotime(date('Y-m-d')))
                    ->inc('count', 1)
                    ->limit(1)
                    ->update();
            } else {
                ModelVisit::create([
                    'name' => $spider,
                    'date' => strtotime(date('Y-m-d'))
                ]);
            }
        }
    }

    /**
     * API请求日志
     * @access public
     * @return void
     */
    public function api(): void
    {
        $app_name = app('http')->getName();
        if ($app_name && 'api' === $app_name) {
            $method = 'API:';
            $method .= Request::param('method') ?: ltrim(Request::baseUrl(), '/');

            $has = ModelVisit::where('name', '=', $method)
                ->where('date', '=', strtotime(date('Y-m-d')))
                ->value('name');
            if ($has) {
                ModelVisit::where('name', '=', $method)
                    ->where('date', '=', strtotime(date('Y-m-d')))
                    ->inc('count', 1)
                    ->limit(1)
                    ->update();
            } else {
                ModelVisit::create([
                    'name' => $method,
                    'date' => strtotime(date('Y-m-d'))
                ]);
            }
        }
    }

    /**
     * 请求日志
     * @access public
     * @return void
     */
    public function log(): void
    {
        // 请求参数
        $params = Request::param()
            ? Request::except(['password', 'sign', '__token__', 'timestamp', 'sign_type', 'appid'])
            : [];
        $params = array_filter($params);
        $params = !empty($params) ? json_encode($params, JSON_UNESCAPED_UNICODE) : '';

        // 请求时间
        $run_time = number_format(microtime(true) - Request::time(true), 3);

        // 日志
        $log = Request::ip() . ' ' . Request::method(true) . ' ' . $run_time . 's, ' .
            number_format((memory_get_usage() - app()->getBeginMem()) / 1048576, 3) . 'mb, ' .
            count(get_included_files()) . PHP_EOL .
            'user agent ' . Request::server('HTTP_USER_AGENT') . PHP_EOL .
            'request ' . Request::url(true) . PHP_EOL .
            (Request::server('HTTP_REFERER') ? 'referer ' . Request::server('HTTP_REFERER') . PHP_EOL : '') .
            ($params ? 'params ' . trim(htmlspecialchars($params)) . PHP_EOL : '');

        $pattern = [
            // '__',
            'apache_setenv',
            'base64_decode',
            'call_user_func',
            'call_user_func_array',
            'chgrp',
            'chown',
            'chroot',
            // 'dl',
            'eval',
            'exec',
            'file_get_contents',
            'file_put_contents',
            'function',
            'imap_open',
            'ini_alter',
            'ini_restore',
            'invoke',
            'openlog',
            'passthru',
            'pcntl_alarm',
            'pcntl_exec',
            'pcntl_fork',
            'pcntl_get_last_error',
            'pcntl_getpriority',
            'pcntl_setpriority',
            'pcntl_signal',
            'pcntl_signal_dispatch',
            'pcntl_sigprocmask',
            'pcntl_sigtimedwait',
            'pcntl_sigwaitinfo',
            'pcntl_strerror',
            'pcntl_wait',
            'pcntl_waitpid',
            'pcntl_wexitstatus',
            'pcntl_wifcontinued',
            'pcntl_wifexited',
            'pcntl_wifsignaled',
            'pcntl_wifstopped',
            'pcntl_wstopsig',
            'pcntl_wtermsig',
            // 'php',
            'popen',
            'popepassthru',
            'proc_open',
            'putenv',
            'readlink',
            'shell_exec',
            'symlink',
            'syslog',
            'system',
        ];
        $pattern = '/' . implode('|', $pattern) . '/si';
        if (0 !== preg_match($pattern, $params)) {
            Log::warning('非法' . $log);
        } elseif (60 <= $run_time) {
            Log::alert('长' . $log);
        } else {
            // Log::info($log);
        }
    }
}
