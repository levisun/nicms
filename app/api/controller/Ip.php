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

use app\common\controller\AsyncController;
use app\common\library\Ipinfo;

class Ip extends AsyncController
{
    public function index()
    {
        if ($ip = $this->request->param('ip', false)) {
            if (false !== filter_var($ip, FILTER_VALIDATE_IP)) {
                // $this->validate();
                $this->openCache(true)->success('IP INFO', Ipinfo::get($ip));
            } else {
                $this->error('错误请求', 40001);
            }
        } else {
            $ip = '125.' . mt_rand(1, 255) . '.' . mt_rand(1, 255) . '.' . mt_rand(1, 255);

            $this->openCache(false)->success('IP INFO', Ipinfo::get($ip));
        }
    }
}
