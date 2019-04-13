<?php
/**
 *
 * API接口层
 * 用户权限
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
use app\library\Rbac;
use app\logic\admin\Base;

class Auth extends Base
{

    /**
     * 权限
     * @access public
     * @param
     * @return array
     */
    public function query(): array
    {
        if (session('?admin_auth_key')) {
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
