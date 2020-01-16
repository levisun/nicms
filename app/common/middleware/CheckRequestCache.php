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
        $this->key = md5($this->appName . Lang::getLangSet() . $request->baseUrl(true));

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
     * @param  Request $_request
     * @return false|Response
     */
    private function readCache(Request &$_request)
    {
        $response = false;
        if (false === app()->isDebug() && $content = Cache::get($this->key)) {
            $pattern = [
                '<meta name="csrf-authorization" content="" />' => authorization_meta(),
                '<meta name="csrf-token" content="" />' => token_meta(),
            ];
            $content = str_replace(array_keys($pattern), array_values($pattern), $content);
            $response = Response::create($content);
            $response->header(array_merge(['X-Powered-By' => 'NI_F_CACHE'], $response->getHeader()));
            $response = $this->browserCache($response, $_request);
        }

        return $response;
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
        // API有独立缓存定义,请勿开启缓存
        if ($this->appName && 'api' !== $this->appName) {
            $_response->allowCache(!app()->isDebug());
            $_response->header(array_merge(['X-Powered-By' => 'NICMS'], $_response->getHeader()));

            if (200 == $_response->getCode() && $_request->isGet() && $_response->isAllowCache()) {
                $_response = $this->browserCache($_response, $_request);

                $content = $_response->getContent() . '<!-- ' . date('Y-m-d H:i:s') . ' -->';
                $pattern = [
                    '/<meta name="csrf-authorization" content="(.*?)" \/>/si' => '<meta name="csrf-authorization" content="" />',
                    '/<meta name="csrf-token" content="(.*?)">/si' => '<meta name="csrf-token" content="" />',
                ];
                $content = (string) preg_replace(array_keys($pattern), array_values($pattern), $content);

                Cache::tag('request')->set($this->key, $content);
            }
        }

        return $_response;
    }

    /**
     * 浏览器缓存信息
     * @access private
     * @param  Response $_response
     * @param  Request  $_request
     * @return Response
     */
    private function browserCache(Response &$_response, Request &$_request): Response
    {
        if ($this->appName && !in_array($this->appName, ['admin', 'api', 'user'])) {
            $_response->allowCache(true)
                ->cacheControl('max-age=1440,must-revalidate')
                ->expires(gmdate('D, d M Y H:i:s', $_request->time() + 1440) . ' GMT')
                ->lastModified(gmdate('D, d M Y H:i:s', $_request->time() + 1440) . ' GMT');
        }

        return $_response;
    }
}
