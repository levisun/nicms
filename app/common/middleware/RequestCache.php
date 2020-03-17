<?php

/**
 *
 * 请求缓存
 *
 * @package   NICMS
 * @category  app\common\middleware
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace app\common\middleware;

use Closure;
use think\facade\Cookie;
use think\facade\Session;
use think\Cache;
use think\Config;
use think\Request;
use think\Response;
use app\common\library\Base64;

class RequestCache
{
    /**
     * 缓存对象
     * @var Cache
     */
    protected $cache;

    /**
     * 应用名
     * @var Cache
     */
    protected $appName = '';

    /**
     * 配置参数
     * @var array
     */
    protected $config = [
        // 请求缓存规则 true为自动规则
        'request_cache_key'    => true,
        // 请求缓存有效期
        'request_cache_expire' => null,
        // 全局请求缓存排除规则
        'request_cache_except' => [],
        // 请求缓存的Tag
        'request_cache_tag'    => '',
    ];

    public function __construct(Cache $cache, Config $config)
    {
        $this->cache  = $cache;
        $this->config = array_merge($this->config, $config->get('route'));

        $this->appName = app('http')->getName();

        // 生成客户端cookieID与sessionID
        if ($this->appName && 'api' !== $this->appName) {
            Session::has('client_id') or Session::set('client_id', Base64::client_id());
            Cookie::has('SID') or Cookie::set('SID', Session::get('client_id'));
        }
    }

    /**
     * 设置当前地址的请求缓存
     * @access public
     * @param Request $request
     * @param Closure $next
     * @param mixed   $cache
     * @return Response
     */
    public function handle(Request $request, Closure $next, $cache = null)
    {
        if ($request->isGet() && false !== $cache) {
            $cache = $cache ?: $this->getRequestCache($request);

            if ($cache) {
                if (is_array($cache)) {
                    list($key, $expire, $tag) = $cache;
                } else {
                    $key    = str_replace('|', '/', $request->url());
                    $expire = $cache;
                    $tag    = null;
                }

                if ($this->cache->has($key) && $hit = $this->cache->get($key)) {
                    list($content, $header, $when) = $hit;
                    if (null === $expire || $when + $expire > $request->server('REQUEST_TIME')) {
                        // 非API请求刷新签名等信息
                        if ('api' !== $this->appName) {
                            preg_match('/name="csrf-appid" content="([0-9]+)"/si', $content, $matches);
                            $app_id = (int) $matches[1];
                            // halt($content);
                            $pattern = [
                                '<meta name="csrf-appsecret" content="" />' => app_secret($app_id),
                                '<meta name="csrf-authorization" content="" />' => authorization_meta(),
                                '<meta name="csrf-token" content="" />' => token_meta(),
                            ];
                            $content = str_replace(array_keys($pattern), array_values($pattern), $content);
                        }

                        return Response::create($content)->header($header);
                    }
                }
            }
        }

        $response = $next($request);

        // API应用不进行请求缓存
        // 因业务不同缓存的开启和时长由方法中定义
        if ('api' === $this->appName) {
            // 获得API执行方法设置的缓存时长
            if ($expire = $response->getHeader('Cache-control')) {
                $expire = (int) str_replace(['max-age=', ',must-revalidate'], '', $expire);
            } else {
                return $response;
            }
        }

        if (isset($key) && 200 == $response->getCode() && $response->isAllowCache()) {
            $header                  = $response->getHeader();
            $header['Cache-Control'] = 'max-age=' . $expire . ',must-revalidate';
            $header['Last-Modified'] = gmdate('D, d M Y H:i:s') . ' GMT';
            $header['Expires']       = gmdate('D, d M Y H:i:s', time() + $expire) . ' GMT';
            $header['X-Powered-By']  = 'NI_F_CACHE';
            $response->header($header);

            $content = $response->getContent();

            // 非API请求刷新签名等信息
            if ('api' !== $this->appName) {
                $pattern = [
                    '/<meta name="csrf-appsecret" content=".*?" \/>/si' => '<meta name="csrf-appsecret" content="" />',
                    '/<meta name="csrf-authorization" content=".*?" \/>/si' => '<meta name="csrf-authorization" content="" />',
                    '/<meta name="csrf-token" content=".*?">/si' => '<meta name="csrf-token" content="" />',
                ];
                $content = (string) preg_replace(array_keys($pattern), array_values($pattern), $content);
            }

            $this->cache->tag(['request', $tag])->set($key, [$content, $header, time()], 28800);
        }

        return $response;
    }

    /**
     * 读取当前地址的请求缓存信息
     * @access protected
     * @param Request $request
     * @return mixed
     */
    protected function getRequestCache(Request $request)
    {
        $key    = $this->config['request_cache_key'];
        $expire = $this->config['request_cache_expire'];
        $except = $this->config['request_cache_except'];
        $tag    = $this->config['request_cache_tag'] ?: $this->appName;

        if ($key instanceof \Closure) {
            $key = call_user_func($key, $request);
        }

        if (false === $key) {
            // 关闭当前缓存
            return;
        }

        foreach ($except as $rule) {
            if (0 === stripos($request->url(), $rule)) {
                return;
            }
        }

        if (true === $key) {
            // 自动缓存功能
            $key = '__URL__';
        } elseif (strpos($key, '|')) {
            [$key, $fun] = explode('|', $key);
        }

        // 特殊规则替换
        if (false !== strpos($key, '__')) {
            $key = str_replace(['__CONTROLLER__', '__ACTION__', '__URL__'], [$request->controller(), $request->action(), md5($request->url(true))], $key);
        }

        if (false !== strpos($key, ':')) {
            $param = $request->param();
            foreach ($param as $item => $val) {
                if (is_string($val) && false !== strpos($key, ':' . $item)) {
                    $key = str_replace(':' . $item, $val, $key);
                }
            }
        } elseif (strpos($key, ']')) {
            if ('[' . $request->ext() . ']' == $key) {
                // 缓存某个后缀的请求
                $key = md5($request->url());
            } else {
                return;
            }
        }

        if (isset($fun)) {
            $key = $fun($key);
        }

        return [$key, $expire, $tag];
    }
}
