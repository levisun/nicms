<?php
/**
 *
 * 常用方法
 * __validate     验证器
 * __writeLog     记录操作日志方法
 * __authenticate 权限验证方法
 * __upload       上传方法
 *
 * @package   NICMS
 * @category  app\library
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */
declare (strict_types = 1);

namespace app\library;

use think\facade\Request;
use app\library\Rbac;
use app\library\Upload;
use app\model\Action as ModelAction;
use app\model\ActionLog as ModelActionLog;

class Common
{

    /**
     * 构造
     */
    public function __construct()
    {}

    /**
     * 数据验证
     * @access protected
     * @param  string  $_validate
     * @param  array   $_data
     * @return bool|string
     */
    protected function __validate(string $_validate, array $_data = [])
    {
        $_validate = str_replace('app\logic\\', '', strtolower($_validate));
        list($_validate) = explode('::', $_validate, 2);

        // 支持场景
        if (false !== strpos($_validate, '.')) {
            list($_validate, $scene) = explode('.', $_validate);
        }

        $class = app()->parseClass('validate', $_validate);
        $v     = new $class;

        if (!empty($scene)) {
            $v->scene($scene);
        }

        $_data = !empty($_data) ? $_data : Request::param();

        if (false === $v->batch(false)->failException(false)->check($_data)) {
            return [
                'debug' => false,
                'cache' => false,
                'msg'   => $v->getError()
            ];
        } else {
            return false;
        }
    }

    /**
     * 记录操作日志
     * @access protected
     * @param  string $_method
     * @param  string $_msg
     * @param  string $_author
     * @return void
     */
    protected function __writeLog(string $_method, string $_msg, string $_auth): void
    {
        $_method = str_replace('app\logic\\', '', strtolower($_method));
        list($_method, $action) = explode('::', $_method);
        list($app, $logic, $controller) = explode('\\', $_method);

        $map = $app . '_' . $logic . '_' . $controller . '_' . $action;
        unset($app, $logic, $controller, $action, $m);

        $result =
        (new ModelAction)->where([
            ['name', '=', $map]
        ])
        ->find();

        if (is_null($result)) {
            $res = (new ModelAction)->create([
                'name'  => $map,
                'title' => $_msg,
            ]);

            $result['id'] = $res->id;
        }

        (new ModelActionLog)->create([
            'action_id'   => $result['id'],
            'user_id'     => session('?' . $_auth) ? session($_auth) : 0,
            'action_ip'   => Request::ip(),
            'module'      => 'admin',
            'remark'      => $_msg,
        ]);

        (new ModelActionLog)->where([
            ['create_time', '<=', strtotime('-180 days')]
        ])
        ->delete();
    }

    /**
     * 权限验证
     * @access protected
     * @param  string $_method
     * @param  string $_author
     * @return bool|array
     */
    protected function __authenticate(string $_method, string $_auth)
    {
        $_method = str_replace('app\logic\\', '', strtolower($_method));
        list($_method, $action) = explode('::', $_method);
        list($app, $logic, $controller) = explode('\\', $_method);

        $result =
        (new Rbac)->authenticate(
            session($_auth),
            $app,
            $logic,
            $controller,
            $action,
            [
                'not_auth_action' => [
                    'auth',
                    'profile',
                    'notice'
                ]
            ]
        );

        return $result ? false : [
            'debug' => false,
            'cache' => false,
            'msg'   => 'auth error'
        ];
    }

    /**
     * 上传
     * @access protected
     * @param  string $_author
     * @return string|array
     */
    protected function __upload($_auth)
    {
        // 用户权限校验
        if (Request::isPost() && !empty($_FILES) && session('?' . $_auth)) {
            $input_name = Request::param('input_name', 'upload');
            $result = (new Upload)->save($input_name);
        } else {
            $result = 'upload error';
        }

        return $result;
    }
}
