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

use think\facade\Log;
use think\facade\Request;
use think\Response;
use think\exception\HttpResponseException;

class CheckRequest
{

    public function handle()
    {
        // if (preg_match('/index\.[\w]+.*?/si', Request::baseUrl())) {
        //     $response = redirect('/');
        //     throw new HttpResponseException($response);
        // }

        // IP进入显示空页面
        $this->ipRequest();
        // 频繁或非法请求将被锁定
        $this->lock();
    }

    /**
     * 频繁或非法请求将被锁定
     * @return void
     */
    private function lock()
    {
        $lock = runtime_path('temp') . md5(Request::ip()) . '.lock';
        if (is_file($lock)) {
            Log::write('[锁定 ' . Request::ip() . Request::url(true) . ']', 'alert');

            if (Request::isAjax() || Request::isPjax()) {
                $response = Response::create([
                    'code' => 444444,
                    'msg'  => '锁定',
                ], 'json')->allowCache(false);
                throw new HttpResponseException($response);
            } else {
                miss(403, false, true);
            }
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
            miss(403, false, true);
        }
    }
}
