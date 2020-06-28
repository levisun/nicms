<?php

/**
 *
 * 访问日志
 *
 * @package   NICMS
 * @category  app\common\library
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace app\common\library;

use think\facade\Log;
use think\facade\Request;
use app\common\library\Ipinfo;
use app\common\model\Visit as ModelVisit;

class AccessLog
{
    private static $engine = [
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

    /**
     * 记录访问日志
     * @access public
     * @static
     * @return void
     */
    public static function record(): void
    {
        $user_agent = strtolower(Request::server('HTTP_USER_AGENT'));

        $ip = Ipinfo::get(Request::ip());
        $has = ModelVisit::where([
            ['ip', '=', $ip['ip']],
            ['user_agent', '=', md5($user_agent)],
            ['date', '=', strtotime(date('Y-m-d'))]
        ])->value('ip');
        if ($has) {
            ModelVisit::where([
                ['ip', '=', $ip['ip']],
                ['user_agent', '=', md5($user_agent)],
                ['date', '=', strtotime(date('Y-m-d'))]
            ])->inc('count', 1)->update();
        } else {
            ModelVisit::create([
                'ip'         => $ip['ip'],
                'ip_attr'    => isset($ip['country']) ? $ip['country'] .  $ip['region'] . $ip['city'] .  $ip['area'] : '',
                'user_agent' => md5($user_agent),
                'date'       => strtotime(date('Y-m-d'))
            ]);
        }

        if (1 === mt_rand(1, 100)) {
            ModelVisit::where([
                ['date', '<', strtotime('-30 days')]
            ])
                ->limit(100)
                ->delete();
        }
    }

    /**
     * 搜索引擎蜘蛛日志
     * @access public
     * @static
     * @return void
     */
    public static function spider(): void
    {
        $user_agent = strtolower(Request::server('HTTP_USER_AGENT'));
        $spider = false;
        foreach (self::$engine as $key => $value) {
            if (0 !== preg_match('/(' . $value . ')/si', $user_agent)) {
                $spider = $key;
                continue;
            }
        }

        if ($spider) {
            $has = ModelVisit::where([
                ['name', '=', $spider],
                ['date', '=', strtotime(date('Y-m-d'))]
            ])->value('name');
            if ($has) {
                ModelVisit::where([
                    ['name', '=', $spider],
                    ['date', '=', strtotime(date('Y-m-d'))]
                ])->inc('count', 1)->update();
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
     * @static
     * @return void
     */
    public static function api(): void
    {
        $app_name = app('http')->getName();
        if ($app_name && 'api' === $app_name) {
            $method = 'API:';
            $method .= Request::param('method') ?: ltrim(Request::baseUrl(), '/');

            $has = ModelVisit::where([
                ['name', '=', $method],
                ['date', '=', strtotime(date('Y-m-d'))]
            ])->value('name');
            if ($has) {
                ModelVisit::where([
                    ['name', '=', $method],
                    ['date', '=', strtotime(date('Y-m-d'))]
                ])->inc('count', 1)->update();
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
     * @static
     * @return void
     */
    public static function log(): void
    {
        // 请求参数
        $params = Request::param()
            ? Request::except(['password', 'sign', '__token__', 'timestamp', 'sign_type', 'appid'])
            : [];
        $params = array_filter($params);
        $params = !empty($params) ? PHP_EOL . json_encode($params, JSON_UNESCAPED_UNICODE) : '';

        // 请求时间
        $run_time = number_format(microtime(true) - Request::time(true), 3);
        // 运行内存
        $run_memory = number_format((memory_get_usage() - app()->getBeginMem()) / 1048576, 3) . 'mb ';
        // 加载文件
        $load_total = count(get_included_files()) . ' ';
        // 请求来源
        $url = Request::ip() . ' ' . Request::method(true) . ' ' . Request::url(true);

        // 日志
        $log  = '请求' . $run_time . 's, ' . $run_memory . $load_total . PHP_EOL;
        $log .= $url . PHP_EOL;
        $log .= Request::server('HTTP_REFERER') ? Request::server('HTTP_REFERER') . PHP_EOL : '';
        $log .= Request::server('HTTP_USER_AGENT') . PHP_EOL;
        $log .= $params ? trim(htmlspecialchars($params)) . PHP_EOL : '';

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
        } elseif (3 <= $run_time) {
            Log::alert('长' . $log);
        } else {
            // Log::info($log);
        }
    }
}
