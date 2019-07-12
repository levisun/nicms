<?php

/**
 *
 * API接口层
 * 网站栏目
 *
 * @package   NICMS
 * @category  app\service\admin\category
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace app\service\admin\category;

use app\service\BaseService;
use app\model\Type as ModelType;

class Type extends BaseService
{
    protected $auth_key = 'admin_auth_key';

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

        if ($result = $this->check_params(['limit'])) {
            return $result;
        }

        $query_limit = (int) $this->request->param('limit/f', 10);

        $result = (new ModelType)
            ->view('type', ['id', 'name', 'remark'])
            ->view('category', ['name' => 'cat_name'], 'category.id=type.category_id')
            ->order('category.id DESC, type.id')
            ->paginate($query_limit, false, ['path' => 'javascript:paging([PAGE]);']);

        $list = $result->toArray();
        $list['render'] = $result->render();

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

    /**
     * 添加
     * @access public
     * @param
     * @return array
     */
    public function added(): array
    {
        if ($result = $this->authenticate(__METHOD__, 'admin type added')) {
            return $result;
        }

        $receive_data = [
            'name'        => $this->request->param('name'),
            'remark'      => $this->request->param('remark'),
            'category_id' => (int)$this->request->param('category_id/f'),
        ];
        if ($result = $this->validate(__METHOD__, $receive_data)) {
            return $result;
        }
    }
}
