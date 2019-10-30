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
use think\facade\Config;
use think\Request;
use think\Response;

class CheckRequestCache
{

    /**
     * 设置当前地址的请求缓存
     * 缓存为浏览器
     * 安全原因不写文件缓存,文件缓存无法根据客户记录
     * @access public
     * @param  Request $request
     * @param  Closure $next
     * @return Response
     */
    public function handle(Request $request, Closure $next)
    {
        if ($request->isGet() && $ms = $request->server('HTTP_IF_MODIFIED_SINCE')) {
            if (strtotime($ms) >= $request->server('REQUEST_TIME')) {
                return Response::create()->code(304);   // 读取缓存
            }
        }

        $response = $next($request);

        // API有独立缓存定义,请勿开启缓存
        // API缓存在\app\common\controller\Async::result方法定义
        if ('api' !== app('http')->getName()) {
            // 调试模式关闭缓存
            $response->allowCache(!app()->isDebug());

            $response->header(array_merge(['X-Powered-By' => 'NICMS'], $response->getHeader()));
            if (200 == $response->getCode() && $request->isGet() && $response->isAllowCache()) {
                $time = time() + 1440;
                $response->allowCache(true)
                    ->cacheControl('max-age=1440,must-revalidate')
                    ->expires(gmdate('D, d M Y H:i:s', $time) . ' GMT')
                    ->lastModified(gmdate('D, d M Y H:i:s', $time) . ' GMT');
            }
        }

        return $response;
    }
}
