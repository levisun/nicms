<?php

/**
 *
 * 控制层
 * admin
 *
 * @package   NICMS
 * @category  app\admin\controller
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\BaseController;
use app\common\library\Rbac;

class Index extends BaseController
{

    protected $authKey = 'admin_auth_key';

    /**
     * 初始化
     * @access public
     * @param
     * @return void
     */
    public function initialize()
    {
        $this->view->config([
            'view_theme' => $this->app->env->get('admin.theme', 'default')
        ]);
    }

    /**
     * 主页
     * @access public
     * @param  string $service
     * @param  string $logic
     * @param  string $action
     * @return
     */
    public function index(string $logic = 'account', string $action = 'user', string $method = 'login')
    {
        $this->authenticate($logic, $action, $method);

        $tpl = $logic . DIRECTORY_SEPARATOR . $action . DIRECTORY_SEPARATOR . $method;
        return $this->fetch($tpl);
    }

    /**
     * 操作验证权限
     * @access private
     * @param  string $_logic   业务层
     * @param  string $_action  控制器
     * @param  string $_method  方法
     * @return void
     */
    protected function authenticate(string &$_logic, string &$_action, string &$_method): void
    {
        // 登录状态
        if ($this->session->has($this->authKey)) {
            // 校验权限
            $result = Rbac::authenticate(
                $this->session->get($this->authKey),
                'admin',
                $_logic,
                $_action,
                $_method
            );

            // 无权限重定向后台首页
            if (false === $result) {
                $this->redirect('settings/dashboard/index');
            }
        }

        // 登录状态不再进入登录页
        elseif ($this->session->has($this->authKey) && in_array($_method, ['login', 'forget'])) {
            $this->redirect('settings/dashboard/index');
        }

        // 非登录状态只能进入登录页
        elseif (!$this->session->has($this->authKey) && !in_array($_method, ['login', 'forget'])) {
            $this->redirect('account/user/login');
        }
    }
}
