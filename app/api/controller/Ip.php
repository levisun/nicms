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
use app\common\controller\Async;
use app\common\library\Ipinfo;

class Ip extends Async
{

    public function index()
    {
        $ip = $this->request->param('ip', false) ?: $this->request->ip();
        if (false !== filter_var($ip, FILTER_VALIDATE_IP)) {
            $ip = (new Ipinfo)->get($ip);
            $data = '';
            foreach ($ip as $key => $value) {
                $data .= $key . '=' . $value . '&';
            }
            $data = rtrim($data, '&');

            // if ($this->isReferer(false)) {

            // }

            return Response::create($data)->allowCache(true)
                ->cacheControl('max-age=1440,must-revalidate')
                ->expires(gmdate('D, d M Y H:i:s', $this->request->time() + 1440) . ' GMT')
                ->lastModified(gmdate('D, d M Y H:i:s', $this->request->time() + 1440) . ' GMT')
                ->contentType('application/javascript');
        }


        return miss(404);
    }
}
