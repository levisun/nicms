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

use think\facade\Cache;
use think\facade\Config;
use think\facade\Log;
use think\facade\Request;
use think\Response;
use think\exception\HttpResponseException;

class CheckRequestCache
{

    public function handle()
    {
        // 频繁或非法请求将被锁定
        $this->lock();
        // 非域名进入302跳转
        $this->_302();
        // 304缓存
        $this->_304();

        if (1 === mt_rand(1, 999)) {
            Log::write('[命运]' . htmlspecialchars(Request::url(true)), 'alert');
            $response = miss(503);
            throw new HttpResponseException($response);
        }
    }

    /**
     * 频繁或非法请求将被锁定
     * @return void
     */
    private function lock()
    {
        $path = app()->getRootPath() . 'runtime' . DIRECTORY_SEPARATOR . 'temp' . DIRECTORY_SEPARATOR;
        $lock  = $path . md5(Request::ip()) . '.lock';
        if (is_file($lock)) {
            Log::write('[锁定]', 'alert');
            $response = miss(502, false);
            throw new HttpResponseException($response);
        }
    }

    /**
     * 非域名进入302跳转
     * @return void
     */
    private function _302(): void
    {
        $domain = Request::subDomain() . '.' . Request::rootDomain();
        if (false !== filter_var($domain, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $response = Response::create(Config::get('app.app_host'), 'redirect', 302);
            throw new HttpResponseException($response);
        }
    }

    /**
     * 304缓存
     * @return void
     */
    private function _304(): void
    {
        if (Request::isGet() && $ms = Request::server('HTTP_IF_MODIFIED_SINCE')) {
            if (strtotime($ms) >= Request::time()) {
                $response = Response::create()->code(304);
                $response->header(array_merge(['X-Powered-By' => 'NICACHE'], $response->getHeader()));
                throw new HttpResponseException($response);
            }
        }
    }
}
