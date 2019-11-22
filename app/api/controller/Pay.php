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

    public function index(string $method)
    {
        if ($this->analysis()->isReferer()) {
            // 支付宝支付
            if ('ali' === $method) {
                # code...
            }

            // 微信支付
            elseif ('wechat' === $method) {
                $type = $this->request->param('type', 'h5');
                // 公众号支付
                if ('js' === $type) {
                    # code...
                }
                // H5支付
                elseif ('h5' === $type) {
                    # code...
                }
                // 二维码支付
                elseif ('qrcode' === $type) {
                    # code...
                }
            }

            // call_user_func();
        }

        return miss(404);
    }

    public function respond(string $method)
    {
        return 'respond:' . $method;
    }

    public function notify(string $method)
    {
        return 'notify:' . $method;
    }

    /**
     * 订单号
     * @access private
     * @return string
     */
    private function orderNo(): string
    {
        return date('YmdHis') .
            str_pad(str_replace('.', '', microtime(true)), 14, '0', STR_PAD_RIGHT) .
            mt_rand(1111, 9999);
    }
}
