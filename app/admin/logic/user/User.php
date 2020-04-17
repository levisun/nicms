<?php

/**
 *
 * API接口层
 * 用户
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
use app\common\model\Level as ModelLevel;
use app\common\model\User as ModelUser;

class User extends BaseLogic
{
    protected $authKey = 'admin_auth_key';

    /**
     * 查询
     * @access public
     * @return array
     */
    public function query(): array
    {
        $query_limit = $this->request->param('limit/d', 20);

        $result = ModelUser::view('user', ['id', 'username', 'realname', 'nickname', 'email', 'phone', 'status', 'create_time'])
            ->view('level', ['name' => 'level_name'], 'level.id=user.level_id')
            ->order('user.create_time DESC')
            ->paginate([
                'list_rows' => $query_limit,
                'path' => 'javascript:paging([PAGE]);',
            ]);

        $list = $result->toArray();
        $list['render'] = $result->render();

        $date_format = $this->request->param('date_format', 'Y-m-d H:i:s');
        foreach ($list['data'] as $key => $value) {
            $value['create_time'] = date($date_format, (int) $value['create_time']);

            $value['url'] = [
                'editor' => url('user/user/editor/' . $value['id']),
                'remove' => url('user/user/remove/' . $value['id']),
            ];

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

    /**
     * 添加
     * @access public
     * @return array
     */
    public function added(): array
    {
        $this->actionLog(__METHOD__, 'admin user added');

        $receive_data = [
            'username'         => $this->request->param('username'),
            'password'         => $this->request->param('password'),
            'password_confirm' => $this->request->param('password_confirm'),
            'phone'            => $this->request->param('phone'),
            'email'            => $this->request->param('email'),
            'level_id'         => $this->request->param('level_id/d'),
            'status'           => $this->request->param('status/d'),
        ];
        if ($result = $this->validate(__METHOD__, $receive_data)) {
            return $result;
        }

        $receive_data['salt'] = Base64::flag(md5(microtime(true) . $receive_data['password']), 6);
        $receive_data['password'] = Base64::createPassword($receive_data['password'], $receive_data['salt']);
        ModelUser::create($receive_data);

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
    public function find(): array
    {
        $result = [];
        if ($id = $this->request->param('id/d')) {
            $result = ModelUser::field('id, username, phone, email, level_id, status')->where([
                ['id', '=', $id],
            ])->find();
            $result = $result ? $result->toArray() : [];
        }

        $level = ModelLevel::where([
            ['status', '=', 1]
        ])->select();
        $result['level_list'] = $level ? $level->toArray() : [];

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
        $this->actionLog(__METHOD__, 'admin user editor');

        if (!$id = $this->request->param('id/d')) {
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
            'level_id'         => $this->request->param('level_id/d'),
            'status'           => $this->request->param('status/d'),
        ];
        if ($result = $this->validate(__METHOD__, $receive_data)) {
            return $result;
        }
        $receive_data['salt'] = Base64::flag(md5(microtime(true) . $receive_data['password']), 6);
        $receive_data['password'] = Base64::createPassword($receive_data['password'], $receive_data['salt']);

        ModelUser::update([
            'username' => $receive_data['username'],
            'password' => $receive_data['password'],
            'phone'    => $receive_data['phone'],
            'email'    => $receive_data['email'],
            'level_id' => $receive_data['level_id'],
            'status'   => $receive_data['status'],
        ], ['id' => $id]);

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
        $this->actionLog(__METHOD__, 'admin user remove');

        if (!$id = $this->request->param('id/d')) {
            return [
                'debug' => false,
                'cache' => false,
                'code'  => 40001,
                'msg'   => 'error'
            ];
        }

        ModelUser::where([
            ['id', '=', $id]
        ])->delete();

        return [
            'debug' => false,
            'cache' => false,
            'msg'   => 'success',
        ];
    }
}
