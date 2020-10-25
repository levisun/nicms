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
use app\common\model\Banner as ModelBanner;

class Banner extends BaseLogic
{
    protected $authKey = 'admin_auth_key';

    /**
     * 查询
     * @access public
     * @return array
     */
    public function query(): array
    {
        $query_limit = $this->request->param('limit/d', 20, 'abs');

        $date_format = $this->request->param('date_format', 'Y-m-d H:i:s');

        $result = ModelBanner::view('banner', ['id', 'name', 'width', 'height'])
            ->where([
                ['id', '=', 0]
            ])
            ->order('banner.update_time DESC')
            ->paginate([
                'list_rows' => $query_limit,
                'path' => 'javascript:paging([PAGE]);',
            ]);

        $list = $result->toArray();
        $list['total'] = number_format($list['total']);
        $list['render'] = $result->render();

        foreach ($list['data'] as $key => $value) {
            $value['image_url'] = unserialize($value['image_url']);
            $value['url'] = [
                'editor' => url('content/banner/editor/' . $value['id']),
                'remove' => url('content/banner/remove/' . $value['id']),
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
    public function added()
    {
        $this->actionLog('admin banner added');

        $receive_data = [
            'name'        => $this->request->param('name'),
            'description' => $this->request->param('description', ''),
            'width'       => $this->request->param('width/d', 0, 'abs'),
            'height'      => $this->request->param('height/d', 0, 'abs'),
            'image_url'   => $this->request->param('image_url/a'),
            'url'         => $this->request->param('url/a'),
            'is_pass'     => $this->request->param('is_pass/d', 0, 'abs'),
            'sort_order'  => $this->request->param('sort_order/d', 0, 'abs'),
            'update_time' => time(),
            'create_time' => time(),
            'lang'        => $this->lang->getLangSet()
        ];

        if ($result = $this->validate($receive_data)) {
            return $result;
        }

        foreach ($receive_data['image_url'] as $value) {
            UploadLog::update($value, 1);
        }

        $receive_data['image_url'] = serialize($receive_data['image_url']);
        $receive_data['url'] = serialize($receive_data['url']);

        ModelBanner::create($receive_data);

        $this->cache->tag('cms banner')->clear();

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
            $result = ModelBanner::where([
                ['id', '=', $id],
            ])->find();
            $result['image_url'] = unserialize($result['image_url']);
            $result['url'] = unserialize($result['url']);
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
        $this->actionLog('admin banner editor');

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
            'width'       => $this->request->param('width/d', 0, 'abs'),
            'height'      => $this->request->param('height/d', 0, 'abs'),
            'image_url'   => $this->request->param('image_url/a'),
            'url'         => $this->request->param('url/a'),
            'description' => $this->request->param('description', ''),
            'is_pass'     => $this->request->param('is_pass/d', 0, 'abs'),
            'update_time' => time(),
        ];

        if ($result = $this->validate($receive_data)) {
            return $result;
        }

        // 删除旧图片
        $image_url = ModelBanner::where([
            ['id', '=', $id],
        ])->value('image_url');
        $image_url = unserialize($image_url);

        foreach ($image_url as $img) {
            if (!is_array($img, $receive_data['image_url'])) {
                UploadLog::remove($img);
            }
        }
        foreach ($receive_data['image_url'] as $img) {
            UploadLog::update($img, 1);
        }

        $receive_data['image_url'] = serialize($receive_data['image_url']);
        $receive_data['url'] = serialize($receive_data['url']);

        ModelBanner::update($receive_data, ['id' => $id]);

        // 清除缓存
        $this->cache->tag('cms banner')->clear();

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
        $this->actionLog('admin banner remove');

        if (!$id = $this->request->param('id/d', 0, 'abs')) {
            return [
                'debug' => false,
                'cache' => false,
                'code'  => 40001,
                'msg'   => 'error'
            ];
        }

        // 删除图片
        $image_url = ModelBanner::where([
            ['id', '=', $id],
        ])->value('image_url');
        $image_url = unserialize($image_url);

        foreach ($image_url as $img) {
            UploadLog::remove($img);
        }

        ModelBanner::where([
            ['id', '=', $id]
        ])->delete();

        // 清除缓存
        $this->cache->tag('cms banner')->clear();

        return [
            'debug' => false,
            'cache' => false,
            'msg'   => 'success'
        ];
    }
}
