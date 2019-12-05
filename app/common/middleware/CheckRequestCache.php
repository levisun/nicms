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
use think\Request;
use think\Response;

class CheckRequestCache
{

    /**
     * 设置当前地址的请求缓存
     * 缓存为浏览器
     * @access public
     * @param  Request $request
     * @param  Closure $next
     * @return Response
     */
    public function handle(Request $request, Closure $next)
    {
        if ($request->isGet() && $ms = $request->server('HTTP_IF_MODIFIED_SINCE')) {
            // 返回浏览器缓存
            if (strtotime($ms) >= $request->server('REQUEST_TIME')) {
                return Response::create()->code(304);
            }
        }

        $key = md5($request->baseUrl());
        $time = $request->server('REQUEST_TIME') + 1440;
        if (false === app()->isDebug() && $content = Cache::get($key)) {
            $pattern = [
                '<meta name="csrf-authorization" content="" />' => '<meta name="csrf-authorization" content="' . create_authorization() . '" />',
                '<meta name="csrf-token" content="" />' => '<meta name="csrf-token" content="' . token() . '" />',
            ];
            $content = str_replace(array_keys($pattern), array_values($pattern), $content);
            return Response::create($content)
                ->allowCache(true)
                ->cacheControl('max-age=1440,must-revalidate')
                ->expires(gmdate('D, d M Y H:i:s', $time) . ' GMT')
                ->lastModified(gmdate('D, d M Y H:i:s', $time) . ' GMT')
                ->header(['X-Powered-By' => 'NICMS']);
        }

        $response = $next($request);

        // API有独立缓存定义,请勿开启缓存
        // API缓存在\app\common\controller\Async::result方法定义
        if ('api' !== app('http')->getName()) {
            // 调试模式关闭缓存
            $response->allowCache(!app()->isDebug());

            $response->header(array_merge(['X-Powered-By' => 'NICMS'], $response->getHeader()));
            if (200 == $response->getCode() && $request->isGet() && $response->isAllowCache()) {
                $response->allowCache(true)
                    ->cacheControl('max-age=1440,must-revalidate')
                    ->expires(gmdate('D, d M Y H:i:s', $time) . ' GMT')
                    ->lastModified(gmdate('D, d M Y H:i:s', $time) . ' GMT');

                $content = $response->getContent();
                $pattern = [
                    '/<meta name="csrf-authorization" content="(.*?)" \/>/si' => '<meta name="csrf-authorization" content="" />',
                    '/<meta name="csrf-token" content="(.*?)" \/>/si' => '<meta name="csrf-token" content="" />',
                ];
                $content = (string) preg_replace(array_keys($pattern), array_values($pattern), $content);
                $content .= '<!-- ' . date('Y-m-d H:i:s') . ' -->';
                Cache::tag('browser')->set($key, $content, mt_rand(28800, 29900));
            }
        }

        return $response;
    }
}
