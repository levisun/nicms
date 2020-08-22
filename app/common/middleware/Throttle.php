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
     * 缓存标识
     * @var string
     */
    private $cache_key = '';

    /**
     * 最大访问次数
     * @var int
     */
    private $max_requests = 600;

    /**
     * 计时时间
     * @var array
     */
    private $duration = [
        's' => 1,
        'm' => 60,
        'h' => 3600,
        'd' => 86400,
    ];

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
        if (false !== filter_var($request->host(true), FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            miss(404, false, true);
        }

        $this->cache_key = app('http')->getName() . $request->ip();

        if ($this->hasLock()) {
            $this->abort();
        }

        // 最近一次请求
        $last_time = Cache::has($this->cache_key) ? (float) Cache::get($this->cache_key) : 0;

        // 平均 n 秒一个请求
        if ($request->time(true) - $last_time < $this->duration['m'] / $this->max_requests) {
            $this->setLock();
            $this->abort();
        }

        $response = $next($request);

        if (200 === $response->getCode()) {
            $last_time = Cache::has($this->cache_key) ? (float) Cache::get($this->cache_key) : $request->time(true);
            Cache::set($this->cache_key, $last_time, $this->duration['m']);
        }

        return $response;
    }

    private function hasLock(): bool
    {
        return Cache::has($this->cache_key . 'lock');
    }

    private function setLock()
    {
        Cache::set($this->cache_key . 'lock', date('Y-m-d H:i:s'), 28800);
    }

    private function abort()
    {
        $content = '<!DOCTYPE html><html lang="zh-cn"><head><meta charset="UTF-8"><meta name="robots" content="none" /><meta name="renderer" content="webkit" /><meta name="force-rendering" content="webkit" /><meta name="viewport"content="width=device-width,initial-scale=1,maximum-scale=1,minimum-scale=1,user-scalable=no" /><meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" /><title>请勿频繁操作</title><style type="text/css">*{padding:0;margin:0}body{background:#fff;font-family:"Century Gothic","Microsoft yahei";color:#333;font-size:18px}section{text-align:center;margin-top:50px}h2,h3{font-weight:normal;margin-bottom:12px;margin-right:12px;display:inline-block}</style></head><body><section><h2 class="miss">o(╥﹏╥)o 请勿频繁操作</h2></section></body></html>';
        throw new HttpResponseException(Response::create($content, 'html')->allowCache(true));
    }
}
