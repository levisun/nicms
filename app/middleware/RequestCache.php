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
        if ($response = $this->readCache($request)) {
            return $response;
        }

        $response = $next($request);

        $this->writeCache($request, $response);

        return $response;
    }

    /**
     * 读取请求缓存
     * @access private
     * @param  Request  $request
     * @return response|bool
     */
    private function readCache($request)
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

        return false;
    }

    /**
     * 记录请求缓存
     * @access private
     * @param  Request  $request
     * @param  Response $response
     * @return void
     */
    private function writeCache($request, $response): void
    {
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
    }

    /**
     * 缓存KEY
     * @access private
     * @param  Request $request
     * @return string
     */
    private function cacheKey($request)
    {
        $key = preg_replace_callback('/timestamp=[0-9]+|sign=[A-Za-z0-9]{32,40}/si', function ($matches) {
            return '';
        }, $request->url(true));

        return sha1($key);
    }
}
