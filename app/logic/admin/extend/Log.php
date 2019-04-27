<?php
/**
 *
 * API接口层
 * 操作日志
 *
 * @package   NICMS
 * @category  app\logic\admin\extend
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */
declare (strict_types = 1);

namespace app\logic\admin\extend;

use think\facade\Lang;
use think\facade\Request;
use app\logic\admin\Base;
use app\model\ActionLog as ModelActionLog;

class Log extends Base
{

    /**
     * 查询
     * @access public
     * @param
     * @return array
     */
    public function query(): array
    {
        if ($result = $this->authenticate(__METHOD__)) {
            return $result;
        }

        $query_limit = (int) Request::post('limit/f', 20);

        $result =
        (new ModelActionLog)->view('action_log', ['action_id', 'user_id', 'action_ip', 'module', 'remark', 'create_time'])
        ->view('action', ['name'=>'action_name'], 'action.id=action_log.action_id')
        ->view('admin', ['username'], 'admin.id=action_log.user_id')
        ->view('role_admin', [], 'role_admin.user_id=admin.id')
        ->view('role', ['name'=>'role_name'], 'role.id=role_admin.role_id')
        ->order('action_log.create_time DESC')
        ->paginate($query_limit, false, ['path'=>'javascript:paging([PAGE]);']);

        $list = $result->toArray();
        $list['render'] = $result->render();

        $date_format = Request::post('date_format', 'Y-m-d H:i:s');
        foreach ($list['data'] as $key => $value) {
            $value['create_time'] = strtotime($value['create_time']);
            $value['create_time'] = date($date_format, $value['create_time']);
            $value['action_name'] = Lang::get($value['action_name']);
            $list['data'][$key] = $value;
        }

        return [
            'debug' => false,
            'cache' => false,
            'msg'   => 'action log data',
            'data'  => [
                'list'         => $list['data'],
                'total'        => $list['total'],
                'per_page'     => $list['per_page'],
                'current_page' => $list['current_page'],
                'last_page'    => $list['last_page'],
                'page'         => $list['render'],
            ]
        ];
    }
}