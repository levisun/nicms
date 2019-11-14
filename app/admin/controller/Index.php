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

    /**
     * 初始化
     * @access public
     * @param
     * @return void
     */
    public function initialize()
    {
        $this->view->config([
            'app_name'   => 'admin',
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
    protected function authenticate(string $_logic, string $_action, string $_method): void
    {
        if ($this->session->has('admin_auth_key')) {
            $result = (new Rbac)->authenticate(
                $this->session->get('admin_auth_key'),
                'admin',
                $_logic,
                $_action,
                $_method
            );

            if (false === $result) {
                $this->redirect('settings/dashboard/index');
            }
        }

        elseif ($this->session->has('admin_auth_key') && $_logic === 'account') {
            $this->redirect('settings/dashboard/index');
        }

        elseif (!$this->session->has('admin_auth_key') && !in_array($_method, ['login', 'forget'])) {
            $this->redirect('account/user/login');
        }
    }
}
