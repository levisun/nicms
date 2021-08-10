<?php

/**
 *
 * API接口层
 * 登录
 *
 * @package   NICMS
 * @category  app\admin\logic\account
 * @author    失眠小枕头 [312630173@qq.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace app\admin\logic\account;

use app\common\controller\BaseLogic;

class Logout extends BaseLogic
{
    protected $authKey = 'admin_auth_key';

    /**
     * 用户注销
     * @access public
     * @return array
     */
    public function query(): array
    {
        $this->actionLog('admin user logout');

        $this->cache->delete('AUTH' . $this->userId);
        $this->removeUserSession();

        return [
            'debug' => false,
            'cache' => false,
            'msg'   => 'success'
        ];
    }
}
