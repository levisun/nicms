<?php

/**
 *
 * API接口层
 * 自定义字段
 *
 * @package   NICMS
 * @category  app\admin\logic\category
 * @author    失眠小枕头 [312630173@qq.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace app\admin\logic\category;

use app\common\controller\BaseLogic;
use app\common\model\Fields as ModelFields;
use app\common\model\FieldsType as ModelFieldsType;
use app\common\model\FieldsExtend as ModelFieldsExtend;

class Fields extends BaseLogic
{
    protected $authKey = 'admin_auth_key';

    /**
     * 查询
     * @access public
     * @return array
     */
    public function query(): array
    {
        $map = [];
        if ($category_id = $this->request->param('category_id/d', 0, 'abs')) {
            $map[] = ['fields.category_id', '=', $category_id];
        }

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

        $result = ModelFields::view('fields', ['id', 'name', 'is_require', 'remark'])
            ->view('category', ['name' => 'cat_name'], 'category.id=fields.category_id')
            ->view('fields_type', ['id' => 'type_id', 'name' => 'type_name'], 'fields_type.id=fields.type_id')
            ->where($map)
            ->order('fields.id DESC')
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

        foreach ($list['data'] as $key => $value) {
            $value['url'] = [
                'editor' => url('category/fields/editor/' . $value['id']),
                'remove' => url('category/fields/remove/' . $value['id']),
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
        $this->actionLog('admin fields added');

        $receive_data = [
            'category_id' => $this->request->param('category_id/d', 0, 'abs'),
            'type_id'     => $this->request->param('type_id/d', 0, 'abs'),
            'name'        => $this->request->param('name'),
            'maxlength'   => $this->request->param('maxlength/d', 0, 'abs'),
            'is_require'  => $this->request->param('is_require/d', 0, 'abs'),
            'sort_order'  => $this->request->param('sort_order/d', 0, 'abs'),
            'remark'      => $this->request->param('remark'),
        ];
        if ($result = $this->validate($receive_data)) {
            return $result;
        }

        ModelFields::create($receive_data);

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
        if ($id = $this->request->param('id/d', 0, 'abs')) {
            $result = ModelFields::where('id', '=', $id)->find();
            $result = $result ? $result->toArray() : [];
        }

        $result['fields_type'] = ModelFieldsType::field('id, name')
            ->order('id DESC')
            ->select()
            ->toArray();

        return [
            'debug' => false,
            'cache' => false,
            'msg'   => 'success',
            'data'  => isset($result) ? $result : []
        ];
    }

    /**
     * 编辑
     * @access public
     * @return array
     */
    public function editor(): array
    {
        $this->actionLog('admin fields editor');

        if (!$id = $this->request->param('id/d', 0, 'abs')) {
            return [
                'debug' => false,
                'cache' => false,
                'code'  => 40001,
                'msg'   => 'error'
            ];
        }

        $receive_data = [
            'category_id' => $this->request->param('category_id/d', 0, 'abs'),
            'type_id'     => $this->request->param('type_id/d', 0, 'abs'),
            'name'        => $this->request->param('name'),
            'maxlength'   => $this->request->param('maxlength/d', 0, 'abs'),
            'is_require'  => $this->request->param('is_require/d', 0, 'abs'),
            'sort_order'  => $this->request->param('sort_order/d', 0, 'abs'),
            'remark'      => $this->request->param('remark'),
        ];
        if ($result = $this->validate($receive_data)) {
            return $result;
        }

        ModelFields::where('id', '=', $id)->limit(1)->update($receive_data);

        return [
            'debug' => false,
            'cache' => false,
            'msg'   => 'success'
        ];
    }

    /**
     * 删除
     * @access public
     * @return array
     */
    public function remove(): array
    {
        $this->actionLog('admin fields remove');

        if (!$id = $this->request->param('id/d', 0, 'abs')) {
            return [
                'debug' => false,
                'cache' => false,
                'code'  => 40001,
                'msg'   => 'error'
            ];
        }

        ModelFields::where('id', '=', $id)->limit(1)->delete();

        ModelFieldsExtend::where('fields_id', '=', $id)->delete();

        return [
            'debug' => false,
            'cache' => false,
            'msg'   => 'success'
        ];
    }
}
