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

use think\facade\Env;
use think\exception\HttpResponseException;
use app\controller\BaseController;
use app\library\Rbac;

class admin extends BaseController
{

    /**
     * 初始化
     * @access public
     * @param
     * @return void
     */
    public function initialize()
    {
        $theme = $this->config->get('app.cdn_host') . '/view/admin/' . Env::get('admin.theme', 'default') . '/';
        $this->setTheme(Env::get('admin.theme', 'default'))
            ->setReplace([
                '__THEME__'       => $theme,
                '__CSS__'         => $theme . 'css/',
                '__IMG__'         => $theme . 'img/',
                '__JS__'          => $theme . 'js/',
                '__NAME__'        => 'nicms',
                '__TITLE__'       => 'nicms',
                '__KEYWORDS__'    => 'nicms',
                '__DESCRIPTION__' => 'nicms',
                '__BOTTOM_MSG__'  => 'nicms',
                '__COPYRIGHT__'   => 'nicms',
            ]);
    }

    /**
     * 主页
     * @access public
     * @param  string $service
     * @param  string $logic
     * @param  string $action
     * @return void
     */
    public function index(string $service = 'account', string $logic = 'user', string $action = 'login')
    {
        $this->authenticate($service, $logic, $action);

        $tpl  = $service . DIRECTORY_SEPARATOR . $logic . DIRECTORY_SEPARATOR . $action;

        $this->fetch($tpl);
    }

    /**
     * 操作验证权限
     * @access private
     * @param  string $_service 业务层
     * @param  string $_logic   控制器
     * @param  string $_action  方法
     * @return void
     */
    protected function authenticate(string $_service, string $_logic, string $_action): void
    {
        if ($this->session->has('admin_auth_key')) {
            $result = (new Rbac)->authenticate(
                $this->session->get('admin_auth_key'),
                'admin',
                $_service,
                $_logic,
                $_action
            );

            if (false === $result) {
                $url = url('settings/info/index');
            }
        } elseif ($this->session->has('admin_auth_key') && $_service === 'account') {
            $url = url('settings/info/index');
        } elseif (!$this->session->has('admin_auth_key') && !in_array($_action, ['login', 'forget'])) {
            $url = url('account/user/login');
        }

        if (isset($url)) {
            $response = $this->response->create($url, 'redirect', 302);
            throw new HttpResponseException($response);
        }
    }
}
