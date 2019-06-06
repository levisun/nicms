<?php
/**
 *
 * API接口层
 * 基础方法
 *     $this->authenticate(__METHOD__, ?操作日志) 权限验证
 *     $this->upload() 上传方法
 *     $this->validate(验证器, ?数据) 验证方法
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

use think\App;
use think\Response;
use think\exception\HttpResponseException;
use app\library\Rbac;
use app\library\Template;

abstract class BaseController extends Template
{

    /**
     * 构造方法
     * @access public
     * @param  App  $app  应用对象
     * @return void
     */
    public function __construct(App $_app)
    {
        // 控制器初始化
        $this->initialize();

        parent::__construct($_app);
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
        if (empty($_str) || preg_match('/[^A-Za-z]+/si', $_str)) {
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
