<?php

/**
 *
 * 检查请求缓存
 *
 * @package   NICMS
 * @category  app\common\event
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace app\common\event;

use think\facade\Config;
use think\facade\Log;
use think\facade\Request;
use think\Response;
use think\exception\HttpResponseException;

class CheckRequest
{

    public function handle()
    {
        // 频繁或非法请求将被锁定
        $this->lock();
        // IP进入显示空页面
        $this->ipRequest();
        // 304缓存
        $this->cache304();

        // if (1 === mt_rand(1, 999)) {
        //     Log::write('[命运]' . htmlspecialchars(Request::url(true)), 'alert');
        //     $response = miss(403);
        //     throw new HttpResponseException($response);
        // }
    }

    /**
     * 频繁或非法请求将被锁定
     * @return void
     */
    private function lock()
    {
        $lock = app()->getRootPath() . 'runtime' . DIRECTORY_SEPARATOR . 'temp' . DIRECTORY_SEPARATOR;
        $lock .= md5(Request::ip()) . '.lock';
        if (is_file($lock)) {
            Log::write('[锁定 ' . Request::ip() . Request::url(true) . ']', 'alert');

            if (Request::isAjax() || Request::isPjax()) {
                $response = Response::create([
                    'code' => 444444,
                    'msg'  => '锁定',
                ], 'json')->allowCache(false);
            } else {
                $response = miss(403, false);
            }

            throw new HttpResponseException($response);
        }
    }

    /**
     * IP进入显示空页面
     * @return void
     */
    private function ipRequest(): void
    {
        $domain = Request::subDomain() . '.' . Request::rootDomain();
        if (false !== filter_var($domain, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $response = Response::create();
            throw new HttpResponseException($response);
        }
    }

    /**
     * 302跳转
     * @return void
     */
    private function redirect(): void
    {
        // $response = Response::create(Config::get('app.app_host'), 'redirect', 302);
        // throw new HttpResponseException($response);
    }

    /**
     * 304缓存
     * @return void
     */
    private function cache304(): void
    {
        if (Request::isGet() && $ms = Request::server('HTTP_IF_MODIFIED_SINCE')) {
            $config = Config::get('route');
            if ($config['request_cache_expire'] && strtotime($ms) + $config['request_cache_expire'] > Request::server('REQUEST_TIME')) {
                $response = Response::create()->code(304);
                $response->header([
                    'X-Powered-By' => 'NI_B_CACHE'
                ]);
                throw new HttpResponseException($response);
            }
        }
    }
}
