<?php

/**
 *
 * 控制层
 * admin
 *
 * @package   NICMS
 * @category  app\admin\controller
 * @author    失眠小枕头 [312630173@qq.com]
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
     * @return void
     */
    public function initialize()
    {
        // 操作验证权限
        $logic = $this->request->param('logic');
        $action = $this->request->param('action');
        $method = $this->request->param('method');

        // 登录状态
        if ($this->session->has($this->authKey)) {
            // 校验权限
            $result = (new Rbac)->authenticate(
                $this->session->get($this->authKey),
                'admin',
                $logic,
                $action,
                $method
            );

            // 无权限重定向后台首页
            if (false === $result) {
                $this->redirect('settings/dashboard/index');
            }
        }

        // 登录状态不再进入登录页
        elseif ($this->session->has($this->authKey) && in_array($method, ['login', 'forget'])) {
            $this->redirect('settings/dashboard/index');
        }

        // 非登录状态只能进入登录页
        elseif (!$this->session->has($this->authKey) && !in_array($method, ['login', 'forget'])) {
            $this->redirect('account/user/login');
        }

        // 初始化视图
        $this->view->config([
            'view_theme' => env('admin.theme', 'default'),
            'tpl_replace_string' => [
                '__NAME__'        => 'NICMS',
                '__FOOTER_MSG__'  => '',
                '__COPYRIGHT__'   => '',
                '__SCRIPT__'      => '',
            ]
        ]);

        $this->view->engine()->assign([
            'TITLE'       => 'NICMS',
            'KEYWORDS'    => '',
            'DESCRIPTION' => '',
            'URL'         => request()->baseUrl(true),
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
        $tpl = $logic . DIRECTORY_SEPARATOR . $action . DIRECTORY_SEPARATOR . $method;
        return $this->fetch($tpl);
    }
}
