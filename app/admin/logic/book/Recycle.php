<?php

/**
 *
 * API接口层
 * 文章
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
use app\common\library\tools\Participle;
use app\common\library\Filter;
use app\common\model\BookArticle as ModelBookArticle;

class Recycle extends BaseLogic
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

        // 安审核条件查询,为空查询所有
        if ($is_pass = $this->request->param('pass/d', 0, 'abs')) {
            $is_pass = $is_pass >= 1 ? 1 : 0;
            $map[] = ['book_article.is_pass', '=', $is_pass];
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
            $map[] = ['book_article.title', 'like', $like, 'OR'];
        }

        $date_format = $this->request->param('date_format', 'Y-m-d H:i:s');

        $query_page = $this->request->param('page/d', 1, 'abs');
        if ($query_page > $this->ERPCache()) {
            return [
                'debug' => false,
                'cache' => true,
                'msg'   => 'error',
            ];
        }

        $result = ModelBookArticle::view('book_article', ['id', 'book_id', 'title', 'is_pass', 'update_time'])
            ->view('book', ['title' => 'book_name'], 'book.id=book_article.book_id')
            ->where('book_article.delete_time', '=', '0')
            ->where($map)
            ->order('book_article.is_pass ASC, book_article.id DESC')
            ->paginate([
                'list_rows' => $this->getQueryLimit(),
                'path' => 'javascript:paging([PAGE]);',
            ], true);

        if ($result && $list = $result->toArray()) {
            if (empty($list['data'])) {
                $this->ERPCache($query_page);
            }

            $list['render'] = $result->render();

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
     * 还原
     * @access public
     * @return array
     */
    public function recover(): array
    {
        $this->actionLog('admin content remove');

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
                'delete_time' => 0
            ]);

            // 清除缓存
            $this->cache->tag('book article list' . $book_id)->clear();
        }

        return [
            'debug' => false,
            'cache' => false,
            'msg'   => 'success'
        ];
    }

    /**
     * 物理删除
     * @access public
     * @return array
     */
    public function remove(): array
    {
        $this->actionLog('admin content remove');

        if (!$id = $this->request->param('id/d', 0, 'abs')) {
            return [
                'debug' => false,
                'cache' => false,
                'code'  => 40001,
                'msg'   => 'error'
            ];
        }

        ModelBookArticle::where('id', '=', $id)->limit(1)->delete();

        return [
            'debug' => false,
            'cache' => false,
            'msg'   => 'success'
        ];
    }
}
