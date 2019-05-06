<?php
/**
 *
 * API接口层
 * 用户登录
 *
 * @package   NICMS
 * @category  app\logic\admin\account
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */
declare (strict_types = 1);

namespace app\logic\admin\account;

use think\facade\Lang;
use think\facade\Request;
use think\facade\Session;
use app\library\Base64;
use app\library\Ip;
use app\library\Rbac;
use app\library\Session as LibSession;
use app\logic\admin\Base;
use app\model\Admin as ModelAdmin;

class User extends Base
{

    /**
     * 登录
     * @access public
     * @param
     * @return array
     */
    public function login(): array
    {
        if ($result = $this->validate(__METHOD__)) {
            return $result;
        }

        $lock = app()->getRuntimePath() . md5(Request::ip() . date('YmdH')) . '.lock';
        clearstatcache();
        if (is_file($lock)) {
            $result = 'login lock';
        } else {
            $result =
            (new ModelAdmin)->where([
                ['username', '=', Request::param('username')]
            ])
            ->find();

            if ($result && $result['password'] === Base64::password(Request::param('password'), $result['salt'])) {
                $ip = (new Ip)->info();
                (new ModelAdmin)->where([
                    ['id', '=', $result['id']]
                ])
                ->data([
                    'flag'               => Session::getId(false),
                    'last_login_time'    => time(),
                    'last_login_ip'      => $ip['ip'],
                    'last_login_ip_attr' => $ip['province_id'] ? $ip['province'] . $ip['city'] . $ip['area'] : ''
                ])
                ->update();

                if ($result['flag'] && $result['flag'] !== Session::getId(false)) {
                    (new LibSession)->delete($result['flag']);
                }

                session('admin_auth_key', $result['id']);
                session('login_lock', null);

                if ($result = $this->authenticate(__METHOD__, 'admin user login')) {
                    return $result;
                }
                $result = 'login success';
            }
            else {
                $login_lock = session('?login_lock') ? session('login_lock') : 0;
                ++$login_lock;
                if ($login_lock >= 5) {
                    session('login_lock', null);
                    file_put_contents($lock, 'lock');
                } else {
                    session('login_lock', $login_lock);
                }
                $result = 'login error';
            }
        }

        return [
            'debug' => false,
            'cache' => false,
            'msg'   => $result
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
            'msg'   => Lang::get('logout success')
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

        $result = (new Rbac)->getAuth(session('admin_auth_key'));
        $result = $result['admin'];
        foreach ($result as $key => $value) {
            $result[$key] = [
                'name' => $key,
                'lang' => Lang::get('auth ' . $key),
            ];
            foreach ($value as $k => $val) {
                $result[$key]['child'][$k] = [
                    'name' => $k,
                    'lang' => Lang::get('auth ' . $k),
                    'url'  => url($key . '/' . $k . '/index')
                ];
            }
        }

        return [
            'debug' => false,
            // 'cache' => false,
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

        $result =
        (new ModelAdmin)->view('admin', ['id', 'username', 'email', 'last_login_ip', 'last_login_ip_attr', 'last_login_time'])
        ->view('role_admin', [], 'role_admin.user_id=admin.id')
        ->view('role role', ['name'=>'role_name'], 'role.id=role_admin.role_id')
        ->where([
            ['admin.id', '=', session('admin_auth_key')]
        ])
        ->find();
        $result['last_login_time'] = date('Y-m-d H:i:s', $result['last_login_time']);

        return [
            'debug' => false,
            // 'cache' => false,
            'msg'   => 'user author',
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
        $file = (array) glob(app()->getRuntimePath() . 'backup' . DIRECTORY_SEPARATOR . '*');
        if (count($file) >= 2) {
            foreach ($file as $key => $value) {
                if (filectime($value) >= strtotime('-7 days')) {
                    $status = true;
                    continue;
                }
            }
        } else{
            $status = false;
        }
        if ($status === false) {
            $result[] = [
                'title' => Lang::get('please make a database backup'),
                'url'   => url('expand/database/index')
            ];
        }

        // 错误日志
        if (is_file(app()->getRuntimePath() . 'log' . DIRECTORY_SEPARATOR . date('Ymd') . '_error.log')) {
            $result[] = [
                'title' => Lang::get('program error message'),
                'url'   => url('expand/elog/index')
            ];
        }

        // 垃圾信息
        $file = (array) glob(app()->getRuntimePath() . 'cache' . DIRECTORY_SEPARATOR . '*');
        $count = 0;
        foreach ($file as $key => $value) {
            $value = (array) glob($value . DIRECTORY_SEPARATOR . '*');
            $count += count($value);
        }
        if ($count >= 2000) {
            $result[] = [
                'title' => Lang::get('too much junk information'),
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
