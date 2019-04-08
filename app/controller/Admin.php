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

        $this->setTheme('admin/default');
        $tpl_path = Config::get('app.cdn_host') . '/template/admin/default/';
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

    public function index(string $logic = 'account', string $controller = 'user', string $action = 'login')
    {
        $this->__authenticate($logic, $controller, $action);

        $tpl  = $logic . DIRECTORY_SEPARATOR . $controller;
        $tpl .= $action ? DIRECTORY_SEPARATOR . $action : '';

        $this->fetch($tpl);
    }

    /**
     * 验证权限
     */
    private function __authenticate(string $_logic, string $_controller, string $_action): void
    {
        if (!in_array($_logic, ['account']) && session('?admin_auth_key')) {
            $result =
             (new Rbac)->authenticate(
                session('admin_auth_key'),
                'admin',
                $_logic,
                $_controller,
                $_action
            );
            if ($result === false) {
                $url = url('setting/info');
            }
        } elseif (in_array($_logic, ['account']) && session('?admin_auth_key')) {
            $url = url('setting/info');
        }
        if (isset($url)) {
            $response = Response::create($url, 'redirect', 302);
            throw new HttpResponseException($response);
        }

    }
}
