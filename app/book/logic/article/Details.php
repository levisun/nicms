<?php

/**
 *
 * API接口层
 * 文章内容
 *
 * @package   NICMS
 * @category  app\cms\logic\article
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace app\cms\logic\article;

use app\common\controller\BaseLogic;
use app\common\model\BookArticle as ModelBookArticle;


class Details extends BaseLogic
{

    /**
     * 查询内容
     * @access public
     * @return array
     */
    public function query(): array
    {
        if ($bid = $this->request->param('bid/d') && $id = $this->request->param('id/d')) {
            $cache_key = md5('book article details' . $bid . $id);
            if (!$this->cache->has($cache_key) || !$result = $this->cache->get($cache_key)) {
                $result = ModelBookArticle::view('book_article', ['id', 'book_id', 'title', 'content', 'hits', 'update_time'])
                    ->view('book', ['name' => 'book_name'], 'book.id=book_article.book_id')
                    ->view('book_type', ['id' => 'type_id', 'name' => 'type_name'], 'book_type.id=book.type_id')
                    ->view('user', ['username' => 'author'], 'user.id=book.author_id', 'LEFT')
                    ->where([
                        ['book_article.is_pass', '=', 1],
                        ['book_article.show_time', '<', time()],
                        ['book_article.delete_time', '=', 0],
                        ['book.is_pass', '=', 1],
                    ])
                    ->find();
            }
        }
    }

    /**
     * 更新浏览量
     * @access public
     * @return array
     */
    public function hits(): array
    {
        if ($id = $this->request->param('id/d', 0, 'abs')) {
            $map = [
                ['id', '=', $id],
            ];

            // 更新浏览数
            ModelBookArticle::where($map)
                ->inc('hits', 1, 60)
                ->update();

            $result = ModelBookArticle::where($map)->value('hits', 0);
        }

        return [
            'debug'  => false,
            'cache'  => 60,
            'msg'    => isset($result) ? 'article hits' : 'article hits error',
            'data'   => isset($result) ? ['hits' => $result] : []
        ];
    }

    /**
     * 下一篇
     * @access private
     * @param  int      $_article_id
     * @param  int      $_category_id
     * @return array
     */
    private function next(int $_article_id, int $_category_id)
    {
        $next_id = ModelArticle::where([
            ['is_pass', '=', 1],
            ['category_id', 'in', $this->child($_category_id)],
            ['show_time', '<', time()],
            ['id', '>', $_article_id],
            ['lang', '=', $this->lang->getLangSet()]
        ])->min('id');
        // ->order('is_top DESC, is_hot DESC, is_com DESC, sort_order DESC, update_time DESC')

        $result = ModelArticle::view('article', ['id', 'category_id', 'title', 'keywords', 'description', 'access_id', 'update_time'])
            ->view('category', ['name' => 'cat_name'], 'category.id=article.category_id')
            ->view('model', ['name' => 'model_name'], 'model.id=category.model_id')
            ->where([
                ['article.is_pass', '=', 1],
                ['article.show_time', '<', time()],
                ['article.id', '=', $next_id]
            ])
            ->find();

        if (null !== $result && $result = $result->toArray()) {
            $result['flag'] = Base64::flag($result['category_id'] . $result['id'], 7);
            $result['url'] = url('details/' . $result['category_id'] . '/' . $result['id']);
            $result['cat_url'] = url('list/' . $result['category_id']);
        } else {
            $result = [
                'title'   => $this->lang->get('not next'),
                'url'     => url('details/' . $_category_id . '/' . $_article_id),
                'cat_url' => url('list/' . $_category_id),
            ];
        }

        return $result;
    }

    /**
     * 上一篇
     * @access private
     * @param  int      $_article_id
     * @param  int      $_category_id
     * @return array
     */
    private function prev(int $_article_id, int $_category_id)
    {
        $prev_id = ModelArticle::where([
            ['is_pass', '=', 1],
            ['category_id', 'in', $this->child($_category_id)],
            ['show_time', '<', time()],
            ['id', '<', $_article_id],
            ['lang', '=', $this->lang->getLangSet()]
        ])->max('id');

        $result = ModelArticle::view('article', ['id', 'category_id', 'title', 'keywords', 'description', 'access_id', 'update_time'])
            ->view('category', ['name' => 'cat_name'], 'category.id=article.category_id')
            ->view('model', ['name' => 'model_name'], 'model.id=category.model_id')
            ->where([
                ['article.is_pass', '=', 1],
                ['article.show_time', '<', time()],
                ['article.id', '=', $prev_id]
            ])
            ->find();

        if (null !== $result && $result = $result->toArray()) {
            $result['flag'] = Base64::flag($result['category_id'] . $result['id'], 7);
            $result['url'] = url('details/' . $result['category_id'] . '/' . $result['id']);
            $result['cat_url'] = url('list/' . $result['category_id']);
        } else {
            $result = [
                'title'   => $this->lang->get('not prev'),
                'url'     => url('details/' . $_category_id . '/' . $_article_id),
                'cat_url' => url('list/' . $_category_id),
            ];
        }

        return $result;
    }
}
