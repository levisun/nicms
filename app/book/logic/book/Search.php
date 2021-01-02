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
use app\common\library\tools\Image;
use app\common\library\tools\Participle;
use app\common\library\Base64;
use app\common\model\Book as ModelBook;
use app\common\model\BookArticle as ModelBookArticle;

class Search extends BaseLogic
{

    /**
     * 查询列表
     * @access public
     * @return array
     */
    public function query(): array
    {
        // 搜索
        if ($search_key = $this->request->param('key')) {
            $search_key = htmlspecialchars_decode($search_key, ENT_QUOTES);
            $search_key = str_replace('&nbsp;', '', $search_key);
            // 搜索5个词
            $search_key = (new Participle)->words($search_key, 5);
            if ($search_key = implode('|', $search_key)) {
                $map[] = ['article.title', 'regexp', $search_key];
            }
        }

        $date_format = $this->request->param('date_format', 'Y-m-d');

        $query_limit = $this->request->param('limit/d', 20, 'abs');
        $query_limit = 100 > $query_limit && 10 < $query_limit ? intval($query_limit / 10) * 10 : 20;

        $query_page = $this->request->param('page/d', 1, 'abs');
        if ($query_page > $this->cache->get('book book search last_page' . $query_limit, $query_page)) {
            return [
                'debug' => false,
                'cache' => true,
                'msg'   => 'error',
            ];
        }

        $total = $this->cache->get('book book search total', false);
        $total = is_bool($total) ? (bool) $total : (int) $total;

        $cache_key = 'book article list' . $search_key . $query_limit . $query_page . $date_format;

        if (!$this->cache->has($cache_key) || !$list = $this->cache->get($cache_key)) {
            // 书籍信息
            $book = (new ModelBook)
                ->view('book', ['id', 'title', 'keywords', 'description', 'type_id', 'author_id', 'image', 'hits', 'origin', 'status', 'update_time'])
                ->view('book_type', ['id' => 'type_id', 'name' => 'type_name'], 'book_type.id=book.type_id', 'LEFT')
                ->view('book_author', ['author'], 'book_author.id=book.author_id', 'LEFT')
                ->where('is_pass', '=', 1)
                ->where('title', 'regexp', $search_key)
                ->find();

            if ($book && $book = $book->toArray()) {
                // 缩略图
                $book['image'] = Image::path($book['image']);

                // 书籍章节
                $result = ModelBookArticle::field(['id', 'book_id', 'title', 'update_time'])
                    ->where('is_pass', '=', '1')
                    ->where('delete_time', '=', '0')
                    ->where('show_time', '<', time())
                        // 安书籍查询
                    // ->where('book_id', '=', $book_id)
                    ->order('sort_order ASC, id ASC')
                    ->paginate([
                        'list_rows' => $query_limit,
                        'path' => 'javascript:paging([PAGE]);',
                    ], $total);

                if ($result && $list = $result->toArray()) {
                    if (!$this->cache->has('book book search total')) {
                        $this->cache->set('book book search total', $list['total'], 28800);
                    }

                    if (!$this->cache->has('book book search last_page' . $query_limit)) {
                        $this->cache->set('book book search last_page' . $query_limit, $list['last_page'], 28800);
                    }

                    $list['total'] = number_format($list['total']);
                    $list['render'] = $result->render();
                    foreach ($list['data'] as $key => $value) {
                        // 书籍文章列表链接
                        $value['cat_url'] = url('book/' . Base64::url62encode($value['book_id']));
                        // 书籍文章文章链接
                        $value['url'] = url('article/' . Base64::url62encode($value['book_id']) . '/' . Base64::url62encode($value['id']));
                        // 标识符
                        $value['flag'] = Base64::flag($value['book_id'] . $value['id'], 7);
                        // 时间格式
                        $value['update_time'] = date($date_format, (int) $value['update_time']);

                        $list['data'][$key] = $value;
                    }

                    $list['book'] = $book;
                    $this->cache->tag(['book', 'book article list' . $book_id])->set($cache_key, $list);
                }
            }
        }

        return [
            'debug' => false,
            'cache' => isset($list) ? true : false,
            'msg'   => isset($list) ? 'list' : 'error',
            'data'  => isset($list) ? [
                'book'         => $list['book'],
                'list'         => $list['data'],
                'total'        => $list['total'],
                'per_page'     => $list['per_page'],
                'current_page' => $list['current_page'],
                'last_page'    => $list['last_page'],
                'page'         => isset($list['render']) ? $list['render'] : '',
            ] : []
        ];
    }
}
