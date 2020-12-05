<?php

/**
 * 微信支付
 *
 * @package   NICMS
 * @category  app\common\library\pay
 * @author    失眠小枕头 [312630173@qq.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */
/*


*/

namespace app\common\library\pay;

use think\facade\Config;
use Exception;

class Order
{
    private $payClass = null;
    private $type = '';

    /**
     * 构造方法
     * @access public
     * @param  string $_class  支付方法
     * @param  array  $_config 支付配置
     */
    public function __construct(string $_class, array $_config = [])
    {
        $namespace = '\app\common\library\pay\\' . ucfirst($_class);

        if (!class_exists($namespace)) {
            throw new Exception('pay class not exists:' . $_class);
        }

        $this->payClass = new $namespace($_config);

        $this->type = $_class;
    }

    /**
     * 异步回调
     * @access public
     * @return bool
     */
    public function notify()
    {
        # code...
    }

    /**
     * 同步回调
     * @access public
     * @return bool
     */
    public function respond()
    {
        # code...
    }

    /**
     * 发起支付
     * @access public
     * @param  string $_method 支付类型
     * @return
     */
    public function pay(string $_method)
    {
        if (!method_exists($this->payClass, $_method)) {
            throw new Exception('pay method not exists:' . $_method);
        }

        $param = $this->payParam();
        $result = $this->payClass->$_method($param);
        halt(1);
        if (is_array($result)) {
            throw new Exception('pay param error:' . json_encode($result));
        }

        return [
            'pay_code'  => $result,
            'pay_param' => $param,
        ];
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
            'notify_url'   => Config::get('app.api_host') . 'pay/notify/' . $this->type . '.do',
            // 同步通知回调地址
            'respond_url'  => Config::get('app.api_host') . 'pay/respond/' . $this->type . '.do?out_trade_no=' . $order_no,
            'respond_url'  => request()->server('HTTP_REFERER'),
        ];

        // 支付参数
        // 不同支付类型参数不同
        $param = request()->param('pay_param/a', []);

        return array_merge($param, $common);
    }

    /**
     * 订单号
     * @access private
     * @return string
     */
    private function orderNo(): string
    {
        list($microtime) = explode(' ', microtime());
        return date('YmdHis') .
            mt_rand(1000, 9999) . mt_rand(1000, 9999) .
            str_pad(substr($microtime, 2, 6), 6, '0', STR_PAD_LEFT) .
            mt_rand(1000, 9999);
    }
}
