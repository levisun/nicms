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

class RequestCache
{
    public function __construct(Cache $cache)
    {
        $this->cache = $cache;
        $this->cache->tag('RequestCache');
    }

    public function handle($request, Closure $next)
    {
        $key = sha1($request->url(true));
        if ($request->isGet() && $this->cache->has($key) && $if_modified_since = $request->server('HTTP_IF_MODIFIED_SINCE')) {
            list($content, $header) = $this->cache->get($key);
            $expire = (int)str_replace('public, max-age=', '', $header['Cache-control']);
            if (strtotime($if_modified_since) + $expire >= $request->server('REQUEST_TIME')) {
                return Response::create()->code(304);
            }
        } elseif ($request->isGet() && $this->cache->has($key)) {
            list($content, $header) = $this->cache->get($key);
            return Response::create($content)->header($header);
        }

        $response = $next($request);

        if ($request->isGet() && 200 == $response->getCode() && $response->isAllowCache()) {
            if (!$response->getHeader('Cache-control')) {
                $expire = 10;
                $response->cacheControl('public, max-age=' . $expire);
            } else {
                $expire = (int)str_replace('public, max-age=', '', $response->getHeader('Cache-control'));
            }

            if (!$response->getHeader('Expires')) {
                $response->expires(gmdate('D, d M Y H:i:s', time() + $expire) . ' GMT');
            }

            if (!$response->getHeader('Last-Modified')) {
                $response->lastModified(gmdate('D, d M Y H:i:s') . ' GMT');
            }
            
            $key = sha1($request->url(true));
            $this->cache->set($key, [$response->getContent(), $response->getHeader()], $expire);
        }

        return $response;
    }
}
