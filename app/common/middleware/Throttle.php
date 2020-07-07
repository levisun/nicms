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

class Throttle
{
    /**
     * 最大访问次数
     * @var int
     */
    private $max_requests = 50;

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

        Cache::has($key . 'lock') and miss(403, false, true);

        $last_time = Cache::get($key, 0);
        $rate = (float) $this->duration['m'] / $this->max_requests;
        if ($request->time() - $last_time < $rate) {
            // Cache::set($key . 'lock', 'true');
            miss(403, false, true);
        }

        $response = $next($request);

        $last_time = Cache::has($key) ? $last_time : $request->time();
        Cache::set($key, $last_time, $this->duration['m']);

        return $response;
    }
}
