<?php

/**
 *
 * 控制层
 *
 * @package   NICMS
 * @category  app\user\controller
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace app\user\controller;

use app\common\controller\BaseController;
use app\common\library\Siteinfo;

class Index extends BaseController
{

    protected $authKey = 'user_auth_key';

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

        }

        // 登录状态不再进入登录页
        elseif ($this->session->has($this->authKey) && in_array($_method, ['login', 'reg', 'forget'])) {
            $this->redirect('account/user/profile');
        }

        // 非登录状态只能进入登录页
        elseif (!$this->session->has($this->authKey) && !in_array($_method, ['login',  'reg', 'forget'])) {
            $this->redirect('account/user/login');
        }
    }
}
