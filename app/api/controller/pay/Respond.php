<?php

/**
 *
 * 控制层
 * 支付同步回调API
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

use app\common\controller\BaseApi;
use app\common\library\pay\Wechat;

class Respond extends BaseApi
{

    /**
     * 调度
     * @access public
     * @param  string $method
     * @return array
     */
    public function index(string $method)
    {
        $method = strtolower($method);

        if (method_exists($this, $method)) {
            return call_user_func([$this, $method]);
        }

        return miss(404);
    }

    /**
     * 微信同步回调
     */
    public function wechat(): int
    {
        $pay = new Wechat($this->config->get('pay.wechat'));
        if ($result = $pay->respond()) {
            # TODO 修改订单状态
            return 1;
        } else {
            return 0;
        }
    }
}
