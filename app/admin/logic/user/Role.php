<?php

/**
 *
 * API接口层
 * 权限组
 *
 * @package   NICMS
 * @category  app\admin\logic\user
 * @author    失眠小枕头 [312630173@qq.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace app\admin\logic\user;

use app\common\controller\BaseLogic;
use app\common\model\Role as ModelRole;
use app\common\model\RoleAccess as ModelRoleAccess;

class Role extends BaseLogic
{
    protected $authKey = 'admin_auth_key';

    /**
     * 查询
     * @access public
     * @return array
     */
    public function query(): array
    {
        $query_page = $this->request->param('page/d', 1, 'abs');

        $result = ModelRole::where('id', '<>', 1)
            ->order('id DESC')
            ->paginate([
                'list_rows' => $this->getQueryLimit(),
                'path' => 'javascript:paging([PAGE]);',
            ], true);

        if ($result && $list = $result->toArray()) {
            $list['render'] = $result->render();

            foreach ($list['data'] as $key => $value) {
                $value['url'] = [
                    'editor' => url('user/role/editor/' . $value['id']),
                    'remove' => url('user/role/remove/' . $value['id']),
                ];
                $list['data'][$key] = $value;
            }
        }

        return [
            'debug' => false,
            'cache' => false,
            'msg'   => 'success',
            'data'  => [
                'list'         => $list['data'],
                'per_page'     => $list['per_page'],
                'current_page' => $list['current_page'],
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
        $this->actionLog('admin role added');

        $receive_data = [
            'name'       => $this->request->param('name'),
            'remark'     => $this->request->param('remark'),
            'status'     => $this->request->param('status/d', 0, 'abs'),
        ];
        if ($result = $this->validate($receive_data)) {
            return $result;
        }

        ModelRole::transaction(function () use ($receive_data) {
            $role = new ModelRole;
            $role->save($receive_data);
            $list = [];

            $node = $this->request->param('node/a');
            foreach ($node as $value) {
                $list[] = [
                    'role_id' => $role->id,
                    'node_id' => $value,
                ];
            }
            (new ModelRoleAccess)->saveAll($list);
        });

        return [
            'debug' => false,
            'cache' => false,
            'msg'   => 'success',
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
        if ($id = $this->request->param('id/d', 0, 'abs')) {
            $result = ModelRole::where('id', '=', $id)->find();
            $result = $result ? $result->toArray() : [];

            $node = ModelRoleAccess::field('node_id')
                ->where('role_id', '=', $id)
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
            'msg'   => 'success',
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
        $this->actionLog('admin role editor');

        if (!$id = $this->request->param('id/d', 0, 'abs')) {
            return [
                'debug' => false,
                'cache' => false,
                'code'  => 40001,
                'msg'   => 'error'
            ];
        }

        $receive_data = [
            'name'       => $this->request->param('name'),
            'remark'     => $this->request->param('remark'),
            'status'     => $this->request->param('status/d', 0, 'abs'),
        ];
        if ($result = $this->validate($receive_data)) {
            return $result;
        }

        ModelRole::transaction(function () use ($receive_data, $id) {
            ModelRole::where('id', '=', $id)->limit(1)->update($receive_data);

            // 删除旧数据
            ModelRoleAccess::where('role_id', '=', $id)->limit(1)->delete();

            $list = [];
            $node = $this->request->param('node/a');
            foreach ($node as $value) {
                $list[] = [
                    'role_id' => $id,
                    'node_id' => $value,
                ];
            }
            (new ModelRoleAccess)->saveAll($list);
        });

        return [
            'debug' => false,
            'cache' => false,
            'msg'   => 'success',
        ];
    }

    /**
     * 删除
     * @access public
     * @return array
     */
    public function remove()
    {
        $this->actionLog('admin role remove');

        if (!$id = $this->request->param('id/d', 0, 'abs')) {
            return [
                'debug' => false,
                'cache' => false,
                'code'  => 40001,
                'msg'   => 'error'
            ];
        }

        ModelRole::transaction(function () use ($id) {
            ModelRole::where('id', '=', $id)->limit(1)->delete();

            ModelRoleAccess::where('role_id', '=', $id)->limit(1)->delete();
        });

        return [
            'debug' => false,
            'cache' => false,
            'msg'   => 'success',
        ];
    }
}
