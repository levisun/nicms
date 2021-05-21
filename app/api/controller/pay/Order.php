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

use think\facade\Config;
use app\common\controller\BaseApi;

class Order extends BaseApi
{

    public function index(string $pay, string $type)
    {
        $this->ApiInit();

        if (!$config = env('pay.' . strtolower($pay))) {
            $this->abort('This method could not be found.', 40001);
        }
        if (!$config = json_decode(base64_decode($config), true)) {
            $this->abort('This method could not be found.', 40002);
        }

        $pay = '\app\common\library\pay\\' . ucfirst($pay);
        // 校验方法是否存在
        if (!class_exists($pay)) {
            $this->abort('This method could not be found.', 40003);
        }
        $type = strtolower($type);
        if (!method_exists($pay, $type)) {
            $this->abort('This method could not be found.', 40004);
        }

        // 支付参数
        $order_no = $this->orderNo();
        $params = $this->request->param('pay_param/a', []);
        $params = array_merge($params, [
            // 商户订单号
            'out_trade_no' => $order_no,
            // 异步通知回调地址
            'notify_url'   => Config::get('app.api_host') . 'pay/notify/' . strtolower($pay) . '.do',
            // 同步通知回调地址
            'respond_url'  => Config::get('app.api_host') . 'pay/respond/' . strtolower($pay) . '.do' . '?out_trade_no=' . $order_no,
        ]);

        $pay = new $pay($config);
        $result = $pay->$type($params);
        if (is_string($result)) {
            $this->abort($result, 50001);
        }

        return $result;
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
