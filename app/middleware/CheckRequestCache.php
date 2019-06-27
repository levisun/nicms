<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2019 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
declare (strict_types = 1);

namespace app\middleware;

use Closure;
use think\Config;
use think\Request;
use think\Response;

/**
 * 请求缓存处理
 */
class CheckRequestCache
{

    /**
     * 设置当前地址的请求缓存
     * @access public
     * @param  Request $request
     * @param  Closure $next
     * @return Response
     */
    public function handle(Request $request, Closure $next, Config $config)
    {
        if ($request->isGet() && false === $config->get('app.debug')) {
            if (strtotime($request->server('HTTP_IF_MODIFIED_SINCE', '0')) + 1440 > $request->server('REQUEST_TIME')) {
                // 读取缓存
                return Response::create()->code(304);
            }
        }

        $response = $next($request);

        return $response;
    }
}
