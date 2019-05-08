<?php
/**
 *
 * 浏览器缓存
 *
 * @package   NICMS
 * @category  app\middleware
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */
declare (strict_types = 1);

namespace app\middleware;

use Closure;
use think\Response;
use think\facade\Config;

class BrowserRequestCache
{

    public function handle($request, Closure $next)
    {
        if (false === Config::get('app.app_debug') && $if_modified_since = $request->server('HTTP_IF_MODIFIED_SINCE')) {
            $expire = (int)Config::get('cache.expire');
            $expire -= 120;
            if (strtotime($if_modified_since) + $expire >= $request->server('REQUEST_TIME')) {
                return Response::create()->code(304);
            }
        }

        $response = $next($request);

        if (false === Config::get('app.app_debug') && $request->isGet() && 'api' !== $request->subDomain()) {
            $expire = (int)Config::get('cache.expire');
            $response->allowCache(true)
                ->cacheControl('public, max-age=' . $expire)
                ->expires(gmdate('D, d M Y H:i:s', time() + $expire) . ' GMT')
                ->lastModified(gmdate('D, d M Y H:i:s') . ' GMT');
        }

        return $response;
    }
}
