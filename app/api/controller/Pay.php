<?php

/**
 *
 * 控制层
 * 支付API
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

class Pay extends Async
{

    public function index(string $type)
    {
        if ($this->analysis()->isReferer()) {
            // 支付宝支付
            if ('ali' === $type) {
                # code...
            }

            // 微信公众号支付
            elseif ('wechatjs' === $type) {
                # code...
            }

            // 微信H5支付
            elseif ('wechath5' === $type) {
                # code
            }

            // 微信二维码支付
            elseif ('wechatqrcode' === $type) {
                # code...
            }
        }

        return file_get_contents(app()->getRootPath() . 'public' . DIRECTORY_SEPARATOR . '404.html');
    }

    public function respond(string $type)
    {
        return 'respond' . $type;
    }

    public function notify(string $type)
    {
        return 'notify' . $type;
    }
}
