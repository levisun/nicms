<?php
/**
 *
 * API接口层
 * 权限判断
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

use think\facade\Lang;
use think\facade\Log;
use think\facade\Request;
use think\facade\Response;
use app\library\Rbac;
use app\library\Upload;
use app\model\Action as ModelAction;
use app\model\ActionLog as ModelActionLog;

class Base
{

    /**
     * 构造
     */
    public function __construct()
    {
        if (!Request::isPost()) {
            return [
                'debug' => false,
                'cache' => false,
                'msg'   => 'request error'
            ];
        }
    }

    /**
     * 记录操作日志
     * @access protected
     * @param  string $_controller
     * @param  string $_action
     * @param  string $_msg
     * @return void
     */
    protected function __actionLog(string $_controller, string $_action, string $_msg = ''): void
    {
        $result =
        (new ModelAction)->where([
            ['name', '=', strtolower($_controller . '_' . $_action)]
        ])
        ->find();

        if ($result) {
            (new ModelActionLog)->save([
                'action_id'   => $result['id'],
                'user_id'     => session('?admin_auth_key') ? session('admin_auth_key') : 0,
                'action_ip'   => Request::ip(),
                'module'      => 'admin',
                'remark'      => $_msg,
            ]);
        } else {
            Log::record('未找到' . $_controller . '::' . $_action . '操作行为.', 'error');
        }

        (new ModelActionLog)->where([
            ['create_time', '<=', strtotime('-180 days')]
        ])
        ->delete();
    }

    /**
     * 验证权限
     * @access protected
     * @param  string  $_logic
     * @param  string  $_controller
     * @param  string  $_action
     * @return mexid
     */
    protected function __authenticate(string $_logic, string $_controller, string $_action)
    {
        $result =
        (new Rbac)->authenticate(
            session('admin_auth_key'),
            'admin',
            $_logic,
            $_controller,
            $_action,
            [
                'not_auth_logic' => [
                    'account'
                ]
            ]
        );

        return $result ? : [
            'debug' => false,
            'cache' => false,
            'msg'   => Lang::get('error')
        ];
    }

    /**
     * 上传
     * @access protected
     * @param
     * @return mexid
     */
    protected function __upload()
    {
        // 用户权限校验
        if (session_id() && session('?member_auth_key')) {
            $input_name = Request::post('input_name', 'upload');
            $result = (new Upload)->save($input_name);
        } else {
            $result = Lang::get('upload error');
        }

        return $result;
    }
}
