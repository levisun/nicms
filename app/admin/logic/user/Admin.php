<?php

/**
 *
 * API接口层
 * 管理员
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
        $query_page = $this->request->param('page/d', 1, 'abs');

        $result = ModelAdmin::view('admin', ['id', 'username', 'email', 'status', 'last_login_ip', 'last_login_ip_attr', 'last_login_time'])
            ->view('role_admin', ['role_id'], 'role_admin.user_id=admin.id')
            ->view('role role', ['name' => 'role_name'], 'role.id=role_admin.role_id')
            ->where('admin.id', '<>', 1)
            ->order('id DESC')
            ->paginate([
                'list_rows' => $this->getQueryLimit(),
                'path' => 'javascript:paging([PAGE]);',
            ], true);

        if ($result && $list = $result->toArray()) {
            $list['render'] = $result->render();

            $date_format = $this->request->param('date_format', 'Y-m-d H:i:s');
            foreach ($list['data'] as $key => $value) {
                $value['last_login_time'] = date($date_format, (int) $value['last_login_time']);

                $value['url'] = [
                    'editor' => url('user/admin/editor/' . $value['id']),
                    'remove' => url('user/admin/remove/' . $value['id']),
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
        $this->actionLog('admin admin added');

        $receive_data = [
            'username'         => $this->request->param('username'),
            'password'         => $this->request->param('password'),
            'password_confirm' => $this->request->param('password_confirm'),
            'phone'            => $this->request->param('phone'),
            'email'            => $this->request->param('email'),
            'role_id'          => $this->request->param('role_id/d', 0, 'abs'),
            'status'           => $this->request->param('status/d', 0, 'abs'),
        ];
        if ($result = $this->validate($receive_data)) {
            return $result;
        }

        ModelAdmin::transaction(function () use ($receive_data) {
            $receive_data['salt'] = Base64::flag(microtime(true) . $receive_data['password'], 6);
            $receive_data['password'] = Base64::createPassword($receive_data['password'], $receive_data['salt']);

            $admin = new ModelAdmin;
            $admin->save($receive_data);

            ModelRoleAdmin::create([
                'user_id' => $admin->id,
                'role_id' => $receive_data['role_id']
            ]);
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
            $result = ModelAdmin::view('admin', ['id', 'username', 'phone', 'email', 'status'])
                ->view('role_admin', ['role_id'], 'role_admin.user_id=admin.id')
                ->where('admin.id', '=', $id)
                ->find();
            $result = $result ? $result->toArray() : [];
        }

        $role = ModelRole::where('id', '<>', 1)
            ->where('status', '=', 1)
            ->select();
        $result['role_list'] = $role ? $role->toArray() : [];

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
        $this->actionLog('admin admin editor');

        if (!$id = $this->request->param('id/d', 0, 'abs')) {
            return [
                'debug' => false,
                'cache' => false,
                'code'  => 40001,
                'msg'   => 'error'
            ];
        }

        $receive_data = [
            'username'         => $this->request->param('username'),
            'password'         => $this->request->param('password'),
            'password_confirm' => $this->request->param('password_confirm'),
            'phone'            => $this->request->param('phone'),
            'email'            => $this->request->param('email'),
            'role_id'          => $this->request->param('role_id/d', 0, 'abs'),
            'status'           => $this->request->param('status/d', 0, 'abs'),
        ];
        if ($result = $this->validate($receive_data)) {
            return $result;
        }

        ModelAdmin::transaction(function () use ($receive_data, $id) {
            $receive_data['salt'] = Base64::flag(microtime(true) . $receive_data['password'], 6);
            $receive_data['password'] = Base64::createPassword($receive_data['password'], $receive_data['salt']);

            ModelAdmin::where('id', '=', $id)->limit(1)->update([
                'username' => $receive_data['username'],
                'password' => $receive_data['password'],
                'salt'     => $receive_data['salt'],
                'phone'    => $receive_data['phone'],
                'email'    => $receive_data['email'],
                'status'   => $receive_data['status']
            ]);

            ModelRoleAdmin::where('user_id', '=', $id)->limit(1)->update([
                'role_id' => $receive_data['role_id']
            ]);
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
        $this->actionLog('admin admin remove');

        if (!$id = $this->request->param('id/d', 0, 'abs')) {
            return [
                'debug' => false,
                'cache' => false,
                'code'  => 40001,
                'msg'   => 'error'
            ];
        }

        ModelAdmin::transaction(function () use ($id) {
            ModelAdmin::where('id', '=', $id)->limit(1)->delete();
            ModelRoleAdmin::where('user_id', '=', $id)->limit(1)->delete();
        });

        return [
            'debug' => false,
            'cache' => false,
            'msg'   => 'success',
        ];
    }
}
