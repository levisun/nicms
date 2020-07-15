<?php

/**
 *
 * 访问限制
 *
 * @package   NICMS
 * @category  app\common\middleware
 * @author    失眠小枕头 [levisun.mail@gmail.com]
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
        $key = md5(app('http')->getName() . $request->ip());

        // 最近一次请求
        $last_time = Cache::has($key) ? (float) Cache::get($key) : 0;

        // 平均 n 秒一个请求
        $rate = (float) $this->duration['m'] / $this->max_requests;

        if ($request->time(true) - $last_time < $rate) {
            $content = '<!DOCTYPE html><html lang="zh-cn"><head><meta charset="UTF-8"><meta name="robots" content="none" /><meta name="renderer" content="webkit" /><meta name="force-rendering" content="webkit" /><meta name="viewport"content="width=device-width,initial-scale=1,maximum-scale=1,minimum-scale=1,user-scalable=no" /><meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" /><title>请勿频繁操作</title><style type="text/css">*{padding:0;margin:0}body{background:#fff;font-family:"Century Gothic","Microsoft yahei";color:#333;font-size:18px}section{text-align:center;margin-top:50px}h2,h3{font-weight:normal;margin-bottom:12px;margin-right:12px;display:inline-block}</style></head><body><section><h2 class="miss">o(╥﹏╥)o 请勿频繁操作</h2><p>' . $request->time(true) . ':' . $last_time . '::' . $rate . '</p></section></body></html>';
            throw new HttpResponseException(Response::create($content, 'html')->allowCache(false));
        }

        $response = $next($request);

        if ($response->getCode() !== 302) {
            $last_time = Cache::has($key) ? $last_time : $request->time(true);
            Cache::set($key, $last_time, $this->duration['m']);
        }

        return $response;
    }
}
