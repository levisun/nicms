<?php

/**
 *
 * API接口层
 * 友情链接
 *
 * @package   NICMS
 * @category  app\admin\logic\content
 * @author    失眠小枕头 [312630173@qq.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace app\admin\logic\content;

use app\common\controller\BaseLogic;
use app\common\library\UploadLog;
use app\common\model\Link as ModelLink;

class Link extends BaseLogic
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
            $map[] = ['link.category_id', '=', $category_id];
        }

        $query_page = $this->request->param('page/d', 1, 'abs');

        $result = ModelLink::view('link', ['id', 'title', 'logo', 'url', 'category_id', 'type_id'])
            ->view('category', ['name' => 'cat_name'], 'category.id=link.category_id')
            ->view('type', ['name' => 'type_name'], 'type.id=link.type_id', 'LEFT')
            ->where($map)
            ->order('link.id DESC')
            ->paginate([
                'list_rows' => $this->getQueryLimit(),
                'path' => 'javascript:paging([PAGE]);',
            ], true);

        if ($result && $list = $result->toArray()) {
            $list['render'] = $result->render();

            foreach ($list['data'] as $key => $value) {
                $value['logo'] = '/' . $value['logo'];
                $value['url'] = [
                    'editor' => url('content/link/editor/' . $value['id']),
                    'remove' => url('content/link/remove/' . $value['id']),
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
    public function added()
    {
        $receive_data = [
            'title'       => $this->request->param('title'),
            'logo'        => $this->request->param('logo'),
            'url'         => $this->request->param('url'),
            'description' => $this->request->param('description'),
            'category_id' => $this->request->param('category_id/d', 0, 'abs'),
            // 'model_id'    => $this->request->param('model_id/d', 0, 'abs'),
            'type_id'     => $this->request->param('type_id/d', 0, 'abs'),
            'admin_id'    => $this->userId,
            'is_pass'     => $this->request->param('is_pass/d', 0, 'abs'),
            'sort_order'  => $this->request->param('sort_order/d', 0, 'abs'),
            'update_time' => time(),
            'create_time' => time(),
            'lang'        => $this->lang->getLangSet()
        ];

        if ($result = $this->validate($receive_data)) {
            return $result;
        }

        UploadLog::update($receive_data['image'], 1);

        $this->actionLog('admin link added');
        ModelLink::create($receive_data);

        $this->cache->tag('cms link list' . $receive_data['category_id'])->clear();

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
            $result = ModelLink::view('link', ['id', 'title', 'logo', 'url', 'category_id', 'type_id', 'sort_order'])
                ->view('category', ['name' => 'cat_name'], 'category.id=link.category_id')
                ->view('model', ['id' => 'model_id', 'name' => 'model_name', 'table_name'], 'model.id=category.model_id')
                ->view('type', ['name' => 'type_name'], 'type.id=link.type_id', 'LEFT')
                ->where('link.id', '=', $id)
                ->find();

            $result = $result ? $result->toArray() : [];
        }

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
            'title'       => $this->request->param('title'),
            'logo'        => $this->request->param('logo'),
            'url'         => $this->request->param('url'),
            'description' => $this->request->param('description'),
            'category_id' => $this->request->param('category_id/d', 0, 'abs'),
            // 'model_id'    => $this->request->param('model_id/d', 0, 'abs'),
            'type_id'     => $this->request->param('type_id/d', 0, 'abs'),
            'admin_id'    => $this->userId,
            'is_pass'     => $this->request->param('is_pass/d', 0, 'abs'),
            'sort_order'  => $this->request->param('sort_order/d', 0, 'abs'),
            'update_time' => time(),
        ];

        if ($result = $this->validate($receive_data)) {
            return $result;
        }

        // 删除旧LOGO
        $logo = ModelLink::where('id', '=', $id)->value('logo');
        if ($logo !== $receive_data['logo']) {
            UploadLog::remove($logo);
        }

        UploadLog::update($receive_data['image'], 1);

        $this->actionLog('admin content editor ID:' . $id);
        ModelLink::where('id', '=', $id)->limit(1)->update($receive_data);

        // 清除缓存
        $this->cache->tag('cms link list' . $receive_data['category_id'])->clear();

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

        $find = ModelLink::where('id', '=', $id)->column('logo', 'category_id');

        if ($find['logo']) {
            UploadLog::remove($find['logo']);
        }

        $this->actionLog('admin content recycle ID:' . $id);
        ModelLink::where('id', '=', $id)->limit(1)->delete();

        // 清除缓存
        $this->cache->tag('cms link list' . $find['category_id'])->clear();

        return [
            'debug' => false,
            'cache' => false,
            'msg'   => 'success'
        ];
    }
}
