<?php
/**
 *
 * 应用公共文件
 *
 * @package   NICMS
 * @category  app
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare (strict_types = 1);

namespace app;

use think\App;
use think\Response;
use think\exception\HttpResponseException;
use think\facade\Config;
use app\library\Rbac;
use app\library\Template;

/**
 * 控制器基础类
 */
abstract class BaseController extends Template
{
    /**
     * 应用实例
     * @var \think\App
     */
    protected $app;

    /**
     * Request实例
     * @var \think\Request
     */
    protected $request;

    /**
     * 构造方法
     * @access public
     * @param  App  $app  应用对象
     * @return void
     */
    public function __construct(App $app)
    {
        $this->app     = $app;
        $this->request = $this->app->request;

        $this->app->debug(Config::get('app.debug'));

        // 控制器初始化
        $this->initialize();

        parent::__construct();
    }

    // 初始化
    protected function initialize()
    { }

    /**
     * 校验请求参数合法性
     * @access protected
     * @param  string $_str
     * @return void
     */
    protected function verification(string $_str): void
    {
        if ($_str && preg_match('/[0-9]+/si', $_str)) {
            $response = Response::create(url('404'), 'redirect', 302);
            throw new HttpResponseException($response);
        }
    }

    /**
     * 操作验证权限
     * @access private
     * @param  string $_auth_key    认证ID
     * @param  string $_method      模块
     * @param  string $_logic       业务层
     * @param  string $_controller  控制器
     * @param  string $_action      方法
     * @return void
     */
    protected function authenticate(string $_auth_key, string $_method, string $_logic, string $_controller, string $_action): void
    {
        if (session('?' . $_auth_key)) {
            $result = (new Rbac)->authenticate(
                    session($_auth_key),
                    $_method,
                    $_logic,
                    $_controller,
                    $_action
                );

            if (false === $result) {
                $url = url('settings/info/index');
            }
        } elseif (session('?' . $_auth_key) && $_logic === 'account') {
            $url = url('settings/info/index');
        } elseif (!session('?' . $_auth_key) && !in_array($_action, ['login', 'forget'])) {
            $url = url('account/user/login');
        }

        if (isset($url)) {
            $response = Response::create($url, 'redirect', 302);
            throw new HttpResponseException($response);
        }
    }
}
