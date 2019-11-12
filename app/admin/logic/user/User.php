<?php

/**
 *
 * API接口层
 * 网站栏目
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
        $query_limit = $this->request->param('limit/d', 10);

        $result = (new ModelUser)
            ->view('user', ['id', 'username', 'realname', 'nickname', 'email', 'phone', 'status', 'phone', 'phone'])
            ->view('level', ['name' => 'level_name'], 'level.id=user.id')
            ->order('user.create_time DESC')
            ->paginate([
                'list_rows'=> $query_limit,
                'path' => 'javascript:paging([PAGE]);',
            ]);

        $list = $result->toArray();
        $list['render'] = $result->render();

        $date_format = $this->request->param('date_format', 'Y-m-d H:i:s');
        foreach ($list['data'] as $key => $value) {
            $value['create_time'] = strtotime($value['create_time']);
            $value['create_time'] = date($date_format, $value['create_time']);
            $list['data'][$key] = $value;
        }

        return [
            'debug' => false,
            'cache' => false,
            'msg'   => 'user data',
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
     * 查询
     * @access public
     * @return array
     */
    public function find(): array
    {
        if ($id = $this->request->param('id/d')) {
            $result = (new ModelUser)
                ->where([
                    ['id', '=', $id],
                ])
                ->findOrEmpty();
        }

        return [
            'debug' => false,
            'cache' => false,
            'msg'   => 'user data',
            'data'  => $result
        ];
    }
}
