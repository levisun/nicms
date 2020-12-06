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
use app\common\library\IpInfo;
use app\common\library\Base64;
use app\common\library\Image;
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
            ->where([
                ['admin.status', '=', 1],
                ['admin.username', '=', $this->request->param('username')]
            ])
            ->whereOr([
                ['admin.phone', '=', $this->request->param('username')],
                ['admin.email', '=', $this->request->param('username')]
            ])
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
                $this->cache->set($cache_key, date('Y-m-d H:i:s'), 28800);
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
        $info = (new IpInfo)->get($this->request->ip());
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

        return [
            'debug' => false,
            'cache' => false,
            'code'  => 10000,
            'msg'   => 'success',
            'data'  => [
                'user_id'      => Base64::encrypt($this->user_id),
                'user_role_id' => Base64::encrypt($this->user_role_id),
                'user_type'    => Base64::encrypt($this->user_type),
                'user_token'   => sha1(implode('', array_map('sha1', $user)) . 'admin'),
            ]
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

        $this->cache->delete('AUTH' . $this->user_id);
        $this->session->delete($this->authKey);
        $this->session->delete($this->authKey . '_role');

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
        if (!$this->cache->has('AUTH' . $this->user_id) || !$result = $this->cache->get('AUTH' . $this->user_id)) {
            $result = (new Rbac)->getAuth($this->user_id);
            $result = $result['admin'];
            foreach ($result as $key => $value) {
                $result[$key] = [
                    'name' => $key,
                    'lang' => $this->lang->get('auth ' . $key),
                ];
                foreach ($value as $k => $val) {
                    $result[$key]['child'][$k] = [
                        'name' => $k,
                        'lang' => $this->lang->get('auth ' . $k),
                        'url'  => url($key . '/' . $k . '/index')
                    ];
                }
            }
            $this->cache->set('AUTH' . $this->user_id, $result);
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

        if ($this->user_id) {
            $result = ModelAdmin::view('admin', ['id', 'username', 'email', 'last_login_ip', 'last_login_ip_attr', 'last_login_time'])
                ->view('role_admin', ['role_id'], 'role_admin.user_id=admin.id')
                ->view('role role', ['name' => 'role_name'], 'role.id=role_admin.role_id')
                ->where('admin.id', '=', $this->user_id)
                ->cache('ADMIN PROFILE' . $this->user_id, 300, 'admin')
                ->find();

            if (null !== $result && $result = $result->toArray()) {
                $result['last_login_time'] = date('Y-m-d H:i:s', (int) $result['last_login_time']);
                $result['avatar'] = Image::avatar('', $result['username']);
                $result['id'] = Base64::encrypt((string) $result['id'], 'uid');
                $result['role_id'] = Base64::encrypt((string) $result['role_id'], 'roleid');
                $result['app_debug'] = (int) $this->config->get('app.debug');
                // unset($result['id'], $result['role_id']);
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
