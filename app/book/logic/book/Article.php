<?php

/**
 *
 * API接口层
 * 文章列表
 *
 * @package   NICMS
 * @category  app\book\logic\article
 * @author    失眠小枕头 [312630173@qq.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace app\book\logic\book;

use app\common\controller\BaseLogic;
use app\common\library\Base64;
use app\common\library\Filter;
use app\common\model\BookArticle as ModelBookArticle;

class Article extends BaseLogic
{

    /**
     * 查询内容
     * @access public
     * @return array
     */
    public function query(): array
    {
        $book_id = $this->request->param('book_id', 0, '\app\common\library\Base64::url62decode');
        $id = $this->request->param('id', 0, '\app\common\library\Base64::url62decode');

        $cache_key = $this->getCacheKey('book article');
        if (!$this->cache->has($cache_key) || !$result = $this->cache->get($cache_key)) {
            $result = ModelBookArticle::view('book_article', ['id', 'book_id', 'title', 'content', 'update_time'])
                ->view('book', ['title' => 'book_title', 'hits', 'origin', 'status'], 'book.id=book_article.book_id')
                ->where('book_article.book_id', '=', $book_id)
                ->where('book_article.id', '=', $id)
                ->find();

            if ($result && $result = $result->toArray()) {
                // 书籍文章列表链接
                $result['book_url'] = url('book/' . Base64::url62encode($result['book_id']));
                // 书籍文章文章链接
                $result['url'] = url('article/' . Base64::url62encode($result['book_id']) . '/' . Base64::url62encode($result['id']));
                // 内容
                $result['content'] = Filter::htmlDecode($result['content']);
                // 时间格式
                $date_format = $this->request->param('date_format', 'Y-m-d');
                $result['update_time'] = date($date_format, (int) $result['update_time']);
                // 标识符
                $result['flag'] = Base64::flag($result['book_id'] . $result['id'], 7);

                $result['title'] = Filter::htmlDecode($result['title']);


                $result['next'] = $this->next($result['id'], $result['book_id']);
                $result['prev'] = $this->prev($result['id'], $result['book_id']);

                $result['id'] = Base64::url62encode($result['id']);
                $result['book_id'] = Base64::url62encode($result['book_id']);

                $this->cache->tag('book article')->set($cache_key, $result);
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
        $next_id = ModelBookArticle::where('is_pass', '=', 1)
            ->where('is_pass', '=', 1)
            ->whereTime('show_time', '<', time())
            ->where('id', '>', $_article_id)
            ->where('book_id', '=', $_book_id)
            ->order('sort_order ASC, id ASC')
            ->min('id');

        $result = ModelBookArticle::field('id, title, book_id')
            ->where('is_pass', '=', 1)
            ->whereTime('show_time', '<', time())
            ->where('id', '=', $next_id)
            ->find();

        if ($result && $result = $result->toArray()) {
            $result['id'] = Base64::url62encode($result['id']);
            $result['book_id'] = Base64::url62encode($result['book_id']);
            $result['flag'] = Base64::flag($result['book_id'] . $result['id'], 7);
            $result['url'] = url('article/' . $result['book_id'] . '/' . $result['id']);
            $result['cat_url'] = url('list/' . $result['book_id']);
        }

        return $result ?: '';
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
        $prev_id = ModelBookArticle::where('is_pass', '=', 1)
            ->where('is_pass', '=', 1)
            ->whereTime('show_time', '<', time())
            ->where('id', '<', $_article_id)
            ->where('book_id', '=', $_book_id)
            ->order('sort_order ASC, id ASC')
            ->max('id');

        $result = ModelBookArticle::field('id, title, book_id')
            ->where('is_pass', '=', 1)
            ->whereTime('show_time', '<', time())
            ->where('id', '=', $prev_id)
            ->find();

        if ($result && $result = $result->toArray()) {
            $result['id'] = Base64::url62encode($result['id']);
            $result['book_id'] = Base64::url62encode($result['book_id']);
            $result['flag'] = Base64::flag($result['book_id'] . $result['id'], 7);
            $result['url'] = url('article/' . $result['book_id'] . '/' . $result['id']);
            $result['cat_url'] = url('list/' . $result['book_id']);
        }

        return $result ?: '';
    }
}
