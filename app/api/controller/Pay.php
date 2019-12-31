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
    private $notify_url = '';
    private $respond_url = '';
    private $pay = null;
    private $payConfig = [
        // 支付宝
        'ali' => [
            'partner'      => '2088211078198539',
            'seller_id'    => '2088211078198539',
            'seller_email' => '1584035147@qq.com',
            'key'          => '4mlfr6b5hkf98ntgiwb4clbkbxioeg28',
            'cacert'       => 'cert/ali_cacert.pem',
            'transport'    => 'http',
        ],
        // 微信
        'wechat' => [
            'appid'        => 'wxea53b7eabf4beb2d',
            'appsecret'    => 'ac1a9edce78573f3d287f9560a2d50a7',
            'mch_id'       => '1487938612',
            'key'          => '0af4769d381ece7b4fddd59dcf048da6',
            'sslcert_path' => '1487938612_cert.pem',
            'sslkey_path'  => '1487938612_key.pem',
        ]
    ];

    public function index(string $method)
    {
        if ($this->analysis()->isReferer()) {
            // 支付宝支付
            if ('ali' === $method) {
                # code...
            }

            // 微信支付
            elseif ('wechat' === $method) {
                $this->pay = new \pay\PayWechat($this->payConfig['wechat']);

                $type = $this->request->param('type', 'h5');
                // 公众号支付
                if ('js' === $type) {
                    $result = $this->pay->jsPay($this->wechatParam());
                }
                // H5支付
                elseif ('h5' === $type) {
                    $result = $this->pay->H5Pay($this->wechatParam());
                }
                // 二维码支付
                elseif ('qrcode' === $type) {
                    $result = $this->pay->qrcodePay($this->wechatParam());
                }
            }

            // call_user_func();
        }

        return miss(404);
    }

    /**
     * 同步回调
     * @access public
     * @param  string $method
     * @return array
     */
    public function respond(string $method)
    {
        // 支付宝支付
        if ('ali' === $method) {
            # code...
        }
        // 微信支付
        elseif ('wechat' === $method) {
            $this->pay = new \pay\PayWechat($this->payConfig['wechat']);
            if ($result = $this->pay->respond()) {
                # TODO 修改订单状态
                return true;
            } else {
                return false;
            }
        }

        return miss(404);
    }

    /**
     * 异步回调
     * @access public
     * @param  string $method
     * @return array
     */
    public function notify(string $method)
    {
        // 支付宝支付
        if ('ali' === $method) {
            # code...
        }
        // 微信支付
        elseif ('wechat' === $method) {
            $this->pay = new \pay\PayWechat($this->payConfig['wechat']);
            if ($result = $this->pay->notify()) {
                # TODO 修改订单状态
                return true;
            } else {
                return false;
            }
        }

        return miss(404);
    }

    /**
     * 微信支付参数
     * @return array
     */
    private function wechatParam(): array
    {
        $order_no = $this->orderNo();
        return [
            'attach'       => mb_substr($this->request->param('attach'), 0, 120, 'UTF-8'),      // 附加数据 127位
            'body'         => mb_substr($this->request->param('body'), 0, 120, 'UTF-8'),        // 商品描述 128位
            'detail'       => $this->request->param('detail'),                                  // 商品详情
            'out_trade_no' => $order_no,                                                        // 商户订单号 32位 数字
            'total_fee'    => $this->request->param('total_fee/f') * 100,                       // 单位分
            'goods_tag'    => mb_substr($this->request->param('goods_tag'), 0, 32, 'UTF-8'),    // 商品标记 32位
            'notify_url'   => $this->app->config['api_host'] . 'pay/notify/wechat.html',
            'respond_url'  => $this->app->config['api_host'] . 'pay/respond/wechat.html' . '?out_trade_no=' . $order_no,
            'product_id'   => mb_substr($this->request->param('product_id'), 0, 32, 'UTF-8'),   // 商品ID 32位
            'openid'       => $this->request->param('openid'),                                  // 请求微信OPENID JS必填
        ];
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
