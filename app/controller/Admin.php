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
use app\controller\BaseController;

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
     * @param  string $_logic
     * @param  string $_controller
     * @param  string $_action
     * @return void
     */
    public function index(string $logic = 'account', string $controller = 'user', string $action = 'login')
    {
        $this->verification($logic);
        $this->verification($controller);
        $this->verification($action);

        $this->authenticate('admin_auth_key', 'admin', $logic, $controller, $action);

        $tpl  = $logic . DIRECTORY_SEPARATOR . $controller . DIRECTORY_SEPARATOR . $action;

        $this->fetch($tpl);
    }
}
