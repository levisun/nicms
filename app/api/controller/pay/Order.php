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
use app\common\library\Base64;
use app\common\model\Order as ModelOrder;

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

        $class = '\app\common\library\pay\\' . ucfirst($pay);
        // 校验方法是否存在
        if (!class_exists($class)) {
            $this->abort('This method could not be found.', 40003);
        }
        $type = strtolower($type);
        if (!method_exists($class, $type)) {
            $this->abort('This method could not be found.', 40004);
        }

        // 检查用户登录状态
        if (!$this->userId) {
            $this->abort('No action permissions.', 40005);
        }

        // 获得订单状态
        $goods_id = $this->request->param('goods_id/d', 0, 'abs');
        $order_info = $this->getOrder($goods_id);
        if ($order_info) {
            if (2 === $order_info['status']) {
                return $this->cache(true)->success('pay success', $order_info);
            } elseif (1 === $order_info['status']) {
                return $this->cache(false)->success('pay await', $order_info);
            }
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

        $pay = new $class($config);
        $result = $pay->$type($params);
        if (is_string($result)) {
            $this->abort($result, 50001);
        }

        // $this->createOrder($goods_id, $order_no, );

        return $this->cache(false)->success('order info', $result);
    }

    /**
     * 创建订单
     * @access private
     * @param  int    $_goods_id 商品ID
     * @param  string $_order_no 订单号
     * @param  int    $_amount   支付金额
     * @return void
     */
    private function createOrder(int $_goods_id, string $_order_no, int $_amount): void
    {
        $has = ModelOrder::where('goods_id', '=', $_goods_id)
            ->where('user_id', '=', $this->userId)
            ->order('id DESC')
            ->value('id');
        if (!$has) {
            ModelOrder::create([
                'user_id'  => $this->userId,
                'goods_id' => $_goods_id,
                'order_no' => $_order_no,
                'amount'   => $_amount,
            ]);
        }
    }

    /**
     * 获得订单
     * @access private
     * @param  int $_goods_id 商品ID
     * @return array|false
     */
    private function getOrder(int $_goods_id)
    {
        // 修改过期订单状态
        ModelOrder::where('status', '=', 1)
            ->whereTime('create_time', '<', strtotime('-10 minutes'))
            ->update(['status' => 4]);

        $result = ModelOrder::field('id, goods_id, user_id, order_no, trade_no, amount, status, pay_time, refund_time')
            ->where('goods_id', '=', $_goods_id)
            ->where('user_id', '=', $this->userId)
            ->order('id DESC')
            ->find();
        if ($result && $result = $result->toArray()) {
            $result['id'] = Base64::url62encode($result['id']);
        }

        return $result ? $result->toArray() : false;
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
