<?php
/**
 *
 * 跨域中间件
 * API模块
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
use think\Response;

class AllowCrossDomain
{
    protected $header = [
        'Access-Control-Allow-Origin'  => '*',
        'Access-Control-Allow-Methods' => 'GET, POST, PATCH, PUT, DELETE',
        'Access-Control-Allow-Headers' => 'Accept, Authorization, Content-Type, If-Match, If-Modified-Since, If-None-Match, If-Unmodified-Since, X-Requested-With',
    ];

    public function handle($_request, Closure $_next, ?array $_header = []): Response
    {
        $this->header['Access-Control-Allow-Origin'] = $_request->server('HTTP_ORIGIN', '*');
        $_header = !empty($_header) ? array_merge($this->header, $_header) : $this->header;

        if ($_request->isOptions()) {
            $_header['Access-Control-Max-Age'] = 14400;
            return Response::create()->code(204)->header($_header);
        }

        $_header = !empty($_header) ? $_header : [];
        $response = $_next($_request)->header($_header);

        return $response;
    }
}
