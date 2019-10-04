<?php

/**
 *
 * API接口层
 * 登录
 *
 * @package   NICMS
 * @category  app\service\admin\account
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace app\service\admin\account;

use app\library\Base64;
use app\library\Rbac;
use app\library\Session as LibSession;
use app\service\BaseService;
use app\model\Admin as ModelAdmin;

class User extends BaseService
{
    protected $authKey = 'admin_auth_key';

    /**
     * 登录
     * @access public
     * @param
     * @return array
     */
    public function login()
    {
        $lock = $this->app->getRuntimePath() . 'temp' . DIRECTORY_SEPARATOR . md5($this->request->ip() . date('YmdH')) . '.lock';
        if (is_file($lock)) {
            // 登录锁定
            return [
                'debug' => false,
                'cache' => false,
                'msg'   => 'login error'
            ];
        }

        $user = (new ModelAdmin)
            ->view('admin', ['id', 'username', 'password', 'salt', 'flag'])
            ->view('role_admin', ['role_id'], 'role_admin.user_id=admin.id')
            ->view('role role', ['name' => 'role_name'], 'role.id=role_admin.role_id')
            ->where([
                ['admin.username', '=', $this->request->param('username')]
            ])
            ->find();
        if (!$user || false === Base64::verifyPassword($this->request->param('password'), $user['salt'], $user['password'])) {
            // 记录登录错误次数
            $login_lock = $this->session->has('login_lock') ? $this->session->get('login_lock') : 0;
            ++$login_lock;
            if ($login_lock >= 5) {
                $this->session->delete('login_lock');
                file_put_contents($lock, 'lock');
            } else {
                $this->session->set('login_lock', $login_lock);
            }

            return [
                'debug' => false,
                'cache' => false,
                'msg'   => 'login error'
            ];
        }

        // 更新登录信息
        (new ModelAdmin)
            ->where([
                ['id', '=', $user['id']]
            ])
            ->data([
                'flag'               => $this->session->getId(false),
                'last_login_time'    => time(),
                'last_login_ip'      => $this->ipinfo['ip'],
                'last_login_ip_attr' => $this->ipinfo['province_id'] ? $this->ipinfo['province'] . $this->ipinfo['city'] . $this->ipinfo['area'] : ''
            ])
            ->update();

        // 唯一登录
        if ($user['flag'] && $user['flag'] !== $this->session->getId(false)) {
            (new LibSession)->delete($user['flag']);
        }

        // 登录令牌
        $this->session->set($this->authKey, $user['id']);
        $this->session->set($this->authKey . '_role', $user['role_id']);
        $this->session->delete('login_lock');

        $this->uid = $user['id'];
        $this->urole = $user['role_id'];
        $this->authenticate(__METHOD__, 'admin user login');

        return [
            'debug' => false,
            'cache' => false,
            'msg'   => 'login success'
        ];
    }

    /**
     * 用户注销
     * @access public
     * @param
     * @return array
     */
    public function logout(): array
    {
        if ($result = $this->authenticate(__METHOD__, 'admin user logout')) {
            return $result;
        }

        $this->cache->delete('auth' . $this->uid);
        $this->session->delete($this->authKey);
        $this->session->delete($this->authKey . '_role');

        return [
            'debug' => false,
            'cache' => false,
            'msg'   => $this->lang->get('logout success')
        ];
    }

    /**
     * 找回密码
     * @access public
     * @param
     * @return array
     */
    public function forget(): array
    {
        if ($result = $this->authenticate(__METHOD__, 'admin user forget')) {
            return $result;
        }
        # code...
    }

    /**
     * 权限
     * @access public
     * @param
     * @return array
     */
    public function auth(): array
    {
        if ($result = $this->authenticate(__METHOD__)) {
            return $result;
        }

        if (!$this->cache->has('auth' . $this->uid) || !$result = $this->cache->get('auth' . $this->uid)) {
            $result = (new Rbac)->getAuth($this->uid);
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
            $this->cache->tag('AUTH')->set('auth' . $this->uid, $result);
        }

        return [
            'debug' => false,
            'cache' => true,
            'msg'   => 'user author',
            'data'  => $result
        ];
    }

    /**
     * 用户信息
     * @access public
     * @param
     * @return array
     */
    public function profile(): array
    {
        if ($result = $this->authenticate(__METHOD__)) {
            return $result;
        }

        $result = (new ModelAdmin)
            ->view('admin', ['id', 'username', 'email', 'last_login_ip', 'last_login_ip_attr', 'last_login_time'])
            ->view('role_admin', ['role_id'], 'role_admin.user_id=admin.id')
            ->view('role role', ['name' => 'role_name'], 'role.id=role_admin.role_id')
            ->where([
                ['admin.id', '=', $this->uid]
            ])
            ->cache('profile' . $this->uid)
            ->find();

        if ($result && $result = $result->toArray()) {
            $result['last_login_time'] = date('Y-m-d H:i:s', (int) $result['last_login_time']);
            $result['avatar'] = avatar('', $result['username']);
        }

        return [
            'debug'  => false,
            'cache'  => false,
            'msg'    => 'user profile',
            'data'   => $result
        ];
    }

    public function notice()
    {
        if ($result = $this->authenticate(__METHOD__)) {
            return $result;
        }

        $result = [];

        // 验证备份状态
        $status = true;
        $file = (array) glob($this->app->getRuntimePath() . 'backup' . DIRECTORY_SEPARATOR . '*');
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

        // 错误日志
        if (is_file($this->app->getRuntimePath() . 'log' . DIRECTORY_SEPARATOR . date('Ymd') . '_error.log')) {
            $result[] = [
                'title' => $this->lang->get('program error message'),
                'url'   => url('expand/elog/index')
            ];
        }

        // 垃圾信息
        $count = count((array) glob($this->app->getRuntimePath() . 'cache' . DIRECTORY_SEPARATOR . '*'));
        if ($count >= 2000) {
            $result[] = [
                'title' => $this->lang->get('too much junk information'),
                'url'   => url('content/cache/index')
            ];
        }

        return [
            'debug' => false,
            'cache' => false,
            'msg'   => 'user notice',
            'data'  => [
                'list'  => $result,
                'total' => count($result),
            ]
        ];
    }
}
