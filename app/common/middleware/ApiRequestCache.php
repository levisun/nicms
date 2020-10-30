<?php

/**
 *
 * 请求缓存
 *
 * @package   NICMS
 * @category  app\common\middleware
 * @author    失眠小枕头 [312630173@qq.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace app\common\middleware;

use Closure;
use think\Request;
use think\Response;
use think\middleware\CheckRequestCache;

class ApiRequestCache extends CheckRequestCache
{

    /**
     * 设置当前地址的请求缓存
     * @access public
     * @param  Request $request
     * @param  Closure $next
     * @param  mixed   $cache
     * @return Response
     */
    public function handle($request, Closure $next, $cache = null)
    {
        // 304缓存
        if ($ms = $request->server('HTTP_IF_MODIFIED_SINCE')) {
            if (strtotime($ms) > $request->server('REQUEST_TIME')) {
                return Response::create()->code(304);
            }
        }

        $response = $next($request);

        return $response;
    }
}
