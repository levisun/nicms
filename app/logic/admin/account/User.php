<?php
/**
 *
 * API接口层
 * 权限判断
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

use think\facade\Config;
use think\facade\Lang;
use think\facade\Request;
use app\library\Base64;
use app\library\Ip;
use app\logic\admin\Base;
use app\model\Admin as ModelAdmin;

// extends Base
class User
{

    public function login(): array
    {
        if (is_file(app()->getRuntimePath() . md5(Request::ip(). date('Y-m-d')) . '.lock') &&
            filemtime(app()->getRuntimePath() . md5(Request::ip(). date('Y-m-d')) . '.lock') >= strtotime('-1 days')) {
            clearstatcache();
            return [
                'debug' => false,
                'msg'   => Lang::get('username or password error')
            ];
        }

        $admin =
        ModelAdmin::where([
            ['username', '=', Request::post('username')]
        ])
        ->find();

        if ($admin && $admin['password'] === Base64::password(Request::post('password'), $admin['salt'])) {
            $ip = Ip::info();
            ModelAdmin::where([
                ['id', '=', $admin['id']]
            ])
            ->data([
                'last_login_time'    => time(),
                'last_login_ip'      => $ip['ip'],
                'last_login_ip_attr' => $ip['province_id'] ? $ip['province'] . $ip['city'] . $ip['area'] : ''
            ])
            ->update();

            return [
                'debug' => false,
                'msg'   => Lang::get('login success'),
                'data'  => $ip
            ];
        } else {
            if (session('?login_lock')) {
                $lock = session('login_lock');
                session('login_lock', ++$lock);
                if ($lock >= 5) {
                    $ip = Ip::info();
                    file_put_contents(
                        app()->getRuntimePath() . md5(Request::ip() . date('Y-m-d')) . '.lock',
                        json_encode([
                            'date'  => date('Y-m-d H:i:s'),
                            'ip'    => $ip,
                            'agent' => Request::header('USER-AGENT')
                        ])
                    );
                }
            } else {
                session('login_lock', 1);
            }

            return [
                'debug' => false,
                'msg'   => Lang::get('username or password error')
            ];
        }
    }

    public function logout(): array
    {
        # code...
    }

    public function forget(): array
    {
        # code...
    }
}
