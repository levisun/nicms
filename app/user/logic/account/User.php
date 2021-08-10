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

namespace app\user\logic\account;

use app\common\controller\BaseLogic;
use app\common\library\File;
use app\common\library\IpV4;
use app\common\library\Base64;
use app\common\model\User as ModelUser;

class User extends BaseLogic
{

    /**
     * 登录
     * @access public
     * @return array
     */
    public function login(): array
    {
        $receive_data = [
            'captcha'  => (string) $this->request->param('captcha'),
            'username' => $this->request->param('username'),
            'password' => $this->request->param('password'),
        ];

        if ($result = $this->validate($receive_data)) {
            return $result;
        }

        $user = ModelUser::view('user', ['id', 'username', 'password', 'salt', 'flag', 'level_id'])
            ->view('user_level', ['name' => 'role_name'], 'user_level.id=user.level_id')
            ->where('user.status', '=', 1)
            ->where('user.username|user.phone|user.email', '=', $this->request->param('username'))
            ->find();

        $user = $user ? $user->toArray() : false;

        // 用户不存在 密码错误
        if (!$user || !Base64::verifyPassword($this->request->param('password'), $user['salt'], $user['password'])) {
            // 记录登录错误次数
            $login_lock = $this->session->has('login_lock') ? $this->session->get('login_lock') : 0;
            ++$login_lock;

            // 错误次数超过5次锁定IP
            // 锁定方法在[\app\common\middleware\Throttle::class]
            if ($login_lock >= 5) {
                $this->session->delete('login_lock');
                $cache_key = $this->request->domain() . $this->request->ip() . 'login_lock';
                $this->cache->set($cache_key, date('Y-m-d H:i:s'), 14400);
            } else {
                $this->session->set('login_lock', $login_lock);
            }

            return [
                'debug' => false,
                'cache' => false,
                'code'  => 40009,
                'msg'   => 'error'
            ];
        }

        // 更新登录信息
        $info = (new IpV4)->get($this->request->ip());
        ModelUser::where('id', '=', $user['id'])
            ->limit(1)
            ->update([
                'flag'               => $this->session->getId(false),
                'last_login_time'    => time(),
                'last_login_ip'      => $info['ip'],
                'last_login_ip_attr' => isset($info['country_id']) ? $info['region'] . $info['city'] . $info['area'] : ''
            ]);

        // 唯一登录
        if ($user['flag'] && $user['flag'] !== $this->session->getId(false)) {
            $this->session->delete($user['flag']);
        }

        // 登录令牌
        $this->session->delete('login_lock');
        $this->setUserSession($user['id'], $user['level_id'], 'user');

        unset($user['password']);
        shuffle($user);

        $user = [];
        $user['user_id'] = Base64::url62encode($this->userId);
        $user['user_role_id'] = Base64::url62encode($this->userRoleId);
        $user['user_type'] = Base64::encrypt($this->userType);
        $user['user_token'] = Base64::encrypt(json_encode([
            $user['user_id'], $user['user_role_id'], $user['user_type']
        ]));

        return [
            'debug' => false,
            'cache' => false,
            'code'  => 10000,
            'msg'   => 'success',
            'data'  => $user,
        ];
    }

    /**
     * 用户注销
     * @access public
     * @return array
     */
    public function logout(): array
    {
        $this->removeUserSession();

        return [
            'debug' => false,
            'cache' => false,
            'msg'   => 'success'
        ];
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
        if ($result = $this->validate($receive_data)) {
            return $result;
        }

        $receive_data['salt'] = Base64::salt(microtime(true) . $receive_data['password']);
        $receive_data['password'] = Base64::createPassword($receive_data['password'], $receive_data['salt']);

        $model = new ModelUser;
        $model->save($receive_data);
        $user_id = (int) $model->id;

        $this->setUserSession($user_id, 0, 'user');

        return [
            'debug' => false,
            'cache' => false,
            'msg'   => 'success',
            'data'  => [
                'user_id'      => Base64::encrypt($user_id),
                'user_role_id' => Base64::encrypt(0),
                'user_type'    => Base64::encrypt('user'),
                'user_token'   => Base64::encrypt(json_encode([$user_id, 0, 'user'])),
            ]
        ];
    }

    /**
     * 用户信息
     * @access public
     * @return array
     */
    public function profile(): array
    {
        $result = null;

        if ($user_token = $this->request->param('user_token')) {
            $user_token = json_decode(Base64::decrypt($user_token));
            if (is_array($user_token)) {
                list($user_id, $user_role_id, $user_type) = $user_token;
                if ($user_type == 'user') {
                    $result = ModelUser::view('user', ['id', 'username', 'email', 'level_id', 'last_login_ip', 'last_login_ip_attr', 'last_login_time'])
                        ->view('user_level', ['name' => 'role_name'], 'user_level.id=user.level_id')
                        ->where('user.status', '=', 1)
                        ->where('user.id', '=', $user_id)
                        ->where('user.user_type', '=', $user_role_id)
                        ->cache('USER PROFILE' . $user_id, 300, 'user')
                        ->find();

                    if (null !== $result && $result = $result->toArray()) {
                        $this->setUserSession($result['id'], $result['level_id'], 'user');

                        $result['last_login_time'] = date('Y-m-d H:i:s', (int) $result['last_login_time']);
                        $result['avatar'] = File::avatar('', $result['username']);

                        $result['user_id'] = Base64::url62encode($this->userId);
                        $result['user_role_id'] = Base64::url62encode($this->userRoleId);
                        $result['user_type'] = Base64::encrypt($this->userType);
                        $result['user_token'] = Base64::encrypt(json_encode([
                            $result['user_id'], $result['user_role_id'], $result['user_type']
                        ]));

                        unset($result['id'], $result['level_id']);
                    }
                }
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
