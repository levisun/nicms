<?php
/**
 *
 *
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

    public function handle($request, Closure $next, ?array $header = [])
    {
        if ('api' === $request->subDomain()) {
            $this->header['Access-Control-Allow-Origin'] = $request->server('HTTP_ORIGIN', '*');
            $header = !empty($header) ? array_merge($this->header, $header) : $this->header;

            if ($request->isOptions()) {
                $header['Access-Control-Max-Age'] = 1440;
                return Response::create()->code(204)->header($header);
            }
            return $next($request)->header($header);
        } else {
            return $next($request);
        }
    }
}
