<?php

/**
 *
 * 控制层
 * Api
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

use app\common\controller\Async;
use app\common\library\Ipinfo;

class Ip extends Async
{
    public function index()
    {
        if ($this->request->server('HTTP_REFERER')) {
            $ip = $this->request->param('ip', false) ?: $this->request->ip();
            if (false !== filter_var($ip, FILTER_VALIDATE_IP)) {
                $this->cache(true)->success('IP INFO', (new Ipinfo)->get($ip));
            } else {
                $this->error('错误请求', 40001);
            }
        }

        return file_get_contents(app()->getRootPath() . 'public' . DIRECTORY_SEPARATOR . '404.html');
    }
}
