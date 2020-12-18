<?php

/**
 *
 * 访问限制
 *
 * @package   NICMS
 * @category  app\common\middleware
 * @author    失眠小枕头 [312630173@qq.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2020
 */

declare(strict_types=1);

namespace app\common\middleware;

use Closure;
use think\Request;
use think\facade\Cache;

use think\Response;
use think\exception\HttpResponseException;

class Throttle
{

    /**
     *
     * @access public
     * @param  Request $request
     * @param  Closure $next
     * @return Response
     */
    public function handle(Request $request, Closure $next)
    {
        if (Cache::has($request->ip() . 'lock')) {
            $this->abort(Cache::get($request->ip() . 'lock'));
        }

        if (Cache::has($request->domain() . $request->ip() . 'login_lock')) {
            $this->abort('login lock');
        }

        $response = $next($request);

        if (200 === $response->getCode()) {
            $this->checkAnHourIpTotal($request);
            $this->checkAnMinuteUserTotal($request);
        }

        return $response;
    }

    /**
     * 校验用户一分钟访问量
     * @access private
     * @param  Request  $_request
     * @param  Response $_response
     * @return void
     */
    private function checkAnMinuteUserTotal(Request &$_request)
    {
        $cache_key = 'an minute total' . $_request->ip() . $_request->ext();

        $last_time = Cache::has($cache_key)
            ? (float) Cache::get($cache_key)
            : $_request->time(true);

        $last_time = round($_request->time(true) - $last_time, 2);
        $last_time = abs($last_time);

        // 平均 n 秒一个请求
        $rate = round(60 / 600, 3);
        if ($last_time && $last_time < $rate) {
            trace('lock UR:' . $_request->ip() . ' ' . date('Y-m-d H:i:s') . ' ' . $last_time . '<' . $rate);
            Cache::tag('request')->set($_request->ip() . 'lock', 'UR', 1440);
        }

        if (!Cache::has($cache_key)) {
            Cache::tag('request')->set($cache_key, $last_time, 60);
        }
    }

    /**
     * 校验IP一小时访问量
     * @access private
     * @param  Request  $_request
     * @param  Response $_response
     * @return void
     */
    private function checkAnHourIpTotal(Request &$_request): void
    {
        // 记录IP一小时访问总量
        $cache_key = 'an hour ip total' . $_request->ip();
        $total = 0;
        if (Cache::has($cache_key)) {
            $total = (int) Cache::get($cache_key);
            Cache::inc($cache_key);
        } else {
            Cache::tag('request')->set($cache_key, 1, 3600);
        }

        // IP一小时访问超过一定数量锁定
        if (1000 < $total) {
            trace('lock IR:' . $_request->ip() . ' ' . date('Y-m-d H:i:s'));
            Cache::tag('request')->set($_request->ip() . 'lock', 'IR', 1440);
        }
    }

    /**
     * 抛出页面
     * @access private
     * @param  string $_msg
     * @return void
     */
    private function abort(string $_msg = '')
    {
        $_msg = $_msg ? '<p>' . $_msg . '</p>' : '';
        $content = '<!DOCTYPE html><html lang="zh-cn"><head><meta charset="UTF-8" /><meta name="robots" content="none" /><meta name="renderer" content="webkit" /><meta name="force-rendering" content="webkit" /><meta name="viewport"content="width=device-width,initial-scale=1,maximum-scale=1,minimum-scale=1,user-scalable=no" /><meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" /><title>请勿频繁操作</title><style type="text/css">*{padding:0;margin:0}body{background:#fff;font-family:"Century Gothic","Microsoft yahei";color:#333;font-size:18px}section{text-align:center;margin-top:50px}h2,h3{font-weight:normal;margin-bottom:12px;margin-right:12px;display:inline-block}</style></head><body><section><h2 class="miss">o(╥﹏╥)o 请勿频繁操作</h2>' . $_msg . '</section></body></html>';
        throw new HttpResponseException(Response::create($content, 'html')->allowCache(true));
    }
}
