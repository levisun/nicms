<?php

/**
 *
 * API接口层
 * 自定义字段
 *
 * @package   NICMS
 * @category  app\admin\logic\category
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace app\admin\logic\category;

use app\common\controller\BaseLogic;
use app\common\model\Fields as ModelFields;
use app\common\model\FieldsType as ModelFieldsType;
use app\common\model\ArticleExtend as ModelArticleExtend;

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
        $query_limit = $this->request->param('limit/d', 10);

        $result = (new ModelFields)
            ->view('fields', ['id', 'name', 'is_require', 'remark'])
            ->view('category', ['name' => 'cat_name'], 'category.id=fields.category_id')
            ->view('fields_type', ['name' => 'type_name'], 'fields_type.id=fields.type_id')
            ->order('category.id DESC, type.id')
            ->paginate([
                'list_rows' => $query_limit,
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
     * @return array
     */
    public function added(): array
    {
        $this->actionLog(__METHOD__, 'admin type added');

        $receive_data = [
            'category_id' => $this->request->param('category_id/d'),
            'type_id'     => $this->request->param('type_id/d'),
            'name'        => $this->request->param('name'),
            'maxlength'   => $this->request->param('maxlength/d'),
            'is_require'  => $this->request->param('is_require/d'),
            'sort_order'  => $this->request->param('sort_order/d'),
            'remark'      => $this->request->param('remark'),
        ];
        if ($result = $this->validate(__METHOD__, $receive_data)) {
            return $result;
        }

        (new ModelFields)->save($receive_data);

        return [
            'debug' => false,
            'cache' => false,
            'msg'   => 'type added success',
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
            $result = (new ModelFields)
                ->where([
                    ['id', '=', $id],
                ])
                ->find();
            $result = $result ? $result->toArray() : [];
        }

        $result['fields_type'] = (new ModelFieldsType)
            ->field('id, name')
            ->order('id DESC')
            ->select()
            ->toArray();

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
     * @return array
     */
    public function editor(): array
    {
        $this->actionLog(__METHOD__, 'admin type editor');

        if (!$id = $this->request->param('id/d')) {
            return [
                'debug' => false,
                'cache' => false,
                'code'  => 40001,
                'msg'   => '请求错误'
            ];
        }

        $receive_data = [
            'category_id' => $this->request->param('category_id/d'),
            'type_id'     => $this->request->param('type_id/d'),
            'name'        => $this->request->param('name'),
            'maxlength'   => $this->request->param('maxlength/d'),
            'is_require'  => $this->request->param('is_require/d'),
            'sort_order'  => $this->request->param('sort_order/d'),
            'remark'      => $this->request->param('remark'),
        ];
        if ($result = $this->validate(__METHOD__, $receive_data)) {
            return $result;
        }

        (new ModelFields)
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
     * @return array
     */
    public function remove(): array
    {
        $this->actionLog(__METHOD__, 'admin category remove');

        if (!$id = $this->request->param('id/d')) {
            return [
                'debug' => false,
                'cache' => false,
                'code'  => 40001,
                'msg'   => '请求错误'
            ];
        }

        (new ModelFields)
            ->where([
                ['id', '=', $id]
            ])
            ->delete();

        (new ModelArticleExtend)
            ->where([
                ['fields_id', '=', $id]
            ])
            ->delete();

        return [
            'debug' => false,
            'cache' => false,
            'msg'   => 'remove category success'
        ];
    }
}
