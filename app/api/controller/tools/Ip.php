<?php

/**
 *
 * 控制层
 * IP信息API
 *
 * @package   NICMS
 * @category  app\api\controller\tools
 * @author    失眠小枕头 [312630173@qq.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace app\api\controller\tools;

use think\Response;
use app\common\library\api\Async;
use app\common\library\IpInfo;
use app\common\library\Ipv4;

class Ip extends Async
{

    public function index()
    {
        // 解决没有传IP参数,缓存造成的缓存错误
        if (!$ip = $this->request->param('ip', false)) {
            $url = $this->request->baseUrl(true) . '?ip=' . $this->request->ip();
            return Response::create($url, 'redirect', 302);
        }

        usleep(500000);

        $ip = $this->request->param('ip', false) ?: $this->request->ip();

        $referer = $this->request->server('HTTP_REFERER');
        if (!$referer || false === stripos($referer, $this->request->rootDomain())) {
            $old = $ip;
            $ip = explode('.', $ip, 4);
            $ip[3] = 0;
            $ip = implode('.', $ip);
        }

        if ($result = (new Ipv4)->get($ip)) {
            if (isset($old)) {
                $result['ip'] = $old;
            }

            $timestamp = $this->request->time() + 3600 * 6;
            return Response::create('const IP = ' . json_encode($result))
                ->allowCache(true)
                ->cacheControl('max-age=28800,must-revalidate')
                ->expires(gmdate('D, d M Y H:i:s', $timestamp + 28800) . ' GMT')
                ->lastModified(gmdate('D, d M Y H:i:s', $timestamp + 28800) . ' GMT')
                ->contentType('application/javascript');
        }

        return miss(404, false);
    }
}
