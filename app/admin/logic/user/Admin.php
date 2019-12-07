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
use app\common\library\Base64;
use app\common\model\Admin as ModelAdmin;
use app\common\model\Role as ModelRole;
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
            ->view('admin', ['id', 'username', 'email', 'status', 'last_login_ip', 'last_login_ip_attr', 'last_login_time'])
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
                'editor' => url('user/admin/editor/' . $value['id']),
                'remove' => url('user/admin/remove/' . $value['id']),
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
        $this->actionLog(__METHOD__, 'admin admin added');

        $receive_data = [
            'username'         => $this->request->param('username'),
            'password'         => $this->request->param('password'),
            'password_confirm' => $this->request->param('password_confirm'),
            'phone'            => $this->request->param('phone'),
            'email'            => $this->request->param('email'),
            'role_id'          => $this->request->param('role_id/d'),
            'status'           => $this->request->param('status/d'),
        ];
        if ($result = $this->validate(__METHOD__, $receive_data)) {
            return $result;
        }

        (new ModelAdmin)->transaction(function () use ($receive_data) {
            $receive_data['salt'] = Base64::flag(md5(microtime(true) . $receive_data['password']), 6);
            $receive_data['password'] = Base64::createPassword($receive_data['password'], $receive_data['salt']);

            $admin = new ModelAdmin;
            $admin->save($receive_data);

            (new ModelRoleAdmin)->save([
                'user_id' => $admin->id,
                'role_id' => $receive_data['role_id']
            ]);
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
                ->view('admin', ['id', 'username', 'phone', 'email', 'status'])
                ->view('role_admin', ['role_id'], 'role_admin.user_id=admin.id')
                ->where([
                    ['admin.id', '=', $id],
                ])
                ->find();
            $result = $result ? $result->toArray() : [];
        }

        $role = (new ModelRole)
            ->where([
                ['id', '<>', 1],
                ['status', '=', 1]
            ])
            ->select();
        $result['role_list'] = $role ? $role->toArray() : [];

        return [
            'debug' => false,
            'cache' => false,
            'msg'   => 'admin',
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
        $this->actionLog(__METHOD__, 'admin admin editor');

        if (!$id = $this->request->param('id/d')) {
            return [
                'debug' => false,
                'cache' => false,
                'code'  => 40001,
                'msg'   => '请求错误'
            ];
        }

        $receive_data = [
            'username'         => $this->request->param('username'),
            'password'         => $this->request->param('password'),
            'password_confirm' => $this->request->param('password_confirm'),
            'phone'            => $this->request->param('phone'),
            'email'            => $this->request->param('email'),
            'role_id'          => $this->request->param('role_id/d'),
            'status'           => $this->request->param('status/d'),
        ];
        if ($result = $this->validate(__METHOD__, $receive_data)) {
            return $result;
        }

        (new ModelAdmin)->transaction(function () use ($receive_data, $id) {
            $receive_data['salt'] = Base64::flag(md5(microtime(true) . $receive_data['password']), 6);
            $receive_data['password'] = Base64::createPassword($receive_data['password'], $receive_data['salt']);

            (new ModelAdmin)->where([
                ['id', '=', $id]
            ])->data([
                'username' => $receive_data['username'],
                'password' => $receive_data['password'],
                'salt' => $receive_data['salt'],
                'phone' => $receive_data['phone'],
                'email' => $receive_data['email'],
                'status' => $receive_data['status']
            ])->update();

            (new ModelRoleAdmin)->where([
                ['user_id', '=', $id]
            ])->data([
                'role_id' => $receive_data['role_id']
            ])->update();
        });

        return [
            'debug' => false,
            'cache' => false,
            'msg'   => 'admin editor success',
        ];
    }

    /**
     * 删除
     * @access public
     * @return array
     */
    public function remove()
    {
        $this->actionLog(__METHOD__, 'admin admin remove');

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
                ['user_id', '=', $id]
            ])->delete();
        });

        return [
            'debug' => false,
            'cache' => false,
            'msg'   => 'admin remove success',
        ];
    }
}
