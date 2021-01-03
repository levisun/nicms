<?php

/**
 *
 * API接口层
 * 用户
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
        $query_limit = $this->request->param('limit/d', 20, 'abs');
        $query_limit = 100 > $query_limit && 10 < $query_limit ? intval($query_limit / 10) * 10 : 20;

        $query_page = $this->request->param('page/d', 1, 'abs');
        if ($query_page > $this->cache->get($this->getCacheKey('page'), $query_page)) {
            return [
                'debug' => false,
                'cache' => true,
                'msg'   => 'error',
            ];
        }

        $total = $this->cache->get($this->getCacheKey('total'));
        $total = is_null($total) ? false : (int) $total;

        $result = ModelUser::view('user', ['id', 'username', 'realname', 'nickname', 'email', 'phone', 'status', 'create_time'])
            ->view('level', ['name' => 'level_name'], 'level.id=user.level_id')
            ->order('user.create_time DESC')
            ->paginate([
                'list_rows' => $query_limit,
                'path' => 'javascript:paging([PAGE]);',
            ], $total);

        $list = $result->toArray();

        if (!$this->cache->has($this->getCacheKey('total'))) {
            $this->cache->tag('request')->set($this->getCacheKey('total'), $list['total'], 28800);
        }

        if (!$this->cache->has($this->getCacheKey('page'))) {
            $this->cache->tag('request')->set($this->getCacheKey('page'), $list['last_page'], 28800);
        }

        $list['total'] = number_format($list['total']);
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
        $this->actionLog('admin user added');

        $receive_data = [
            'username'         => $this->request->param('username'),
            'password'         => $this->request->param('password'),
            'password_confirm' => $this->request->param('password_confirm'),
            'phone'            => $this->request->param('phone'),
            'email'            => $this->request->param('email'),
            'level_id'         => $this->request->param('level_id/d', 0, 'abs'),
            'status'           => $this->request->param('status/d', 0, 'abs'),
        ];
        if ($result = $this->validate($receive_data)) {
            return $result;
        }

        $receive_data['salt'] = Base64::flag(microtime(true) . $receive_data['password'], 6);
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
        if ($id = $this->request->param('id/d', 0, 'abs')) {
            $result = ModelUser::field('id, username, phone, email, level_id, status')
                ->where('id', '=', $id)
                ->find();
            $result = $result ? $result->toArray() : [];
        }

        $level = ModelLevel::where('status', '=', 1)->select();
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
        $this->actionLog('admin user editor');

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
            'level_id'         => $this->request->param('level_id/d', 0, 'abs'),
            'status'           => $this->request->param('status/d', 0, 'abs'),
        ];
        if ($result = $this->validate($receive_data)) {
            return $result;
        }
        $receive_data['salt'] = Base64::flag(microtime(true) . $receive_data['password'], 6);
        $receive_data['password'] = Base64::createPassword($receive_data['password'], $receive_data['salt']);

        ModelUser::where('id', '=', $id)->limit(1)->update([
            'username' => $receive_data['username'],
            'password' => $receive_data['password'],
            'phone'    => $receive_data['phone'],
            'email'    => $receive_data['email'],
            'level_id' => $receive_data['level_id'],
            'status'   => $receive_data['status'],
        ]);

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
        $this->actionLog('admin user remove');

        if (!$id = $this->request->param('id/d', 0, 'abs')) {
            return [
                'debug' => false,
                'cache' => false,
                'code'  => 40001,
                'msg'   => 'error'
            ];
        }

        ModelUser::where('id', '=', $id)->limit(1)->delete();

        return [
            'debug' => false,
            'cache' => false,
            'msg'   => 'success',
        ];
    }
}
