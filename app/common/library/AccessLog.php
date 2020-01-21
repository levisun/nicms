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
    private $searchengine = [
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
     * @return void
     */
    public function record(): void
    {
        $user_agent = strtolower(Request::server('HTTP_USER_AGENT'));

        $ip = (new Ipinfo)->get(Request::ip());
        $has = (new ModelVisit)->where([
            ['ip', '=', $ip['ip']],
            ['user_agent', '=', md5($user_agent)],
            ['date', '=', strtotime(date('Y-m-d'))]
        ])->value('ip');
        if ($has) {
            (new ModelVisit)->where([
                ['ip', '=', $ip['ip']],
                ['user_agent', '=', md5($user_agent)],
                ['date', '=', strtotime(date('Y-m-d'))]
            ])->inc('count', 1)->update();
        } else {
            (new ModelVisit)->save([
                'ip'         => $ip['ip'],
                'ip_attr'    => isset($ip['country']) ? $ip['country'] .  $ip['region'] . $ip['city'] .  $ip['area'] : '',
                'user_agent' => md5($user_agent),
                'date'       => strtotime(date('Y-m-d'))
            ]);
        }

        if (1 === mt_rand(1, 9)) {
            (new ModelVisit)
                ->where([
                    ['date', '<=', strtotime('-30 days')]
                ])
                ->limit(100)
                ->delete();
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
        foreach ($this->searchengine as $key => $value) {
            if (0 !== preg_match('/(' . $value . ')/si', $user_agent)) {
                $spider = $key;
                continue;
            }
        }

        if ($spider) {
            $has = (new ModelVisit)->where([
                ['name', '=', $spider],
                ['date', '=', strtotime(date('Y-m-d'))]
            ])->value('name');
            if ($has) {
                (new ModelVisit)->where([
                    ['name', '=', $spider],
                    ['date', '=', strtotime(date('Y-m-d'))]
                ])->inc('count', 1)->update();
            } else {
                (new ModelVisit)->save([
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
            $method = 'API: ' . pathinfo(Request::baseUrl(), PATHINFO_BASENAME);
            $has = (new ModelVisit)->where([
                ['name', '=', $method],
                ['date', '=', strtotime(date('Y-m-d'))]
            ])->value('name');
            if ($has) {
                (new ModelVisit)->where([
                    ['name', '=', $method],
                    ['date', '=', strtotime(date('Y-m-d'))]
                ])->inc('count', 1)->update();
            } else {
                (new ModelVisit)->save([
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
        // $run_time = number_format(microtime(true) - app()->getBeginTime(), 3);
        $run_time = number_format(microtime(true) - Request::time(true), 3);
        $run_memory = number_format((memory_get_usage() - app()->getBeginMem()) / 1048576, 3) . 'mb ';
        $load_total = count(get_included_files()) . ' ';
        $url = Request::ip() . ' ' . Request::method(true) . ' ' . Request::url(true);
        $params = Request::param()
            ? Request::except(['password', 'sign', '__token__', 'timestamp', 'sign_type', 'appid'])
            : [];
        $params = array_filter($params);
        $params = !empty($params) ? PHP_EOL . json_encode($params, JSON_UNESCAPED_UNICODE) : '';

        $log = '请求' . $run_time . 's, ' . $run_memory . $load_total . PHP_EOL;
        $log .= $url . PHP_EOL;
        $log .= Request::server('HTTP_REFERER') ? Request::server('HTTP_REFERER') . PHP_EOL : '';
        $log .= Request::server('HTTP_USER_AGENT') . PHP_EOL;
        $log .= $params ? trim(htmlspecialchars($params)) . PHP_EOL : '';

        $pattern = '/dist|base64_decode|call_user_func|chown|eval|exec|passthru|phpinfo|proc_open|popen|shell_exec|php/si';
        if (0 !== preg_match($pattern, $params)) {
            Log::warning('非法' . $log);
        } elseif (1 <= $run_time) {
            Log::warning('长' . $log);
        } else {
            // Log::info($log);
        }
    }
}
