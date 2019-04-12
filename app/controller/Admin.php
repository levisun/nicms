<?php
/**
 *
 * 控制层
 * admin
 *
 * @package   NICMS
 * @category  app\controller
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */
declare (strict_types = 1);

namespace app\controller;

use think\Response;
use think\exception\HttpResponseException;
use think\facade\Config;
use think\facade\Env;
use think\facade\Lang;
use app\library\Rbac;
use app\library\Template;

class admin extends Template
{

    /**
     * 构造方法
     * @access public
     * @param  App  $app  应用对象
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->setTheme('admin/' . Env::get('app.admin_theme', 'default'));
        $tpl_path = Config::get('app.cdn_host') . '/template/admin/' . Env::get('app.admin_theme', 'default') . '/';
        $this->setReplace([
            'theme' => $tpl_path,
            'css'   => $tpl_path . 'css/',
            'img'   => $tpl_path . 'img/',
            'js'    => $tpl_path . 'js/',
        ]);

        // 开启session
        $session = Config::get('session');
        $session['auto_start'] = true;
        Config::set($session, 'session');
        session_start();
        session_write_close();
    }

    /**
     * 主页
     * @access public
     * @param  string $_logic
     * @param  string $_controller
     * @param  string $_action
     * @return void
     */
    public function index(string $logic = 'account', string $controller = 'user', string $action = 'login')
    {
        $this->__authenticate($logic, $controller, $action);

        $tpl  = $logic . DIRECTORY_SEPARATOR . $controller;
        $tpl .= $action ? DIRECTORY_SEPARATOR . $action : '';

        $this->fetch($tpl);
    }

    /**
     * 验证权限
     * @access private
     * @param  string $_logic
     * @param  string $_controller
     * @param  string $_action
     * @return void
     */
    private function __authenticate(string $_logic, string $_controller, string $_action): void
    {
        if (in_array($_logic, ['account']) && session('?admin_auth_key')) {
            $result = url('settings/info/index');
        } elseif (session('?admin_auth_key')) {
            $RBAC = new Rbac;
            $result =
            $RBAC->authenticate(
                session('admin_auth_key'),
                'admin',
                $_logic,
                $_controller,
                $_action,
                [
                    'not_auth_logic' => [
                        'account'
                    ]
                ]
            );
            $result = $result ? : url('settings/info/index');

            if ($result === true) {
                $auth = $RBAC->getAuth(session('admin_auth_key'));
                $auth = $auth['admin'];
                foreach ($auth as $key => $value) {
                    $auth[$key] = [
                        'name' => $key,
                        'lang' => Lang::get('auth ' . $key),
                    ];
                    foreach ($value as $k => $val) {
                        $auth[$key]['child'][$k] = [
                            'name' => $k,
                            'lang' => Lang::get('auth ' . $k),
                            'url'  => url($key . '/' . $k . '/index')
                        ];
                    }
                }
                $this->assign(['auth' => json_encode($auth)]);
            }
        } elseif (!in_array($_logic, ['account'])) {
            $result = url('account/user/login');
        }

        if (isset($result) && is_string($result)) {
            $response = Response::create($result, 'redirect', 302);
            throw new HttpResponseException($response);
        }
    }
}
