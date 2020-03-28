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

    /**
     * 初始化
     * @access public
     * @return void
     */
    public function initialize()
    {
        $result = Siteinfo::query('user');
        $this->view->config([
            'view_theme' => $result['theme'],
            'tpl_replace_string' => [
                '__NAME__'        => $result['name'],
                '__TITLE__'       => $result['title'],
                '__KEYWORDS__'    => $result['keywords'],
                '__DESCRIPTION__' => $result['description'],
                '__FOOTER_MSG__'  => $result['footer'],
                '__COPYRIGHT__'   => $result['copyright'],
                '__SCRIPT__'      => $result['script'],
            ]
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
        if ($this->session->has('user_auth_key')) {
            // $this->redirect('settings/dashboard/index');
        }

        elseif ($this->session->has('user_auth_key') && $_logic === 'account') {
            // $this->redirect('settings/dashboard/index');
        }

        elseif (!$this->session->has('user_auth_key') && !in_array($_method, ['login', 'reg', 'forget'])) {
            $this->redirect('account/user/login');
        }
    }
}
