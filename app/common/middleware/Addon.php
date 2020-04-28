<?php

/**
 *
 * 插件
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
use think\Request;
use think\Response;

class Addon
{

    /**
     *
     * @access public
     * @param  Request $request
     * @param  Closure $next
     * @param  mixed   $cache
     * @return Response
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        return $response;
    }
}
