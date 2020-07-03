<?php

/**
 *
 * API接口层
 * 文章列表
 *
 * @package   NICMS
 * @category  app\book\logic\article
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace app\book\logic\book;

use app\common\controller\BaseLogic;
use app\common\model\BookArticle as ModelBookArticle;
use app\common\library\Filter;

class Article extends BaseLogic
{

    /**
     * 查询列表
     * @access public
     * @return array
     */
    public function query(): array
    {
        $book_id = $this->request->param('book_id/d', 0, 'abs');
        $id = $this->request->param('id/d', 0, 'abs');
        $date_format = $this->request->param('date_format', 'Y-m-d');

        $cache_key = 'book article' . $book_id . $id . $date_format;
        $cache_key = md5($cache_key);

        if (!$this->cache->has($cache_key) || !$result = $this->cache->get($cache_key)) {
            $result = ModelBookArticle::where([
                ['book_id', '=', $book_id],
                ['id', '=', $id],
            ])->find();

            if ($result && $result = $result->toArray()) {
                // 书籍文章列表链接
                $result['cat_url'] = url('book/' . $result['book_id']);
                // 书籍文章文章链接
                $result['url'] = url('article/' . $result['book_id'] . '/' . $result['id']);
                // 内容
                $result['content'] = Filter::decode($result['content']);
                // 时间格式
                $result['update_time'] = date($date_format, (int) $result['update_time']);

                $result['next'] = $this->next($result['id'], $result['book_id']);
                $result['prev'] = $this->prev($result['id'], $result['book_id']);

                $this->cache->tag(['book', 'book article list' . $book_id])->set($cache_key, $result);
            }
        }

        return [
            'debug' => false,
            'cache' => isset($result) ? true : false,
            'msg'   => isset($result) ? 'article' : 'error',
            'data'  => isset($result) ? $result : []
        ];
    }

    /**
     * 下一篇
     * @access private
     * @param  int      $_article_id
     * @param  int      $_book_id
     * @return array
     */
    private function next(int $_article_id, int $_book_id)
    {
        $next_id = ModelBookArticle::where([
            ['is_pass', '=', 1],
            ['show_time', '<', time()],
            ['id', '>', $_article_id],
            ['book_id', '=', $_book_id],
        ])->min('id');

        $result = ModelBookArticle::field('id, title, book_id')
            ->where([
                ['is_pass', '=', 1],
                ['show_time', '<', time()],
                ['id', '=', $next_id]
            ])->find();

        if ($result && $result = $result->toArray()) {
            $result['url'] = url('article/' . $result['book_id'] . '/' . $result['id']);
            $result['cat_url'] = url('list/' . $result['book_id']);
        } else {
            $result = [
                'title'   => $this->lang->get('not next'),
                'url'     => url('article/' . $_book_id . '/' . $_article_id),
                'cat_url' => url('list/' . $_book_id),
            ];
        }

        return $result;
    }

    /**
     * 上一篇
     * @access private
     * @param  int      $_article_id
     * @param  int      $_book_id
     * @return array
     */
    private function prev(int $_article_id, int $_book_id)
    {
        $prev_id = ModelBookArticle::where([
            ['is_pass', '=', 1],
            ['show_time', '<', time()],
            ['id', '<', $_article_id],
            ['book_id', '=', $_book_id],
        ])->max('id');

        $result = ModelBookArticle::field('id, title, book_id')
            ->where([
                ['is_pass', '=', 1],
                ['show_time', '<', time()],
                ['id', '=', $prev_id]
            ])->find();

        if ($result && $result = $result->toArray()) {
            $result['url'] = url('article/' . $result['book_id'] . '/' . $result['id']);
            $result['cat_url'] = url('list/' . $result['book_id']);
        } else {
            $result = [
                'title'   => $this->lang->get('not next'),
                'url'     => url('article/' . $_book_id . '/' . $_article_id),
                'cat_url' => url('list/' . $_book_id),
            ];
        }

        return $result;
    }
}
