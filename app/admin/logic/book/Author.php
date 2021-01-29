<?php

/**
 *
 * API接口层
 * 书籍
 *
 * @package   NICMS
 * @category  app\admin\logic\content
 * @author    失眠小枕头 [312630173@qq.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace app\admin\logic\book;

use app\common\controller\BaseLogic;
use app\common\model\BookAuthor as ModelBookAuthor;

class Author extends BaseLogic
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

        // 搜索
        if ($search_key = $this->request->param('key', null, '\app\common\library\Filter::nonChsAlpha')) {
            $like = explode(' ', $search_key);
            $like = array_map('trim', $like);
            $like = array_filter($like);
            $like = array_unique($like);
            $like = array_slice($like, 0, 3);
            $like = array_map(function ($value) {
                return '%' . $value . '%';
            }, $like);
            $map[] = ['book_author.author', 'like', $like, 'OR'];
        }

        $date_format = $this->request->param('date_format', 'Y-m-d H:i:s');

        $query_limit = $this->request->param('limit/d', 20, 'abs');
        $query_limit = 100 > $query_limit && 10 < $query_limit ? intval($query_limit / 10) * 10 : 20;

        $query_page = $this->request->param('page/d', 1, 'abs');
        if ($query_page > $this->getPageCache()) {
            return [
                'debug' => false,
                'cache' => true,
                'msg'   => 'error',
            ];
        }

        $result = ModelBookAuthor::view('book_author', ['id', 'user_id', 'author', 'create_time'])
            ->view('user', ['username'], 'user.id=book_author.user_id', 'LEFT')
            ->where($map)
            ->order('id DESC')
            ->paginate([
                'list_rows' => $query_limit,
                'path' => 'javascript:paging([PAGE]);',
            ], $this->getTotalCache());

        $list = $result->toArray();

        $this->setTotalPageCache($list['total'], $list['last_page']);

        $list['total'] = number_format($list['total']);
        $list['render'] = $result->render();

        foreach ($list['data'] as $key => $value) {
            $value['url'] = [
                'editor' => url('book/author/editor/' . $value['id']),
                'remove' => url('book/author/remove/' . $value['id']),
            ];

            // 时间格式
            $value['create_time'] = date($date_format, (int) $value['create_time']);

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
        $this->actionLog('admin author added');

        $receive_data = [
            'author'      => $this->request->param('author'),
            'create_time' => time(),
        ];
        if ($result = $this->validate($receive_data)) {
            return $result;
        }

        ModelBookAuthor::create($receive_data);

        $this->cache->tag('book')->clear();

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
            $result = ModelBookAuthor::view('book_author', ['id', 'user_id', 'author', 'create_time'])
                ->view('user', ['username'], 'user.id=book_author.user_id', 'LEFT')
                ->where('book_author.id', '=', $id)
                ->find();

            if ($result && $result = $result->toArray()) {

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
        $this->actionLog('admin author editor');

        if (!$id = $this->request->param('id/d', 0, 'abs')) {
            return [
                'debug' => false,
                'cache' => false,
                'code'  => 40001,
                'msg'   => 'error'
            ];
        }

        $receive_data = [
            'author' => $this->request->param('author'),
        ];
        if ($result = $this->validate($receive_data)) {
            return $result;
        }

        ModelBookAuthor::where('id', '=', $id)->limit(1)->update($receive_data);

        $this->cache->tag('book')->clear();

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
        $this->actionLog('admin book remove');

        if (!$id = $this->request->param('id/d', 0, 'abs')) {
            return [
                'debug' => false,
                'cache' => false,
                'code'  => 40001,
                'msg'   => 'error'
            ];
        }

        ModelBookAuthor::where('id', '=', $id)->limit(1)->delete();

        $this->cache->tag('book')->clear();

        return [
            'debug' => false,
            'cache' => false,
            'msg'   => 'success'
        ];
    }
}
