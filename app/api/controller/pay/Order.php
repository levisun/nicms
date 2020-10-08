<?php

/**
 *
 * 控制层
 * 支付API
 *
 * @package   NICMS
 * @category  app\api\controller\pay
 * @author    失眠小枕头 [312630173@qq.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace app\api\controller\pay;

use app\common\library\api\Async;
use app\common\library\pay\Wechat;

class Order extends Async
{

    public function index(string $method)
    {
        if ($this->validate->referer() && $this->validate->fromToken()) {
            $method = strtolower($method);

            if (method_exists($this, $method)) {
                return call_user_func([$this, $method]);
            }
        }

        return miss(404, false);
    }

    /**
     * 微信支付
     */
    public function wechat(): array
    {
        $pay = new Wechat($this->config->get('pay.wechat'));
        $type = $this->request->param('type', 'h5');

        // 公众号支付
        if ('js' === $type) {
            $result = $pay->jsPay($this->payParam());
        }
        // H5支付
        elseif ('h5' === $type) {
            $result = $pay->H5Pay($this->payParam());
        }
        // 二维码支付
        elseif ('qrcode' === $type) {
            $result = $pay->qrcodePay($this->payParam());
        }

        return $result;
    }

    /**
     * 支付参数
     * @access private
     * @return array
     */
    private function payParam(): array
    {
        $order_no = $this->orderNo();
        $common = [
            // 商户订单号
            'out_trade_no' => $order_no,
            // 异步通知回调地址
            'notify_url'   => $this->app->config['api_host'] . 'pay/notify/wechat.do',
            // 同步通知回调地址
            'respond_url'  => $this->app->config['api_host'] . 'pay/respond/wechat.do' .
                '?out_trade_no=' . $order_no,
        ];

        // 支付参数
        // 不同支付类型参数不同
        $param = $this->request->param('pay_param/a');
        return array_merge($param, $common);
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
