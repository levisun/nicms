<?php

/**
 *
 * API接口层
 * 权限节点
 *
 * @package   NICMS
 * @category  app\admin\logic\user
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace app\admin\logic\user;

use app\common\controller\BaseLogic;
use app\common\model\Admin as ModelAdmin;
use app\common\model\RoleAdmin as ModelRoleAdmin;

class Admin extends BaseLogic
{
    protected $authKey = 'admin_auth_key';

    /**
     * 查询
     * @access public
     * @return array
     */
    public function query(): array
    {
        $query_limit = $this->request->param('limit/d', 10);

        $result = (new ModelAdmin)
            ->view('admin', ['id', 'username', 'email', 'last_login_ip', 'last_login_ip_attr', 'last_login_time'])
            ->view('role_admin', ['role_id'], 'role_admin.user_id=admin.id')
            ->view('role role', ['name' => 'role_name'], 'role.id=role_admin.role_id')
            ->where([
                ['admin.id', '<>', 1]
            ])
            ->order('id DESC')
            ->paginate([
                'list_rows' => $query_limit,
                'path' => 'javascript:paging([PAGE]);',
            ]);

        $list = $result->toArray();
        $list['render'] = $result->render();

        foreach ($list['data'] as $key => $value) {
            $value['url'] = [
                'editor' => url('user/role/editor/' . $value['id']),
                'remove' => url('user/role/remove/' . $value['id']),
            ];
            $list['data'][$key] = $value;
        }

        return [
            'debug' => false,
            'cache' => false,
            'msg'   => 'node data',
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

    /**
     * 添加
     * @access public
     * @return array
     */
    public function added(): array
    {
        $this->actionLog(__METHOD__, 'admin role added');

        $receive_data = [
            'name'       => $this->request->param('name'),
            'remark'     => $this->request->param('remark'),
            'status'     => $this->request->param('status/d'),
        ];
        if ($result = $this->validate(__METHOD__, $receive_data)) {
            return $result;
        }

        (new ModelAdmin)->transaction(function () use ($receive_data) {
            $admin = new ModelAdmin;
            $admin->save($receive_data);

            // (new ModelRoleAdmin)->saveAll($list);
        });

        return [
            'debug' => false,
            'cache' => false,
            'msg'   => 'role added success',
        ];
    }

    /**
     * 查询
     * @access public
     * @return array
     */
    public function find()
    {
        $result = [];
        if ($id = $this->request->param('id/d')) {
            $result = (new ModelAdmin)
                ->where([
                    ['id', '=', $id],
                ])
                ->find();
            $result = $result ? $result->toArray() : [];

            $node = (new ModelRoleAdmin)
                ->field('node_id')
                ->where([
                    ['role_id', '=', $id]
                ])
                ->order('node_id ASC')
                ->select();
            if ($node = $node->toArray()) {
                foreach ($node as $value) {
                    $result['node'][] = $value['node_id'];
                }
            }
        }

        return [
            'debug' => false,
            'cache' => false,
            'msg'   => 'role',
            'data'  => $result
        ];
    }

    /**
     * 编辑
     * @access public
     * @return array
     */
    public function editor()
    {
        $this->actionLog(__METHOD__, 'admin role editor');

        if (!$id = $this->request->param('id/d')) {
            return [
                'debug' => false,
                'cache' => false,
                'code'  => 40001,
                'msg'   => '请求错误'
            ];
        }

        $receive_data = [
            'name'       => $this->request->param('name'),
            'remark'     => $this->request->param('remark'),
            'status'     => $this->request->param('status/d'),
        ];
        if ($result = $this->validate(__METHOD__, $receive_data)) {
            return $result;
        }

        (new ModelAdmin)->transaction(function () use ($receive_data, $id) {
            (new ModelAdmin)->where([
                ['id', '=', $id]
            ])
                ->data($receive_data)
                ->update();
            // 删除旧数据
            (new ModelRoleAdmin)->where([
                ['role_id', '=', $id]
            ])->delete();
            $list = [];
            $node = $this->request->param('node/a');
            foreach ($node as $value) {
                $list[] = [
                    'role_id' => $id,
                    'node_id' => $value,
                ];
            }
            (new ModelRoleAdmin)->saveAll($list);
        });

        return [
            'debug' => false,
            'cache' => false,
            'msg'   => 'role editor success',
        ];
    }

    /**
     * 删除
     * @access public
     * @return array
     */
    public function remove()
    {
        $this->actionLog(__METHOD__, 'admin role remove');

        if (!$id = $this->request->param('id/d')) {
            return [
                'debug' => false,
                'cache' => false,
                'code'  => 40001,
                'msg'   => '请求错误'
            ];
        }

        (new ModelAdmin)->transaction(function () use ($id) {
            (new ModelAdmin)->where([
                ['id', '=', $id]
            ])
                ->delete();
            (new ModelRoleAdmin)->where([
                ['role_id', '=', $id]
            ])->delete();
        });

        return [
            'debug' => false,
            'cache' => false,
            'msg'   => 'role remove success',
        ];
    }
}
