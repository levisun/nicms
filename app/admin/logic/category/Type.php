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
    protected $authKey = 'admin_auth_key';

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

        $query_limit = (int) $this->request->param('limit/f', 10);

        $result = (new ModelType)
            ->view('type', ['id', 'name', 'remark'])
            ->view('category', ['name' => 'cat_name'], 'category.id=type.category_id')
            ->order('category.id DESC, type.id')
            ->paginate([
                'list_rows'=> $query_limit,
                'path' => 'javascript:paging([PAGE]);',
            ]);

        $list = $result->toArray();
        $list['render'] = $result->render();

        foreach ($list['data'] as $key => $value) {
            $value['url'] = [
                'editor' => url('category/type/editor/' . $value['id']),
                'remove' => url('category/type/remove/' . $value['id']),
            ];
            $list['data'][$key] = $value;
        }

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
            'category_id' => (int) $this->request->param('category_id/f'),
        ];
        if ($result = $this->validate(__METHOD__, $receive_data)) {
            return $result;
        }

        (new ModelType)->create($receive_data);

        return [
            'debug' => false,
            'cache' => false,
            'msg'   => 'type added success',
        ];
    }

    /**
     * 查询
     * @access public
     * @param
     * @return array
     */
    public function find(): array
    {
        if ($result = $this->authenticate(__METHOD__)) {
            return $result;
        }

        if ($id = (int) $this->request->param('id/f')) {
            $result = (new ModelType)
                ->where([
                    ['id', '=', $id],
                ])
                ->find();
            $result = $result ? $result->toArray() : [];
        }

        return [
            'debug' => false,
            'cache' => false,
            'msg'   => 'type data',
            'data'  => isset($result) ? $result : []
        ];
    }

    /**
     * 编辑
     * @access public
     * @param
     * @return array
     */
    public function editor(): array
    {
        if ($result = $this->authenticate(__METHOD__, 'admin type editor')) {
            return $result;
        }

        if (!$id = (int) $this->request->param('id/f')) {
            return [
                'debug' => false,
                'cache' => false,
                'code'  => 40001,
                'msg'   => '请求错误'
            ];
        }

        $receive_data = [
            'name'        => $this->request->param('name'),
            'remark'      => $this->request->param('remark'),
            'category_id' => (int) $this->request->param('category_id/f'),
        ];
        if ($result = $this->validate(__METHOD__, $receive_data)) {
            return $result;
        }

        (new ModelType)
            ->where([
                ['id', '=', $id]
            ])
            ->data($receive_data)
            ->update();

        return [
            'debug' => false,
            'cache' => false,
            'msg'   => 'type editor success'
        ];
    }

    /**
     * 删除
     * @access public
     * @param
     * @return array
     */
    public function remove(): array
    {
        if ($result = $this->authenticate(__METHOD__, 'admin category remove')) {
            return $result;
        }

        if (!$id = (int) $this->request->param('id/f')) {
            return [
                'debug' => false,
                'cache' => false,
                'code'  => 40001,
                'msg'   => '请求错误'
            ];
        }

        (new ModelType)
            ->where([
                ['id', '=', $id]
            ])
            ->delete();

        return [
            'debug' => false,
            'cache' => false,
            'msg'   => 'remove category success'
        ];
    }
}