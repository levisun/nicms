<?php

/**
 *
 * API接口层
 * 书籍分类
 *
 * @package   NICMS
 * @category  app\admin\logic\book
 * @author    失眠小枕头 [312630173@qq.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace app\admin\logic\book;

use app\common\controller\BaseLogic;
use app\common\library\UploadLog;
use app\common\model\BookType as ModelBookType;

class Type extends BaseLogic
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
        $result = ModelBookType::field(['id', 'name', 'is_show', 'sort_order'])
            ->where([
                ['pid', '=', 0],
                ['lang', '=', $this->lang->getLangSet()]
            ])
            ->order('sort_order ASC, id DESC')
            ->select();

        $result = $result ? $result->toArray() : [];

        foreach ($result as $key => $value) {
            $value['url'] = [
                'added'  => url('book/type/added/' . $value['id']),
                'editor' => url('book/type/editor/' . $value['id']),
                'remove' => url('book/type/remove/' . $value['id']),
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

        $result = ModelBookType::field(['id', 'name', 'type_id', 'is_show', 'is_channel', 'sort_order'])
            ->where([
                ['pid', '=', $_pid],
                ['lang', '=', $this->lang->getLangSet()]
            ])
            ->order('sort_order ASC, id DESC')
            ->select();

        $result = $result ? $result->toArray() : [];

        foreach ($result as $key => $value) {
            for ($i = 0; $i < $this->layer; $i++) {
                $value['name'] = '|__' . $value['name'];
            }

            $value['url'] = [
                'added'  => url('book/type/added/' . $value['id']),
                'editor' => url('book/type/editor/' . $value['id']),
                'remove' => url('book/type/remove/' . $value['id']),
            ];
            $value['child'] = $this->child((int) $value['id']);
            $result[$key] = $value;
        }

        return $result ? $result : false;
    }

    /**
     * 添加
     * @access public
     * @return array
     */
    public function added(): array
    {
        $this->actionLog('admin book type added');

        $pid = $this->request->param('pid/d', 0, 'abs');

        $receive_data = [
            'pid'         => $pid,
            'name'        => $this->request->param('name'),
            'aliases'     => $this->request->param('aliases'),
            'title'       => $this->request->param('title'),
            'keywords'    => $this->request->param('keywords'),
            'description' => $this->request->param('description'),
            'image'       => $this->request->param('image'),
            'is_show'     => $this->request->param('is_show/d', 1, 'abs'),
            'is_channel'  => $this->request->param('is_channel/d', 0, 'abs'),
            'sort_order'  => $this->request->param('sort_order/d', 0, 'abs'),
            'url'         => $this->request->param('url'),
            'update_time' => time(),
            'create_time' => time(),
            'lang'        => $this->lang->getLangSet()
        ];
        if ($result = $this->validate($receive_data)) {
            return $result;
        }

        ModelBookType::create($receive_data);

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
            $result = ModelBookType::where('id', '=', $id)->find();

            if (null !== $result && $result = $result->toArray()) {
                $result['image'] = $result['image']
                    ? $this->config->get('app.img_host') . $result['image']
                    : '';

                $result['parent'] = ModelBookType::where('id', '=', $result['pid'])->value('name as parent');
            }
        } else {
            $result = [];
            if ($pid = $this->request->param('pid/d', 0, 'abs')) {
                $result['parent'] = ModelBookType::where('id', '=', $pid)->value('name as parent');
            }
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
        $this->actionLog('admin book type editor');

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
            'is_show'     => $this->request->param('is_show/d', 1, 'abs'),
            'sort_order'  => $this->request->param('sort_order/d', 0, 'abs'),
            'url'         => $this->request->param('url'),
            'update_time' => time(),
        ];
        if ($result = $this->validate($receive_data)) {
            return $result;
        }

        // 删除旧图片
        $image = ModelBookType::where('id', '=', $id)->value('image');
        if ($image !== $receive_data['image']) {
            UploadLog::remove($image);
            UploadLog::update($receive_data['image'], 1);
        }

        ModelBookType::update($receive_data, ['id' => $id]);

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
        $this->actionLog('admin book type remove');

        if (!$id = $this->request->param('id/d', 0, 'abs')) {
            return [
                'debug' => false,
                'cache' => false,
                'code'  => 40001,
                'msg'   => 'error'
            ];
        }

        $image = ModelBookType::where([
            ['id', '=', $id],
            ['lang', '=', $this->lang->getLangSet()]
        ])->value('image');

        if (null !== $image && $image) {
            UploadLog::remove($image);
        }

        ModelBookType::where('id', '=', $id)->delete();

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
        $this->actionLog('admin book type sort');

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
            (new ModelBookType)->saveAll($list);
        }

        return [
            'debug' => false,
            'cache' => false,
            'msg'   => 'success'
        ];
    }
}
