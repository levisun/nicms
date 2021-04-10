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
use app\common\library\tools\Ipv4;
use app\common\model\Visit as ModelVisit;

class RequestLog
{
    private $userAgent = '';
    private $appName = '';
    private $url = '';

    /**
     *
     * @access public
     * @param  Request $request
     * @param  Closure $next
     * @return Response
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        if (200 === $response->getCode()) {
            $this->userAgent = strtolower($request->server('HTTP_USER_AGENT'));
            $this->appName = app('http')->getName();
            $this->url = $request->param('method') ?: ltrim($request->baseUrl(), '/');
            $this->ip($request);
            $this->api($request);
            $this->record($request);
            $this->spider();
        }

        return $response;
    }

    /**
     * IP日志
     * @access private
     * @return void
     */
    private function record(&$_request): void
    {
        $url = ltrim($_request->baseUrl(), '/');
        if (!in_array($this->appName, ['admin', 'api']) && $url) {
            $has = ModelVisit::where('name', '=', $url)
                ->where('date', '=', strtotime(date('Y-m-d')))
                ->value('name');
            if ($has) {
                ModelVisit::where('name', '=', $url)
                    ->where('date', '=', strtotime(date('Y-m-d')))
                    ->inc('count', 1)
                    ->limit(1)
                    ->update();
            } else {
                ModelVisit::create([
                    'name' => $url,
                    'date' => strtotime(date('Y-m-d'))
                ]);
            }
        }
    }

    /**
     * IP日志
     * @access private
     * @return void
     */
    private function ip(&$_request): void
    {
        if (!in_array($this->appName, ['admin', 'api'])) {
            $has = ModelVisit::where('ip', '=', $_request->ip())
                ->where('date', '=', strtotime(date('Y-m-d')))
                ->value('ip');
            if ($has) {
                ModelVisit::where('ip', '=', $_request->ip())
                    ->where('date', '=', strtotime(date('Y-m-d')))
                    ->inc('count', 1)
                    ->limit(1)
                    ->update();
            } else {
                $ip = (new Ipv4)->get($_request->ip());
                ModelVisit::create([
                    'ip'      => $_request->ip(),
                    'ip_attr' => isset($ip['country']) ? $ip['country'] .  $ip['region'] . $ip['city'] .  $ip['area'] : '',
                    'date'    => strtotime(date('Y-m-d'))
                ]);
            }
        }
    }

    /**
     * API请求日志
     * @access private
     * @return void
     */
    private function api(&$_request): void
    {
        $method = $_request->param('method') ?: ltrim($_request->baseUrl(), '/');
        if ('api' === $this->appName && $method) {
            $method = 'API:' . $method;
            $has = ModelVisit::where('name', '=', $method)
                ->where('date', '=', strtotime(date('Y-m-d')))
                ->value('name');
            if ($has) {
                ModelVisit::where('name', '=', $method)
                    ->where('date', '=', strtotime(date('Y-m-d')))
                    ->inc('count', 1)
                    ->limit(1)
                    ->update();
            } else {
                ModelVisit::create([
                    'name' => $method,
                    'date' => strtotime(date('Y-m-d'))
                ]);
            }
        }
    }

    /**
     * 搜索引擎蜘蛛日志
     * @access private
     * @return void
     */
    private function spider(): void
    {
        $engine = [
            'GOOGLE'         => 'googlebot',
            'GOOGLE ADSENSE' => 'mediapartners-google',
            'BAIDU'          => 'baiduspider',
            'MSN'            => 'msnbot',
            'YODAO'          => 'yodaobot',
            'YAHOO'          => 'yahoo! slurp;',
            'Yahoo China'    => 'yahoo! slurp china;',
            'IASK'           => 'iaskspider',
            'SOGOU WEB'      => 'sogou web spider',
            'SOGOU PUSH'     => 'sogou push spider',
            'YISOU'          => 'yisouspider',
        ];

        foreach ($engine as $spider => $value) {
            if (0 !== preg_match('/(' . $value . ')/si', $this->userAgent)) {
                $has = ModelVisit::where('name', '=', $spider)
                    ->where('date', '=', strtotime(date('Y-m-d')))
                    ->value('name');
                if ($has) {
                    ModelVisit::where('name', '=', $spider)
                        ->where('date', '=', strtotime(date('Y-m-d')))
                        ->inc('count', 1)
                        ->limit(1)
                        ->update();
                } else {
                    ModelVisit::create([
                        'name' => $spider,
                        'date' => strtotime(date('Y-m-d'))
                    ]);
                }
                continue;
            }
        }
    }
}
