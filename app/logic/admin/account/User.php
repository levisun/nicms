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
use app\library\Base64;
use app\library\Ip;
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
        $result = $this->__authenticate('account', 'user', 'login');
        if ($result !== true) {
            return $result;
        }

        $result = validate('admin.login');
        if ($result === true || true) {
            $lock = app()->getRuntimePath() . md5(Request::ip() . date('Ymd')) . '.lock';
            clearstatcache();
            if (!is_file($lock)) {
                $result =
                ModelAdmin::where([
                    ['username', '=', Request::post('username')]
                ])
                ->find();

                if ($result && $result['password'] === Base64::password(Request::post('password'), $result['salt'])) {
                    $ip = Ip::info();
                    ModelAdmin::where([
                        ['id', '=', $result['id']]
                    ])
                    ->data([
                        'last_login_time'    => time(),
                        'last_login_ip'      => $ip['ip'],
                        'last_login_ip_attr' => $ip['province_id'] ? $ip['province'] . $ip['city'] . $ip['area'] : ''
                    ])
                    ->update();
                    session('admin_auth_key', $result['id']);
                    $result = Lang::get('login success');
                } else {
                    $login_lock = session('?login_lock') ? session('login_lock') : 0;
                    ++$login_lock;
                    if ($login_lock >= 5) {
                        session('login_lock', null);
                        file_put_contents($lock, json_encode([
                            'date'  => date('Y-m-d H:i:s'),
                            'ip'    => Request::ip(),
                            'agent' => Request::header('USER-AGENT')
                        ]));
                    } else {
                        session('login_lock', $login_lock);
                    }
                    $result = Lang::get('username or password error');
                }
            } else {
                $result = Lang::get('username or password error');
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
        session('admin_auth_key', null);
        return [
            'debug' => false,
            'cache' => false,
            'msg'   => Lang::get('logout success')
        ];
    }

    public function forget(): array
    {
        # code...
    }

    /**
     * 用户信息
     * @access public
     * @param
     * @return array
     */
    public function profile(): array
    {
        if (session('?admin_auth_key')) {
            $result =
            ModelAdmin::view('admin admin', ['id', 'username', 'email', 'last_login_ip', 'last_login_ip_attr', 'last_login_time'])
            ->view('role_admin role_admin', [], 'role_admin.user_id=admin.id')
            ->view('role role', ['name'=>'role_name'], 'role.id=role_admin.role_id')
            ->where([
                ['admin.id', '=', session('admin_auth_key')]
            ])
            ->find();
            $result['last_login_time'] = date('Y-m-d H:i:s', $result['last_login_time']);
        } else {
            $result = null;
        }

        return [
            'debug' => false,
            'cache' => false,
            'msg'   => Lang::get('user author'),
            'data'  => $result
        ];
    }
}
