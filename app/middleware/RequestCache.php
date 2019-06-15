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
declare (strict_types = 1);

namespace app\middleware;

use Closure;
use think\Cache;
use think\Response;
use think\facade\Config;

class RequestCache
{
    protected $cache;
    protected $request;

    public function __construct(Cache $_cache)
    {
        $this->cache = $_cache;
        $this->cache->tag('RequestCache');

        // 开启调试清空请求缓存
        if (true === Config::get('app.debug')) {
            $this->cache->tag('RequestCache')->clear();
        }
    }

    public function handle($_request, Closure $_next): Response
    {
        $this->request = $_request;

        if ($response = $this->readCache()) {
            $response = $this->gzip($response);
            return $response;
        }

        $response = $_next($this->request);

        $this->writeCache($response);

        $response = $this->gzip($response);

        return $response;
    }

    /**
     * 输出压缩
     * @access private
     * @param  Response $_response
     * @return Response
     */
    private function gzip($_response): Response
    {
        if ($this->request->isGet() && !headers_sent() && function_exists('gzencode')) {
            $content = $_response->getContent();
            $content = gzencode($content, 7, FORCE_GZIP);
            $_response->content($content);
            $_response->header([
                'Content-Encoding' => 'gzip',
                'Content-Length'   => strlen($content)
            ]);
        }

        return $_response;
    }

    /**
     * 读取请求缓存
     * @access private
     * @param
     * @return Response|bool
     */
    private function readCache()
    {
        $key = $this->getCacheKey();
        if (false !== $key && $this->cache->has($key)) {
            list($content, $header) = $this->cache->get($key);
            if ($if_modified_since = $this->request->server('HTTP_IF_MODIFIED_SINCE')) {
                $expire = (int)str_replace('public, max-age=', '', $header['Cache-control']);
                if (strtotime($if_modified_since) + $expire >= $this->request->server('REQUEST_TIME')) {
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
     * @param  Response $_response
     * @return void
     */
    private function writeCache($_response): void
    {
        $key = $this->getCacheKey();
        if (false !== $key && 200 == $_response->getCode() && $_response->isAllowCache()) {
            if ($_response->getHeader('Cache-control')) {
                $expire = (int)str_replace('public, max-age=', '', $_response->getHeader('Cache-control'));
            } else {
                $expire = Config::get('cache.expire');
                $_response->cacheControl('public, max-age=' . $expire)
                    ->expires(gmdate('D, d M Y H:i:s', time() + $expire) . ' GMT')
                    ->lastModified(gmdate('D, d M Y H:i:s') . ' GMT');
            }

            if (isset($expire)) {
                $this->cache->set($key, [$_response->getContent(), $_response->getHeader()], $expire);
            }
        }
    }

    /**
     * 缓存KEY
     * @access private
     * @param
     * @return bool|string
     */
    private function getCacheKey()
    {
        if ($this->request->isGet() && false === Config::get('app.debug')) {
            $key  = $this->request->ip();
            $key .= client_mac();
            $key .= $this->request->cookie(Config::get('session.name'));
            $key .= $this->request->server('HTTP_USER_AGENT');
            $key .= preg_replace_callback('/timestamp=[0-9]+|sign=[A-Za-z0-9]{32,40}/si', function () {
                return '*';
            }, $this->request->url(true));
            // \think\facade\Log::record($key, 'alert')->save();
            return md5($key);
        } else {
            return false;
        }
    }
}
