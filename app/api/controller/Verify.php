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
use think\captcha\facade\Captcha;

class Verify extends AsyncController
{

    public function index()
    {
        if ($this->request->server('HTTP_REFERER')) {
            $config = mt_rand(0, 1) ? 'verify_zh' : 'verify_math';
            return Captcha::create($config, true);
        } else {
            $this->error('错误请求', 40009);
        }
    }
}
