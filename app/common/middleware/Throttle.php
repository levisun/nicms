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
        // IP进入显示空页面
        if ($request->isValidIP($request->host(true), 'ipv4') || $request->isValidIP($request->host(true), 'ipv6')) {
            miss(404, false, true);
        }

        if (Cache::has($request->ip() . 'lock')) {
            $this->abort(Cache::get($request->ip() . 'lock'));
        }

        if (Cache::has($request->domain() . $request->ip() . 'login_lock')) {
            $this->abort('login lock');
        }

        $response = $next($request);

        $this->checkAnHourIpTotal($request, $response);
        $this->checkAnMinuteUserTotal($request, $response);

        return $response;
    }

    /**
     * 校验用户一分钟访问量
     * @access private
     * @param  Request  $_request
     * @param  Response $_response
     * @return void
     */
    private function checkAnMinuteUserTotal(Request $_request, Response $_response)
    {
        if (200 !== $_response->getCode()) {
            return;
        }
        $last_time = $_request->time(true);

        $cache_key = 'an minute total' . $_request->ip() . $_request->ext();
        if (Cache::has($cache_key)) {
            $last_time = (float) Cache::get($cache_key);
        } else {
            Cache::set($cache_key, $last_time, 60);
        }

        // 平均 n 秒一个请求
        $last_time = round($_request->time(true) - $last_time, 3);
        $rate = round(60 / 1200, 3);
        if (0 < $last_time && $last_time < $rate) {
            trace('lock' . $_request->ip() . ' ' . date('Y-m-d H:i:s') . ' ' . $last_time . '<' . $rate);
            Cache::set($_request->ip() . 'lock', date('Y-m-d H:i:s'), 1440);
        }
    }

    /**
     * 校验IP一小时访问量
     * @access private
     * @param  Request  $_request
     * @param  Response $_response
     * @return void
     */
    private function checkAnHourIpTotal(Request $_request, Response $_response): void
    {
        if (200 !== $_response->getCode()) {
            return;
        }

        // 记录IP一小时访问总量
        $cache_key = 'an hour ip total' . $_request->ip();
        $total = 0;
        if (Cache::has($cache_key . 'last time') && Cache::has($cache_key)) {
            $total = (int) Cache::get($cache_key);
            Cache::inc($cache_key);
        } else {
            Cache::set($cache_key . 'last time', date('Y-m-d H:i:s'), 3600);
            Cache::set($cache_key, 1, 86400);
        }

        // IP一小时访问超过一定数量抛出
        if (1000 <= $total) {
            Cache::set($_request->ip() . 'lock', 'IP:' . date('Y-m-d H:i:s'), 28800);
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
        $content = '<!DOCTYPE html><html lang="zh-cn"><head><meta charset="UTF-8" /><meta name="robots" content="none" /><meta name="renderer" content="webkit" /><meta name="force-rendering" content="webkit" /><meta name="viewport"content="width=device-width,initial-scale=1,maximum-scale=1,minimum-scale=1,user-scalable=no" /><meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" /><title>请勿频繁操作</title><style type="text/css">*{padding:0;margin:0}body{background:#fff;font-family:"Century Gothic","Microsoft yahei";color:#333;font-size:18px}section{text-align:center;margin-top:50px}h2,h3{font-weight:normal;margin-bottom:12px;margin-right:12px;display:inline-block}</style></head><body><section><h2 class="miss">o(╥﹏╥)o 请勿频繁操作</h2>' . $_msg . '<p>' . date('Y-m-d H:i:s') . '</p></section></body></html>';
        throw new HttpResponseException(Response::create($content, 'html')->allowCache(true));
    }
}
