<?php

/**
 *
 * 请求缓存
 *
 * @package   NICMS
 * @category  app\middleware
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace app\middleware;

use Closure;
use think\Config;
use think\Request;
use think\Response;

class CheckRequestCache
{

    /**
     * 设置当前地址的请求缓存
     * @access public
     * @param  Request $request
     * @param  Closure $next
     * @param  Config  $config
     * @return Response
     */
    public function handle(Request $request, Closure $next, Config $config)
    {
        if ($request->isGet() && $ms = $request->server('HTTP_IF_MODIFIED_SINCE')) {
            if (strtotime($ms) + 28800 > $request->server('REQUEST_TIME')) {
                return Response::create()->code(304);   // 读取缓存
            }
        }

        $response = $next($request);

        // 调试模式关闭浏览器缓存
        // API有定义缓存,请勿开启缓存
        if (true === $config->get('app.debug')) {
            $response->allowCache(false);
        }

        $response->header(array_merge(['X-Powered-By' => 'NICMS'], $response->getHeader()));
        if (200 == $response->getCode() && $request->isGet() && $response->isAllowCache()) {
            $response->allowCache(true)
                ->cacheControl('max-age=28800,must-revalidate')
                ->expires(gmdate('D, d M Y H:i:s', time() + 28800) . ' GMT')
                ->lastModified(gmdate('D, d M Y H:i:s') . ' GMT');
        }

        return $response;
    }
}
