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

use app\common\controller\Async;

class Handle extends Async
{

    public function index()
    {
        if ($this->analysis()->isReferer()) {
            if (empty($_POST)) {
                $this->error('错误请求', 40009);
            }

            $this->run()->cache(false)->response(
                $this->result['msg'],
                $this->result['data'],
                $this->result['code']
            );
        }

        return $this->_404();
    }
}
