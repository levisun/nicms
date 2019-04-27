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
        $this->setTheme('admin/' . Env::get('app.admin_theme', 'default'));
        parent::__construct();
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
        $this->authenticate($logic, $controller, $action);

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
    private function authenticate(string $_logic, string $_controller, string $_action): void
    {
        if (session('?admin_auth_key')) {
            $result =
            (new Rbac)->authenticate(
                session('admin_auth_key'),
                'admin',
                $_logic,
                $_controller,
                $_action
            );

            if (false === $result) {
                $url = url('settings/info/index');
            }
        }
        elseif (session('?admin_auth_key') && $_logic === 'account') {
            $url = url('settings/info/index');
        }
        elseif (!session('?admin_auth_key') && !in_array($_action, ['login', 'forget'])) {
            $url = url('account/user/login');
        }

        if (isset($url)) {
            $response = Response::create($url, 'redirect', 302);
            throw new HttpResponseException($response);
        }
    }
}
