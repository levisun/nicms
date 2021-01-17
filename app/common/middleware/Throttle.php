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
        $lock_cache_key = $request->ip() . $request->server('HTTP_REFERER') . $request->domain() . 'lock';
        if (Cache::has($lock_cache_key)) {
            return miss('请勿频繁操作', false);
        }

        $response = $next($request);

        if (200 === $response->getCode()) {
            $cache_key = 'an minute total' . $request->ip();

            if (Cache::has($cache_key)) {
                Cache::inc($cache_key);
            } else {
                Cache::set($cache_key, 1, 10);
            }

            if (60 <= Cache::get($cache_key)) {
                $log = 'lock IP:' . $request->ip() . PHP_EOL;
                $log .= $request->server('HTTP_REFERER') ?: $request->url(true);
                trace($log, 'warning');
                Cache::tag('request')->set($lock_cache_key, 'UR', 1440);
            }
        }

        return $response;
    }
}
