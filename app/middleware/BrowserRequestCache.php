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
use think\Cache;
use think\Response;
use think\facade\Config;

class BrowserRequestCache
{
    public function __construct(Cache $cache)
    {
        $this->cache = $cache;
        $this->cache->tag('RequestCache');
    }

    public function handle($request, Closure $next)
    {
        if ($request->isGet()) {

        }

        $key = sha1($request->baseFile(true));


        if ($this->cache->has($key) && $if_modified_since = $request->server('HTTP_IF_MODIFIED_SINCE')) {
            list($content, $header) = $this->cache->get($key);
            $expire = (int)str_replace('public, max-age=', '', $header['Cache-control']);
            if (strtotime($if_modified_since) + $expire >= $request->server('REQUEST_TIME')) {
                return Response::create()->code(304);
            }
        } elseif ($this->cache->has($key)) {
            list($content, $header) = $this->cache->get($key);
            return Response::create($content)->header($header);
        }

        $response = $next($request);

        if (200 == $response->getCode() && $response->isAllowCache()) {
            $expire = (int)Config::get('cache.expire');
            if (!$response->getHeader('Expires')) {
                $response->expires(gmdate('D, d M Y H:i:s', time() + $expire) . ' GMT');
            }
            if (!$response->getHeader('Last-Modified')) {
                $response->lastModified(gmdate('D, d M Y H:i:s') . ' GMT');
            }
            if (!$response->getHeader('Cache-control')) {
                $response->cacheControl('public, max-age=10');
            } else {
                $expire = (int)str_replace('public, max-age=', '', $response->getHeader('Cache-control'));
            }

            $this->cache->set($key, [$response->getContent(), $response->getHeader()], $expire);
        }

        // if (false === Config::get('app.app_debug') && $request->isGet() && 'api' !== $request->subDomain()) {
        //     $expire = (int)Config::get('cache.expire');
        //     $response->allowCache(true)
        //         ->cacheControl('public, max-age=' . $expire)
        //         ->expires(gmdate('D, d M Y H:i:s', time() + $expire) . ' GMT')
        //         ->lastModified(gmdate('D, d M Y H:i:s') . ' GMT');
        // }

        return $response;
    }
}
