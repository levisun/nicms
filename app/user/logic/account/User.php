<?php

/**
 *
 * API接口层
 * 登录
 *
 * @package   NICMS
 * @category  app\user\logic\account
 * @author    失眠小枕头 [312630173@qq.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace app\admin\logic\account;

use app\common\controller\BaseLogic;
use app\common\library\Base64;
use app\common\model\User as ModelUser;

class User extends BaseLogic
{

    /**
     * 登录
     * @access public
     * @return array
     */
    public function login()
    {
    }

    /**
     * 用户注销
     * @access public
     * @return array
     */
    public function logout()
    {
    }

    /**
     * 找回密码
     * @access public
     * @return array
     */
    public function forget()
    {
    }

    /**
     * 注册
     * @access public
     * @return array
     */
    public function reg()
    {
        $receive_data = [
            'username'         => $this->request->param('username'),
            'password'         => $this->request->param('password'),
            'password_confirm' => $this->request->param('password_confirm'),
            'phone'            => $this->request->param('phone'),
            'email'            => $this->request->param('email'),
            'level_id'         => $this->request->param('level_id/d', 0),
            'status'           => $this->request->param('status/d', 1),
        ];
        if ($result = $this->validate(__METHOD__, $receive_data)) {
            return $result;
        }

        $receive_data['salt'] = Base64::flag(microtime(true) . $receive_data['password'], 6);
        $receive_data['password'] = Base64::createPassword($receive_data['password'], $receive_data['salt']);
        ModelUser::create($receive_data);

        return [
            'debug' => false,
            'cache' => false,
            'msg'   => 'user added success',
        ];
    }
}
