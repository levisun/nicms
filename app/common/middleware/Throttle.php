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
            // return miss('请勿频繁操作', false);
        }

        $response = $next($request);

        if (200 === $response->getCode()) {
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
}
