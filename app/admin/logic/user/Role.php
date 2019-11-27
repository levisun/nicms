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
use app\common\model\Role as ModelRole;

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
        $query_limit = $this->request->param('limit/d', 10);

        $result = (new ModelRole)
            ->where([
                ['id', '<>', 1]
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
    public function added()
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

        // (new ModelRole)->create($receive_data);

        // return [
        //     'debug' => false,
        //     'cache' => false,
        //     'msg'   => 'node added success',
        // ];
    }

    /**
     * 查询
     * @access public
     * @return array
     */
    public function find()
    {

    }

    /**
     * 编辑
     * @access public
     * @return array
     */
    public function editor()
    {
        $this->actionLog(__METHOD__, 'admin role editor');
    }

    /**
     * 删除
     * @access public
     * @return array
     */
    public function remove()
    {
        $this->actionLog(__METHOD__, 'admin role remove');
    }
}
