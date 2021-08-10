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
use app\common\library\Base64;
use app\common\library\File;
use app\common\model\Admin as ModelAdmin;

class Profile extends BaseLogic
{
    protected $authKey = 'admin_auth_key';

    /**
     * 用户信息
     * @access public
     * @return array
     */
    public function query(): array
    {
        $result = null;

        if ($this->userId) {
            $result = ModelAdmin::view('admin', ['id', 'username', 'email', 'last_login_ip', 'last_login_ip_attr', 'last_login_time'])
                ->view('role_admin', ['role_id'], 'role_admin.user_id=admin.id')
                ->view('role role', ['name' => 'role_name'], 'role.id=role_admin.role_id')
                ->where('admin.id', '=', $this->userId)
                ->cache('ADMIN PROFILE' . $this->userId, 300, 'admin')
                ->find();

            if ($result && $result = $result->toArray()) {
                $this->setUserSession($result['id'], $result['role_id'], 'admin');

                $result['last_login_time'] = date('Y-m-d H:i:s', (int) $result['last_login_time']);
                $result['avatar'] = File::avatar('', $result['username']);

                $result['user_id'] = Base64::url62encode($this->userId);
                $result['user_role_id'] = Base64::url62encode($this->userRoleId);
                $result['user_type'] = Base64::encrypt($this->userType);
                $result['user_token'] = Base64::encrypt(json_encode([
                    $result['user_id'], $result['user_role_id'], $result['user_type']
                ]));

                unset($result['id'], $result['role_id']);
            }
        }

        return [
            'debug'  => false,
            'cache'  => false,
            'msg'    => 'success',
            'data'   => $result
        ];
    }
}
