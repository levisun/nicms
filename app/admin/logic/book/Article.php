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
use app\common\library\tools\Participle;
use app\common\library\Filter;
use app\common\model\BookArticle as ModelBookArticle;

class Article extends BaseLogic
{
    protected $authKey = 'admin_auth_key';

    /**
     * 查询
     * @access public
     * @return array
     */
    public function query(): array
    {
        $model = ModelBookArticle::view('book_article', ['id', 'book_id', 'title', 'is_pass', 'update_time'])
            ->view('book', ['title' => 'book_name'], 'book.id=book_article.book_id')
            ->where('book_article.delete_time', '=', '0')
            ->order('book_article.is_pass ASC, book_article.id DESC');

        // 安审核条件查询,为空查询所有
        if ($is_pass = $this->request->param('pass/d', 0, 'abs')) {
            $is_pass = $is_pass >= 1 ? 1 : 0;
            $model->where('book_article.is_pass', '=', $is_pass);
        }

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
            $model->where('book_article.title', 'like', $like, 'OR');
        }

        $result = $model->paginate([
            'list_rows' => $this->getQueryLimit(),
            'path' => 'javascript:paging([PAGE]);',
        ], true);

        if ($result && $list = $result->toArray()) {
            $list['render'] = $result->render();

            $date_format = $this->request->param('date_format', 'Y-m-d H:i:s');
            foreach ($list['data'] as $key => $value) {
                $value['url'] = [
                    'editor' => url('book/article/editor/' . $value['id']),
                    'remove' => url('book/article/remove/' . $value['id']),
                ];

                // 时间格式
                $value['update_time'] = date($date_format, (int) $value['update_time']);
                $value['title'] = Filter::htmlDecode($value['title']);

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
        $this->actionLog('admin book added');

        $receive_data = [
            'book_id'    => $this->request->param('book_id/d', 0, 'abs'),
            'title'      => $this->request->param('title'),
            'content'    => $this->request->param('content', '', '\app\common\library\Filter::htmlEncode'),
            'is_pass'    => $this->request->param('is_pass/d', 0, 'abs'),
            'sort_order' => $this->request->param('sort_order/d', 0, 'abs'),
            'show_time'  => $this->request->param('show_time/d', 0, 'abs'),
            'update_time' => time(),
            'create_time' => time(),
        ];
        if ($result = $this->validate($receive_data)) {
            return $result;
        }

        ModelBookArticle::create($receive_data);

        $this->cache->tag('book article list' . $receive_data['book_id'])->clear();
        $this->cache->tag('book article')->clear();

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
            $result = ModelBookArticle::where('book.id', '=', $id)->find();

            if ($result && $result = $result->toArray()) {
                $result['show_time'] = $result['show_time'] ? date('Y-m-d', $result['show_time']) : date('Y-m-d');
                $result['content'] = Filter::htmlDecode($result['content']);
                $result['title'] = Filter::htmlDecode($result['title']);
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
        $this->actionLog('admin book editor');

        if (!$id = $this->request->param('id/d', 0, 'abs')) {
            return [
                'debug' => false,
                'cache' => false,
                'code'  => 40001,
                'msg'   => 'error'
            ];
        }

        $receive_data = [
            'book_id'    => $this->request->param('book_id/d', 0, 'abs'),
            'title'      => $this->request->param('title'),
            'content'    => $this->request->param('content', '', '\app\common\library\Filter::htmlEncode'),
            'is_pass'    => $this->request->param('is_pass/d', 0, 'abs'),
            'sort_order' => $this->request->param('sort_order/d', 0, 'abs'),
            'show_time'  => $this->request->param('show_time/d', 0, 'abs'),
            'update_time' => time(),
        ];
        if ($result = $this->validate($receive_data)) {
            return $result;
        }

        ModelBookArticle::where('id', '=', $id)->limit(1)->update($receive_data);

        $this->cache->tag('book article list' . $receive_data['book_id'])->clear();
        $this->cache->tag('book article')->clear();

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

        $book_id = ModelBookArticle::where('id', '=', $id)->value('book_id');

        if ($book_id) {
            ModelBookArticle::where('id', '=', $id)->limit(1)->update([
                'delete_time' => time()
            ]);

            // 清除缓存
            $this->cache->tag('book article list' . $book_id)->clear();
            $this->cache->tag('book article')->clear();
        }

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
        $this->actionLog('admin content sort');

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
            (new ModelBookArticle)->saveAll($list);
        }

        return [
            'debug' => false,
            'cache' => false,
            'msg'   => 'success'
        ];
    }
}
