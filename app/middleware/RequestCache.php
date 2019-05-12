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
    protected $cache;

    public function __construct(Cache $cache)
    {
        $this->cache = $cache;
        $this->cache->tag('RequestCache');
    }

    public function handle($request, Closure $next)
    {
        $key = $this->cacheKey($request);
        if ($request->isGet() && $this->cache->has($key)) {
            list($content, $header) = $this->cache->get($key);
            if ($if_modified_since = $request->server('HTTP_IF_MODIFIED_SINCE')) {
                $expire = (int)str_replace('public, max-age=', '', $header['Cache-control']);
                if (strtotime($if_modified_since) + $expire >= $request->server('REQUEST_TIME')) {
                    return Response::create()->code(304);
                }
            } else {
                return Response::create($content)->header($header);
            }
        }

        $response = $next($request);

        if ($request->isGet() && 200 == $response->getCode() && $response->isAllowCache()) {
            if ($response->getHeader('Cache-control')) {
                $expire = (int)str_replace('public, max-age=', '', $response->getHeader('Cache-control'));
            } elseif (false === Config::get('app.debug')) {
                $expire = Config::get('cache.expire');
                $response->cacheControl('public, max-age=' . $expire)
                    ->expires(gmdate('D, d M Y H:i:s', time() + $expire) . ' GMT')
                    ->lastModified(gmdate('D, d M Y H:i:s') . ' GMT');
            }

            if (isset($expire)) {
                $key = $this->cacheKey($request);
                $this->cache->set($key, [$response->getContent(), $response->getHeader()], $expire);
            }
        }

        return $response;
    }

    /**
     * 缓存KEY
     * @access private
     * @param  Request $request
     * @return string
     */
    private function cacheKey($request)
    {
        $key = $request->url(true);

        $key = preg_replace_callback('/timestamp=[0-9]+|sign=[A-Za-z0-9]{32,40}/si', function ($matches) {
            return '';
        }, $key);

        return sha1($key);
    }
}
