<?php

/**
 *
 * 访问限制
 *
 * @package   NICMS
 * @category  app\common\middleware
 * @author    失眠小枕头 [312630173@qq.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2020
 */

declare(strict_types=1);

namespace app\common\middleware;

use Closure;
use think\Request;
use think\facade\Cache;

class Throttle
{
    private $requestLock = '';
    private $requestInc = '';

    /**
     *
     * @access public
     * @param  Request $request
     * @param  Closure $next
     * @return Response
     */
    public function handle(Request $request, Closure $next)
    {
        $this->requestInc = __METHOD__ . $request->ip() . $request->domain();
        $this->requestLock = $this->requestInc . 'lock';

        if (Cache::has($this->requestLock)) {
            return miss('您的请求过于频繁已被拦截！', false);
        }

        if (!$request->rootDomain()) {
            $this->inc($request);
            return miss(404, false);
        }

        // IP进入显示空页面
        if ($request->isValidIP($request->host(true), 'ipv4') || $request->isValidIP($request->host(true), 'ipv6')) {
            $this->inc($request, 10);
            return miss(404, false);
        }

        if (false === stripos(config('app.app_host'), $request->rootDomain())) {
            $this->inc($request, 10);
            return miss(404, false);
        }

        if (!in_array($request->ext(), ['', 'do', config('route.url_html_suffix')])) {
            $this->inc($request, 10);
            return miss(404, false);
        }

        $response = $next($request);

        if (200 === $response->getCode()) {
            $this->inc($request);
        }

        return $response;
    }

    private function inc(Request $request, int $step = 1): void
    {
        if (Cache::has($this->requestInc)) {
            Cache::inc($this->requestInc, $step);
        } else {
            Cache::set($this->requestInc, $step, 10);
        }

        if (50 <= Cache::get($this->requestInc)) {
            $log = 'lock IP:' . $request->ip() . PHP_EOL;
            $log .= $request->server('HTTP_REFERER') ?: $request->url(true);
            trace($log, 'warning');
            Cache::tag('request')->set($this->requestLock, 'UR', 1440);
        }
    }
}
