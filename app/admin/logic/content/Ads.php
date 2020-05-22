<?php

/**
 *
 * API接口层
 * 友情链接
 *
 * @package   NICMS
 * @category  app\admin\logic\content
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace app\admin\logic\content;

use app\common\controller\BaseLogic;
use app\common\library\Image;
use app\common\model\Ads as ModelAds;

class Ads extends BaseLogic
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

        $result = ModelAds::order('update_time DESC')
            ->paginate([
                'list_rows' => $query_limit,
                'path' => 'javascript:paging([PAGE]);',
            ]);

        $list = $result->toArray();
        $list['render'] = $result->render();

        foreach ($list['data'] as $key => $value) {
            $value['start_time'] = date($date_format, $value['start_time']);
            $value['end_time'] = date($date_format, $value['end_time']);

            $value['image'] = Image::path($value['image']);

            $value['url'] = [
                'editor' => url('content/ads/editor/' . $value['id']),
                'remove' => url('content/ads/remove/' . $value['id']),
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
        $this->actionLog(__METHOD__, 'admin ads added');

        $receive_data = [
            'name'        => $this->request->param('name'),
            'width'       => $this->request->param('width/d', 0, 'abs'),
            'height'      => $this->request->param('height/d', 0, 'abs'),
            'image'       => $this->request->param('image'),
            'url'         => $this->request->param('url'),
            'description' => $this->request->param('description', ''),
            'is_pass'     => $this->request->param('is_pass/d', 0, 'abs'),
            'start_time'  => $this->request->param('start_time', date('Y-m-d'), 'strtotime'),
            'end_time'    => $this->request->param('end_time', date('Y-m-d'), 'strtotime'),
            'update_time' => time(),
            'create_time' => time(),
            'lang'        => $this->lang->getLangSet()
        ];

        if ($result = $this->validate(__METHOD__, $receive_data)) {
            return $result;
        }

        ModelAds::create($receive_data);

        $this->cache->tag('cms ads')->clear();

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
            $result = ModelAds::where([
                ['id', '=', $id],
            ])->find();

            if ($result && $result = $result->toArray()) {
                $result['start_time'] = $result['start_time'] ? date('Y-m-d', $result['start_time']) : date('Y-m-d');
                $result['end_time'] = $result['end_time'] ? date('Y-m-d', $result['end_time']) : date('Y-m-d');
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
        $this->actionLog(__METHOD__, 'admin content editor');

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
            'image'       => $this->request->param('image'),
            'url'         => $this->request->param('url'),
            'description' => $this->request->param('description', ''),
            'is_pass'     => $this->request->param('is_pass/d', 0, 'abs'),
            'start_time'  => $this->request->param('start_time', date('Y-m-d'), 'strtotime'),
            'end_time'    => $this->request->param('end_time', date('Y-m-d'), 'strtotime'),
            'update_time' => time(),
        ];

        if ($result = $this->validate(__METHOD__, $receive_data)) {
            return $result;
        }

        // 删除旧图片
        $image = ModelAds::where([
            ['id', '=', $id],
        ])->value('image');

        if ($image !== $receive_data['image']) {
            $this->removeFile($image);
            $this->writeFileLog($receive_data['image']);
        }

        ModelAds::update($receive_data, ['id' => $id]);

        // 清除缓存
        $this->cache->tag('cms ads')->clear();

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
        $this->actionLog(__METHOD__, 'admin content recycle');

        if (!$id = $this->request->param('id/d', 0, 'abs')) {
            return [
                'debug' => false,
                'cache' => false,
                'code'  => 40001,
                'msg'   => 'error'
            ];
        }

        $image = ModelAds::where([
            ['id', '=', $id]
        ])->value('image');

        if ($image) {
            $this->removeFile($image);
        }

        ModelAds::where([
            ['id', '=', $id]
        ])->delete();

        // 清除缓存
        $this->cache->tag('cms ads')->clear();

        return [
            'debug' => false,
            'cache' => false,
            'msg'   => 'success'
        ];
    }
}
