<?php

/**
 *
 * 控制层
 * 支付异步回调API
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

class Notify extends BaseApi
{

    /**
     * 调度
     * @access public
     * @param  string $method
     * @return mixed
     */
    public function index(string $pay)
    {
        if (!$config = env('pay.' . strtolower($pay))) {
            $this->abort('This method could not be found.', 40001);
        }
        if (!$config = json_decode(base64_decode($config), true)) {
            $this->abort('This method could not be found.', 40002);
        }

        $pay = '\app\common\library\pay\\' . ucfirst($pay);
        // 校验方法是否存在
        if (!class_exists($pay)) {
            $this->abort('This method could not be found.', 40001);
        }
        if (!method_exists($pay, 'notify')) {
            $this->abort('This method could not be found.', 40002);
        }

        $pay = new $pay($config);
        $result = $pay->notify();
        if (is_string($result)) {
            $this->abort($result, 50001);
        }

        return $result ? 1 : 0;
    }
}
