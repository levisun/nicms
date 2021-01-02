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
use app\common\library\tools\Image;
use app\common\library\UploadLog;
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
        $date_format = $this->request->param('date_format', 'Y-m-d H:i:s');

        $query_limit = $this->request->param('limit/d', 20, 'abs');
        $query_limit = 100 > $query_limit && 10 < $query_limit ? intval($query_limit / 10) * 10 : 20;

        $query_page = $this->request->param('page/d', 1, 'abs');
        if ($query_page > $this->cache->get('admin content ads last_page' . $query_limit, $query_page)) {
            return [
                'debug' => false,
                'cache' => true,
                'msg'   => 'error',
            ];
        }

        $total = $this->cache->get('admin content ads total', false);
        $total = is_bool($total) ? (bool) $total : (int) $total;

        $result = ModelAds::order('update_time DESC')
            ->paginate([
                'list_rows' => $query_limit,
                'path' => 'javascript:paging([PAGE]);',
            ], $total);

        $list = $result->toArray();

        if (!$this->cache->has('admin content ads total')) {
            $this->cache->set('admin content ads total', $list['total'], 28800);
        }

        if (!$this->cache->has('admin content ads last_page' . $query_limit)) {
            $this->cache->set('admin content ads last_page' . $query_limit, $list['last_page'], 28800);
        }

        $list['total'] = number_format($list['total']);
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
        $this->actionLog('admin ads added');

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

        if ($result = $this->validate($receive_data)) {
            return $result;
        }

        UploadLog::update($receive_data['image'], 1);

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
            $result = ModelAds::where('id', '=', $id)->find();

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
        $this->actionLog('admin ads editor');

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

        if ($result = $this->validate($receive_data)) {
            return $result;
        }

        // 删除旧图片
        $image = ModelAds::where('id', '=', $id)->value('image');

        if ($image !== $receive_data['image']) {
            UploadLog::remove($image);
        }

        UploadLog::update($receive_data['image'], 1);

        ModelAds::where('id', '=', $id)->limit(1)->update($receive_data);

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
        $this->actionLog('admin ads remove');

        if (!$id = $this->request->param('id/d', 0, 'abs')) {
            return [
                'debug' => false,
                'cache' => false,
                'code'  => 40001,
                'msg'   => 'error'
            ];
        }

        $image = ModelAds::where('id', '=', $id)->value('image');

        if ($image) {
            UploadLog::remove($image);
        }

        ModelAds::where('id', '=', $id)->limit(1)->delete();

        // 清除缓存
        $this->cache->tag('cms ads')->clear();

        return [
            'debug' => false,
            'cache' => false,
            'msg'   => 'success'
        ];
    }
}
