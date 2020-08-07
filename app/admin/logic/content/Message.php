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
use app\common\model\Message as ModelMessage;

class Message extends BaseLogic
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

        $result = ModelMessage::view('message', ['id', 'title', 'username', 'content', 'category_id', 'type_id'])
            ->view('category', ['name' => 'cat_name'], 'category.id=message.category_id', 'LEFT')
            ->view('type', ['name' => 'type_name'], 'type.id=message.type_id', 'LEFT')
            ->view('user', ['username' => 'author'], 'user.id=message.user_id', 'LEFT')
            ->order('message.is_pass ASC, message.update_time DESC')
            ->paginate([
                'list_rows' => $query_limit,
                'path' => 'javascript:paging([PAGE]);',
            ]);

        $list = $result->toArray();
        $list['total'] = number_format($list['total']);
        $list['render'] = $result->render();

        foreach ($list['data'] as $key => $value) {
            $value['url'] = [
                'editor' => url('content/link/editor/' . $value['id']),
                'remove' => url('content/link/remove/' . $value['id']),
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
     * 查询
     * @access public
     * @return array
     */
    public function find(): array
    {
        $result = [];
        if ($id = $this->request->param('id/d', 0, 'abs')) {
            $result = ModelMessage::view('message', ['id', 'title', 'username', 'content', 'category_id', 'type_id'])
                ->view('category', ['name' => 'cat_name'], 'category.id=message.category_id', 'LEFT')
                ->view('type', ['name' => 'type_name'], 'type.id=message.type_id', 'LEFT')
                ->view('user', ['username' => 'author'], 'user.id=message.user_id', 'LEFT')
                ->where([
                    ['message.id', '=', $id],
                ])
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
        $this->actionLog(__METHOD__, 'admin feedback editor');

        if (!$id = $this->request->param('id/d', 0, 'abs')) {
            return [
                'debug' => false,
                'cache' => false,
                'code'  => 40001,
                'msg'   => 'error'
            ];
        }

        $receive_data = [
            'is_pass'     => $this->request->param('is_pass/d', 0, 'abs'),
            'reply'       => $this->request->param('reply'),
            'update_time' => time(),
        ];

        ModelMessage::update($receive_data, ['id' => $id]);

        $category_id = ModelMessage::where([
            ['id', '=', $id]
        ])->value('category_id');

        // 清除缓存
        $this->cache->tag('cms message list' . $category_id)->clear();

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

        $category_id = ModelMessage::where([
            ['id', '=', $id]
        ])->value('category_id');

        ModelMessage::where([
            ['id', '=', $id]
        ])->delete();

        // 清除缓存
        $this->cache->tag('cms message list' . $category_id)->clear();

        return [
            'debug' => false,
            'cache' => false,
            'msg'   => 'success'
        ];
    }
}
