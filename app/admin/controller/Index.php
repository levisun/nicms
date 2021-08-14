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
            $result = (new Rbac)->setUserId($this->session->get($this->authKey))
                ->setAppName('admin')
                ->authenticate($logic, $action, $method);

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
        $view_path = 'theme/' . app('http')->getName() . '/' . env('admin.theme', 'default') . '/';
        $url  = config('app.static_host') . str_replace('theme/', '', $view_path);
        $this->view->config([
            'view_path' => $view_path,
            'tpl_replace_string' => [
                '__APP_NAME__'    => config('app.app_name'),
                '__STATIC__'      => config('app.static_host') . 'static/',
                '__URL__'         => $this->request->baseUrl(true),
                '__LANG__'        => app('lang')->getLangSet(),
                '__API_HOST__'    => config('app.api_host'),
                '__IMG_HOST__'    => config('app.img_host'),
                '__STATIC_HOST__' => config('app.static_host'),

                '__NAME__'        => 'NICMS',
                '__FOOTER_MSG__'  => '',
                '__COPYRIGHT__'   => '',
                '__SCRIPT__'      => '',

                '__THEME__'       => $url,
                '__CSS__'         => $url . 'css/',
                '__IMG__'         => $url . 'img/',
                '__JS__'          => $url . 'js/',
            ]
        ]);

        $this->view->engine()->assign([
            'TITLE'       => 'NICMS',
            'KEYWORDS'    => '',
            'DESCRIPTION' => '',
            'URL'         => $this->request->baseUrl(true),
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
