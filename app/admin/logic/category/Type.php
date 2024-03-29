<?php

/**
 *
 * API接口层
 * 分类
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
use app\common\model\Type as ModelType;

class Type extends BaseLogic
{
    protected $authKey = 'admin_auth_key';

    /**
     * 查询
     * @access public
     * @return array
     */
    public function query(): array
    {
        $model = ModelType::view('type', ['id', 'name', 'remark'])
            ->view('category', ['name' => 'cat_name'], 'category.id=type.category_id')
            ->order('category.id DESC, type.id');

        if ($category_id = $this->request->param('category_id/d', 0, 'abs')) {
            $model->where('fields.category_id', '=', $category_id);
        }

        $result = $model->paginate([
            'list_rows' => $this->getQueryLimit(),
            'path' => 'javascript:paging([PAGE]);',
        ], true);

        if ($result && $list = $result->toArray()) {
            $list['render'] = $result->render();

            foreach ($list['data'] as $key => $value) {
                $value['url'] = [
                    'editor' => url('category/type/editor/' . $value['id']),
                    'remove' => url('category/type/remove/' . $value['id']),
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
        $receive_data = [
            'name'        => $this->request->param('name'),
            'remark'      => $this->request->param('remark'),
            'category_id' => $this->request->param('category_id/d', 0, 'abs'),
        ];
        if ($result = $this->validate($receive_data)) {
            return $result;
        }

        $this->actionLog('admin type added');
        ModelType::create($receive_data);

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
            $result = ModelType::where('id', '=', $id)->find();
            $result = $result ? $result->toArray() : [];
        }

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
        if (!$id = $this->request->param('id/d', 0, 'abs')) {
            return [
                'debug' => false,
                'cache' => false,
                'code'  => 40001,
                'msg'   => 'error'
            ];
        }

        $receive_data = [
            'name'        => $this->request->param('name'),
            'remark'      => $this->request->param('remark'),
            'category_id' => $this->request->param('category_id/d', 0, 'abs'),
        ];
        if ($result = $this->validate($receive_data)) {
            return $result;
        }

        $this->actionLog('admin type editor ID:' . $id);
        ModelType::where('id', '=', $id)->limit(1)->update($receive_data);

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
        if (!$id = $this->request->param('id/d', 0, 'abs')) {
            return [
                'debug' => false,
                'cache' => false,
                'code'  => 40001,
                'msg'   => 'error'
            ];
        }

        $this->actionLog('admin category remove ID:' . $id);
        ModelType::where('id', '=', $id)->limit(1)->delete();

        return [
            'debug' => false,
            'cache' => false,
            'msg'   => 'success'
        ];
    }
}
