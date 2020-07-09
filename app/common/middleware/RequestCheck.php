<?php

/**
 *
 * 请求合法校验
 *
 * @package   NICMS
 * @category  app\common\middleware
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2020
 */

declare(strict_types=1);

namespace app\common\middleware;

use Closure;
use think\Request;

class RequestCheck
{

    /**
     *
     * @access public
     * @param  Request $request
     * @param  Closure $next
     * @return Response
     */
    public function handle(Request $request, Closure $next)
    {
        // index.[html|php]等进入重定向到根域名
        if (0 === stripos($request->baseUrl(), '/index.')) {
            return redirect('/');
        }

        // IP进入显示空页面
        if (false !== filter_var($request->subDomain() . '.' . $request->rootDomain(), FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            miss(404, false, true);
        }

        $response = $next($request);

        return $response;
    }
}
