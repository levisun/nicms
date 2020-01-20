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
use think\facade\Cache;
use think\facade\Config;
use think\facade\Cookie;
use think\facade\Lang;
use think\facade\Session;
use think\Request;
use think\Response;
use app\common\library\Base64;

class CheckRequestCache
{
    private $appName = '';
    private $key = '';

    /**
     * 设置当前地址的请求缓存
     * @access public
     * @param  Request $request
     * @param  Closure $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 获得应用名
        $this->appName = app('http')->getName();

        if ($this->appName && 'api' !== $this->appName) {
            // 生成客户端cookie令牌
            Session::has('client_id') or Session::set('client_id', Base64::client_id());
            Cookie::has('SID') or Cookie::set('SID', Session::get('client_id'));
        }

        // 缓存KEY
        $this->key = $this->appName . Lang::getLangSet() . $request->url(true);

        // 返回缓存
        if ($response = $this->readCache($request)) {
            return $response;
        }

        $response = $next($request);

        $response = $this->writeCache($response, $request);

        return $response;
    }

    /**
     * 读取缓存
     * @access private
     * @param  Request  $_request
     * @return false|Response
     */
    private function readCache(Request &$_request)
    {
        // 校验admin与user的权限
        if (in_array($this->appName, ['admin', 'user']) && !Session::has($this->appName . '_auth_key')) {
            return false;
        }

        if (Cache::has($this->key)) {
            $data = Cache::get($this->key);

            if ('api' === $this->appName) {
                $response = Response::create($data['content']);
                $response->header(array_merge(
                    $data['header'],
                    ['X-Powered-By' => 'NI_F_CACHE' . count(get_included_files())]
                ));
            } else {
                $pattern = [
                    '<meta name="csrf-authorization" content="" />' => authorization_meta(),
                    '<meta name="csrf-token" content="" />' => token_meta(),
                ];
                $data['content'] = str_replace(array_keys($pattern), array_values($pattern), $data['content']);
                $response = Response::create($data['content']);
                $response->header(['X-Powered-By' => 'NI_F_CACHE' . count(get_included_files())]);
                $response->allowCache(true)
                    ->cacheControl('max-age=1440,must-revalidate')
                    ->expires(gmdate('D, d M Y H:i:s', $_request->time() + 1440) . ' GMT')
                    ->lastModified(gmdate('D, d M Y H:i:s', $_request->time() + 1440) . ' GMT');
            }

            return $response;
        }

        return false;
    }

    /**
     * 写入缓存
     * @access private
     * @param  Response $_response
     * @param  Request  $_request
     * @return Response
     */
    private function writeCache(Response &$_response, Request &$_request): Response
    {
        if (200 == $_response->getCode() && $_request->isGet() && false === app()->isDebug()) {
            // 获得缓存时间
            $expire = Config::get('cache.stores.' . Config::get('cache.default') . '.expire');

            // API应用判断执行方法是否进行缓存
            // 非API应用添加浏览器header信息,跨域签名替换
            if ('api' === $this->appName) {
                if ($expire = $_response->getHeader('Cache-control')) {
                    $expire = (int) str_replace(['max-age=', ',must-revalidate'], '', $expire);
                } else {
                    return $_response;
                }
            } else {
                $_response->allowCache(true)
                    ->cacheControl('max-age=1440,must-revalidate')
                    ->expires(gmdate('D, d M Y H:i:s', $_request->time() + 1440) . ' GMT')
                    ->lastModified(gmdate('D, d M Y H:i:s', $_request->time() + 1440) . ' GMT');

                $pattern = [
                    '/<meta name="csrf-authorization" content=".*?" \/>/si' => '<meta name="csrf-authorization" content="" />',
                    '/<meta name="csrf-token" content=".*?">/si' => '<meta name="csrf-token" content="" />',
                ];
                $content = (string) preg_replace(array_keys($pattern), array_values($pattern), $_response->getContent());
            }

            Cache::tag('request')->set($this->key, [
                'content' => isset($content) ? $content : $_response->getContent(),
                'header' => $_response->getHeader()
            ], $expire - 10);
        }

        return $_response;
    }
}
