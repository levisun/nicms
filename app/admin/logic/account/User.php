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
use app\common\library\File;
use app\common\library\IpV4;
use app\common\library\Base64;
use app\common\library\Rbac;
use app\common\model\Admin as ModelAdmin;

class User extends BaseLogic
{
    protected $authKey = 'admin_auth_key';

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

        $user = ModelAdmin::view('admin', ['id', 'username', 'password', 'salt', 'flag'])
            ->view('role_admin', ['role_id'], 'role_admin.user_id=admin.id')
            ->view('role', ['name' => 'role_name'], 'role.id=role_admin.role_id')
            ->where('admin.status', '=', 1)
            ->where('admin.username|admin.phone|admin.email', '=', $this->request->param('username'))
            ->find();

        if ($user && $user = $user->toArray()) {
            // 校验密码
            if (Base64::verifyPassword($this->request->param('password'), $user['salt'], $user['password'])) {
                // 更新登录信息
                $info = (new IpV4)->get($this->request->ip());
                ModelAdmin::where('id', '=', $user['id'])
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
                $this->setUserSession($user['id'], $user['role_id'], 'admin');

                $this->actionLog('admin user login');

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
            } else {
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
            }
        }

        return [
            'debug' => false,
            'cache' => false,
            'code'  => 40009,
            'msg'   => 'error'
        ];
    }

    /**
     * 用户注销
     * @access public
     * @return array
     */
    public function logout(): array
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

    /**
     * 找回密码
     * @access public
     * @return array
     */
    public function forget()
    {
        $this->actionLog('admin user forget');
        # code...
    }

    /**
     * 权限
     * @access public
     * @return array
     */
    public function auth(): array
    {
        if (!$this->cache->has('AUTH' . $this->userId) || !$result = $this->cache->get('AUTH' . $this->userId)) {
            $result = (new Rbac)->setUserId($this->userId)->getAuth();
            $result = $result['admin'];
            foreach ($result as $key => $value) {
                $result[$key] = [
                    'name' => $key,
                    'lang' => $this->lang->get('auth.' . $key),
                ];
                foreach ($value as $k => $val) {
                    $result[$key]['child'][$k] = [
                        'name' => $k,
                        'lang' => $this->lang->get('auth.' . $key . '_' . $k),
                        'url'  => url($key . '/' . $k . '/index')
                    ];
                }
            }
            $this->cache->set('AUTH' . $this->userId, $result);
        }

        return [
            'debug' => false,
            'cache' => true,
            'msg'   => 'success',
            'data'  => $result
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

    public function notice(): array
    {
        $result = [];

        // 验证备份状态
        $status = true;
        $file = (array) glob($this->app->getRootPath() . 'runtime' . DIRECTORY_SEPARATOR . 'backup' . DIRECTORY_SEPARATOR . '*');
        if (count($file) >= 2) {
            foreach ($file as $value) {
                if (filectime($value) >= strtotime('-7 days')) {
                    $status = true;
                    continue;
                }
            }
        } else {
            $status = false;
        }
        if ($status === false) {
            $result[] = [
                'title' => $this->lang->get('please make a database backup'),
                'url'   => url('expand/database/index')
            ];
        }

        return [
            'debug' => false,
            'cache' => true,
            'msg'   => 'success',
            'data'  => [
                'list'  => $result,
                'total' => count($result),
            ]
        ];
    }
}
