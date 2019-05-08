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
 * @category  app\logic\admin
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */
declare (strict_types = 1);

namespace app\logic\admin;

use think\facade\Request;
use app\library\Rbac;
use app\library\Upload;
use app\model\Action as ModelAction;
use app\model\ActionLog as ModelActionLog;

class Base
{

    /**
     * 权限验证
     * @access protected
     * @param  string  $_method
     * @param  string  $_write_log
     * @return bool|array
     */
    protected function authenticate(string $_method, string $_write_log = '')
    {
        $_method = str_replace('app\logic\\', '', strtolower($_method));
        list($_method, $action) = explode('::', $_method);
        list($app, $logic, $controller) = explode('\\', $_method);

        $result = (new Rbac)
            ->authenticate(
                session('admin_auth_key'),
                $app,
                $logic,
                $controller,
                $action,
                [
                    'not_auth_action' => [
                        'login',
                        'logout',
                        'forget',
                        'auth',
                        'profile',
                        'notice'
                    ]
                ]
            );

        // 验证成功,记录操作日志
        if ($result && $_write_log) {
            $map = $app . '_' . $logic . '_' . $controller . '_' . $action;

            // 查询操作方法
            $has = (new ModelAction)
                ->where([
                    ['name', '=', $map]
                ])
                ->find();

            // 创建新操作方法
            if (is_null($has)) {
                $res = (new ModelAction)
                    ->create([
                        'name'  => $map,
                        'title' => $_write_log,
                    ]);
                $has['id'] = $res->id;
            }

            // 写入操作日志
            (new ModelActionLog)
                ->create([
                    'action_id' => $has['id'],
                    'user_id'   => session('admin_auth_key'),
                    'action_ip' => Request::ip(),
                    'module'    => 'admin',
                    'remark'    => $_write_log,
                ]);

            // 删除过期日志
            (new ModelActionLog)
                ->where([
                    ['create_time', '<=', strtotime('-180 days')]
                ])
                ->delete();
        }

        return $result ? false : [
            'debug' => false,
            'cache' => false,
            'code'  => 'error',
            'msg'   => 'auth error'
        ];
    }

    /**
     * 上传
     * @access protected
     * @param
     * @return string|array
     */
    protected function upload()
    {
        if ($result = $this->authenticate(__METHOD__, 'admin upload file')) {
            return $result;
        }

        if (Request::isPost() && !empty($_FILES)) {
            $input_name = Request::param('input_name', 'upload');
            $result = (new Upload)->save($input_name);
        } else {
            $result = 'upload error';
        }

        return $result;
    }

    /**
     * 数据验证
     * @access protected
     * @param  string  $_validate
     * @param  array   $_data
     * @return bool|string
     */
    protected function validate(string $_validate, array $_data = [])
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
                'code'  => 'error',
                'msg'   => $v->getError()
            ];
        } else {
            return false;
        }
    }
}
