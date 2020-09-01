<?php

/**
 *
 * 跨域中间件
 * CORS
 * API模块
 *
 * @package   NICMS
 * @category  app\middleware
 * @author    失眠小枕头 [312630173@qq.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace app\common\middleware;

use Closure;
use think\Config;
use think\Request;
use think\Response;
use think\Session;
use think\Log;

/**
 * 跨域请求支持
 */
class AllowCrossDomain
{
    protected $session;
    protected $log;

    protected $cookieDomain;

    protected $header = [
        'Access-Control-Allow-Credentials' => 'true',
        'Access-Control-Allow-Methods'     => 'GET, POST, PATCH, PUT, DELETE, OPTIONS',
        'Access-Control-Allow-Headers'     => 'Accept, Authorization, Content-Type, If-Match, If-Modified-Since, If-None-Match, If-Unmodified-Since, X-CSRF-TOKEN, X-Requested-With',
    ];

    public function __construct(Config $config, Session $session, Log $log)
    {
        $this->session = $session;
        $this->log = $log;

        $this->cookieDomain = $config->get('cookie.domain', '');
    }

    /**
     * 允许跨域请求
     * @access public
     * @param  Request $request
     * @param  Closure $next
     * @param  array   $header
     * @return Response
     */
    public function handle(Request $request, Closure $next, ?array $header = [])
    {
        $header = !empty($header) ? array_merge($this->header, $header) : $this->header;

        if (!isset($header['Access-Control-Allow-Origin'])) {
            $origin = $request->header('origin');

            if ($origin && ('' == $this->cookieDomain || strpos($origin, $this->cookieDomain))) {
                $header['Access-Control-Allow-Origin'] = $origin;
            } else {
                $header['Access-Control-Allow-Origin'] = '*';
            }
        }

        if ($request->method(true) == 'OPTIONS') {
            $header['Access-Control-Max-Age'] = 28800;
            return Response::create()->code(204)->header($header);
        }

        return $next($request)->header($header);
    }

    public function end(Response $response)
    {
        $this->session->save();
        $this->log->save();
    }
}
