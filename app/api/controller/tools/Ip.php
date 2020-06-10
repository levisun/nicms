<?php

/**
 *
 * 控制层
 * IP信息API
 *
 * @package   NICMS
 * @category  app\api\controller\tools
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace app\api\controller\tools;

use think\Response;
use think\exception\HttpResponseException;
use app\common\library\api\Async;
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
        if ($ip = Ipinfo::get($ip)) {
            return $this->cache(1440)->success('IP', $ip);
        }

        return miss(404);
    }
}
