<?php

/**
 *
 * API接口层
 * 栏目
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
use app\common\library\UploadLog;
use app\common\model\Category as ModelCategory;

class Category extends BaseLogic
{
    protected $authKey = 'admin_auth_key';

    // 层级
    private $layer = 0;

    /**
     * 查询
     * @access public
     * @return array
     */
    public function query(): array
    {
        $result = ModelCategory::view('category', ['id', 'name', 'type_id', 'model_id', 'is_show', 'is_channel', 'sort_order'])
            ->view('model', ['name' => 'model_name'], 'model.id=category.model_id')
            ->where([
                ['category.pid', '=', 0],
                ['category.lang', '=', $this->lang->getLangSet()]
            ])
            ->order('category.type_id ASC, category.sort_order ASC, category.id DESC')
            ->select();

        $result = $result ? $result->toArray() : [];

        foreach ($result as $key => $value) {
            $value['type_name'] = $this->typeName($value['type_id']);
            $value['url'] = [
                'added'  => url('category/category/added/' . $value['id']),
                'editor' => url('category/category/editor/' . $value['id']),
                'remove' => url('category/category/remove/' . $value['id']),
            ];
            $this->layer = 0;
            $value['child'] = $this->child((int) $value['id']);
            $result[$key] = $value;
        }

        return [
            'debug' => false,
            'cache' => false,
            'msg'   => 'success',
            'data'  => [
                'list'  => $result,
                'total' => count($result),
            ]
        ];
    }

    /**
     * 子导航
     * @access private
     * @param  int $_pid
     * @return bool|array
     */
    private function child(int $_pid)
    {
        $this->layer++;

        $result = ModelCategory::view('category', ['id', 'name', 'type_id', 'model_id', 'is_show', 'is_channel', 'sort_order'])
            ->view('model', ['name' => 'model_name'], 'model.id=category.model_id')
            ->where([
                ['category.pid', '=', $_pid],
                ['category.lang', '=', $this->lang->getLangSet()]
            ])
            ->order('category.sort_order ASC, category.id DESC')
            ->select();

        $result = $result ? $result->toArray() : [];

        foreach ($result as $key => $value) {
            for ($i = 0; $i < $this->layer; $i++) {
                $value['name'] = '|__' . $value['name'];
            }

            $value['type_name'] = $this->typeName((int) $value['type_id']);
            $value['url'] = [
                'added'  => url('category/category/added/' . $value['id']),
                'editor' => url('category/category/editor/' . $value['id']),
                'remove' => url('category/category/remove/' . $value['id']),
            ];
            $value['child'] = $this->child((int) $value['id']);
            $result[$key] = $value;
        }

        return $result ? $result : false;
    }

    /**
     * 导航类型
     * @access private
     * @param  int $_tid
     * @return string
     */
    private function typeName(int $_tid): string
    {
        if ($_tid === 1) {
            return $this->lang->get('category top type');
        } elseif ($_tid === 2) {
            return $this->lang->get('category main type');
        } elseif ($_tid === 3) {
            return $this->lang->get('category foot type');
        } else {
            return $this->lang->get('category other type');
        }
    }

    /**
     * 添加
     * @access public
     * @return array
     */
    public function added(): array
    {
        $this->actionLog('admin category added');

        $pid = $this->request->param('pid/d', 0, 'abs');

        $receive_data = [
            'pid'         => $pid,
            'name'        => $this->request->param('name'),
            'aliases'     => $this->request->param('aliases'),
            'title'       => $this->request->param('title'),
            'keywords'    => $this->request->param('keywords'),
            'description' => $this->request->param('description'),
            'image'       => $this->request->param('image'),
            'model_id'    => $this->request->param('model_id/d', 1, 'abs'),
            'type_id'     => $this->request->param('type_id/d', 1, 'abs'),
            'is_show'     => $this->request->param('is_show/d', 1, 'abs'),
            'is_channel'  => $this->request->param('is_channel/d', 0, 'abs'),
            'sort_order'  => $this->request->param('sort_order/d', 0, 'abs'),
            'access_id'   => $this->request->param('access_id/d', 0, 'abs'),
            'url'         => $this->request->param('url'),
            'update_time' => time(),
            'create_time' => time(),
            'lang'        => $this->lang->getLangSet()
        ];
        if ($result = $this->validate($receive_data)) {
            return $result;
        }

        UploadLog::update($receive_data['image'], 1);

        ModelCategory::create($receive_data);

        $this->cache->tag('cms nav')->clear();

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
            $result = ModelCategory::view('category')
                ->view('model', ['name' => 'model_name'], 'model.id=category.model_id')
                ->where('category.id', '=', $id)
                ->find();

            if (null !== $result && $result = $result->toArray()) {
                $result['image_url'] = $result['image']
                    ? $this->config->get('app.img_host') . $result['image']
                    : '';

                $result['parent'] = ModelCategory::where('id', '=', $result['pid'])->value('name as parent');
            }
        } else {
            $result = [];
            if ($pid = $this->request->param('pid/d', 0, 'abs')) {
                $result['parent'] = ModelCategory::where('id', '=', $pid)->value('name as parent');
            }
        }

        $result['type_list'] = [
            ['id' => '1', 'name' => $this->lang->get('category top type')],
            ['id' => '2', 'name' => $this->lang->get('category main type')],
            ['id' => '3', 'name' => $this->lang->get('category foot type')],
            ['id' => '4', 'name' => $this->lang->get('category other type')],
        ];

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
        $this->actionLog('admin category editor');

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
            'aliases'     => $this->request->param('aliases'),
            'title'       => $this->request->param('title'),
            'keywords'    => $this->request->param('keywords'),
            'description' => $this->request->param('description'),
            'image'       => $this->request->param('image'),
            'model_id'    => $this->request->param('model_id/d', 1, 'abs'),
            'type_id'     => $this->request->param('type_id/d', 1, 'abs'),
            'is_show'     => $this->request->param('is_show/d', 1, 'abs'),
            'is_channel'  => $this->request->param('is_channel/d', 0, 'abs'),
            'sort_order'  => $this->request->param('sort_order/d', 0, 'abs'),
            'access_id'   => $this->request->param('access_id/d', 0, 'abs'),
            'url'         => $this->request->param('url'),
            'update_time' => time(),
        ];
        if ($result = $this->validate($receive_data)) {
            return $result;
        }

        // 删除旧图片
        $image = ModelCategory::where('id', '=', $id)->value('image');
        if ($image !== $receive_data['image']) {
            UploadLog::remove($image);
        }

        UploadLog::update($receive_data['image'], 1);

        ModelCategory::where('id', '=', $id)->limit(1)->update($receive_data);

        $this->cache->tag('cms nav')->clear();

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
        $this->actionLog('admin category remove');

        if (!$id = $this->request->param('id/d', 0, 'abs')) {
            return [
                'debug' => false,
                'cache' => false,
                'code'  => 40001,
                'msg'   => 'error'
            ];
        }

        $image = ModelCategory::where([
            ['id', '=', $id],
            ['lang', '=', $this->lang->getLangSet()]
        ])->value('image');

        if (null !== $image && $image) {
            UploadLog::remove($image);
        }

        ModelCategory::where('id', '=', $id)->limit(1)->delete();

        $this->cache->tag('cms nav')->clear();

        return [
            'debug' => false,
            'cache' => false,
            'msg'   => 'success'
        ];
    }

    /**
     * 排序
     * @access public
     * @return array
     */
    public function sort(): array
    {
        $this->actionLog('admin category sort');

        $sort_order = $this->request->param('sort_order/a');
        if (empty($sort_order)) {
            return [
                'debug' => false,
                'cache' => false,
                'code'  => 40001,
                'msg'   => 'error'
            ];
        }

        $list = [];
        foreach ($sort_order as $key => $value) {
            if ($value) {
                $list[] = ['id' => (int) $key, 'sort_order' => (int) $value];
            }
        }
        if (!empty($list)) {
            (new ModelCategory)->saveAll($list);
        }

        return [
            'debug' => false,
            'cache' => false,
            'msg'   => 'success'
        ];
    }
}
