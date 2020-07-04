<?php

/**
 *
 * API接口层
 * 操作日志
 *
 * @package   NICMS
 * @category  app\admin\logic\extend
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace app\admin\logic\extend;

use app\common\controller\BaseLogic;
use app\common\model\ActionLog as ModelActionLog;

class Log extends BaseLogic
{
    protected $authKey = 'admin_auth_key';

    /**
     * 查询
     * @access public
     * @return array
     */
    public function query(): array
    {
        $query_limit = $this->request->param('limit/d', 20, 'abs');

        $result = ModelActionLog::view('action_log', ['action_id', 'user_id', 'action_ip', 'module', 'remark', 'create_time'])
            ->view('action', ['name' => 'action_name'], 'action.id=action_log.action_id')
            ->view('admin', ['username'], 'admin.id=action_log.user_id')
            ->view('role_admin', [], 'role_admin.user_id=admin.id')
            ->view('role', ['name' => 'role_name'], 'role.id=role_admin.role_id')
            ->order('action_log.create_time DESC')
            ->paginate([
                'list_rows' => $query_limit,
                'path' => 'javascript:paging([PAGE]);',
            ]);

        $list = $result->toArray();
        $list['total'] = number_format($list['total']);
        $list['render'] = $result->render();

        $date_format = $this->request->param('date_format', 'Y-m-d H:i:s');
        foreach ($list['data'] as $key => $value) {
            $value['create_time'] = date($date_format, (int) $value['create_time']);
            $value['action_name'] = $this->lang->get($value['action_name']);
            unset($value['action_id'], $value['user_id']);
            $list['data'][$key] = $value;
        }

        return [
            'debug' => false,
            'cache' => false,
            'msg'   => 'success',
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
