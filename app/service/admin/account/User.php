<?php
/**
 *
 * API接口层
 * 用户登录
 *
 * @package   NICMS
 * @category  app\service\admin\account
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */
declare (strict_types = 1);

namespace app\service\admin\account;

use app\library\Base64;
use app\library\Ip;
use app\library\Rbac;
use app\library\Session as LibSession;
use app\service\BaseService;
use app\model\Admin as ModelAdmin;

class User extends BaseService
{
    protected $auth_key = 'admin_auth_key';

    /**
     * 登录
     * @access public
     * @param
     * @return array
     */
    public function login()
    {
        if ($result = $this->validate(__METHOD__)) {
            return $result;
        }

        $lock = $this->app->getRuntimePath() . md5($this->request->ip() . date('YmdH')) . '.lock';
        if (!is_file($lock)) {
            $user = (new ModelAdmin)
                ->field(['id', 'username', 'password', 'salt', 'flag'])
                ->where([
                    ['username', '=', $this->request->param('username')]
                ])
                ->find();

            if ($user && $user['password'] === Base64::password($this->request->param('password'), $user['salt'])) {
                $ip = (new Ip)->info();
                (new ModelAdmin)
                    ->where([
                        ['id', '=', $user['id']]
                    ])
                    ->data([
                        'flag'               => $this->session->getId(false),
                        'last_login_time'    => time(),
                        'last_login_ip'      => $ip['ip'],
                        'last_login_ip_attr' => $ip['province_id'] ? $ip['province'] . $ip['city'] . $ip['area'] : ''
                    ])
                    ->update();

                // 唯一登录, 踢掉
                if ($user['flag'] && $user['flag'] !== $this->session->getId(false)) {
                    (new LibSession)->delete($user['flag']);
                }

                // 登录令牌
                session('admin_auth_key', $user['id']);
                session('login_lock', null);

                $this->uid = $result['id'];
                $this->authenticate(__METHOD__, 'admin user login');

                return [
                    'debug' => false,
                    'cache' => false,
                    'msg'   => 'login success'
                ];
            } else {
                $login_lock = session('?login_lock') ? session('login_lock') : 0;
                ++$login_lock;
                if ($login_lock >= 5) {
                    session('login_lock', null);
                    file_put_contents($lock, 'lock');
                } else {
                    session('login_lock', $login_lock);
                }
            }
        }

        return [
            'debug' => false,
            'cache' => false,
            'msg'   => 'login error'
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

        session('admin_auth_key', null);

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
        if ($result = $this->authenticate(__METHOD__)) {
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
            ->view('role_admin', [], 'role_admin.user_id=admin.id')
            ->view('role role', ['name' => 'role_name'], 'role.id=role_admin.role_id')
            ->where([
                ['admin.id', '=', $this->uid]
            ])
            ->find()
            ->toArray();
        $result['last_login_time'] = date('Y-m-d H:i:s', $result['last_login_time']);

        return [
            'debug' => false,
            'cache' => true,
            'msg'   => 'user profile',
            'data'  => $result
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
        $file = (array)glob($this->app->getRuntimePath() . 'backup' . DIRECTORY_SEPARATOR . '*');
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
        $count = count((array)glob($this->app->getRuntimePath() . 'cache' . DIRECTORY_SEPARATOR . '*'));
        if ($count >= 2000) {
            $result[] = [
                'title' => $this->lang->get('too much junk information'),
                'url'   => url('content/cache/index')
            ];
        }

        return [
            'debug' => false,
            'cache' => true,
            'msg'   => 'user notice',
            'data'  => [
                'list'  => $result,
                'total' => count($result),
            ]
        ];
    }
}
