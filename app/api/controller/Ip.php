<?php

/**
 *
 * 控制层
 * IP信息API
 *
 * @package   NICMS
 * @category  app\api\controller
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace app\api\controller;

use think\Response;
use think\exception\HttpResponseException;
use app\common\controller\Async;
use app\common\library\Ipinfo;

class Ip extends Async
{

    public function index()
    {
        // 解决没有传IP参数,缓存造成的缓存错误
        if (!$ip = $this->request->param('ip', false)) {
            $url = $this->request->baseUrl(true) . '?ip=' . $this->request->ip();
            $response = Response::create($url, 'redirect', 302);
            throw new HttpResponseException($response);
        }

        $ip = $this->request->param('ip', false) ?: $this->request->ip();
        if ($ip = (new Ipinfo)->get($ip)) {
            if ($this->request->param('json', false)) {
                return $this->cache(28800)->success('IP', $ip);
            } else {
                $data = 'var NICMS_IPINFO=' . json_encode($ip, JSON_UNESCAPED_UNICODE);
                return Response::create($data)->allowCache(true)
                    ->cacheControl('max-age=28800,must-revalidate')
                    ->expires(gmdate('D, d M Y H:i:s', $this->request->time() + 28800) . ' GMT')
                    ->lastModified(gmdate('D, d M Y H:i:s', $this->request->time() + 28800) . ' GMT')
                    ->contentType('application/javascript');
            }
        }

        return miss(404);
    }
}
