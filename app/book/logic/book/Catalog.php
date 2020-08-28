<?php

/**
 *
 * API接口层
 * 书籍目录
 *
 * @package   NICMS
 * @category  app\book\logic\book
 * @author    失眠小枕头 [312630173@qq.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace app\book\logic\book;

use app\common\controller\BaseLogic;
use app\common\library\Base64;
use app\common\library\Image;
use app\common\model\Book as ModelBook;
use app\common\model\BookArticle as ModelBookArticle;

class Catalog extends BaseLogic
{

    /**
     * 查询列表
     * @access public
     * @return array
     */
    public function query(): array
    {
        $book_id = $this->request->param('id', 0, '\app\common\library\Base64::url62decode');
        $map = [
            ['is_pass', '=', '1'],
            ['delete_time', '=', '0'],
            ['show_time', '<', time()],
            // 安书籍查询
            ['book_id', '=', $book_id]
        ];

        $query_limit = $this->request->param('limit/d', 20, 'abs');
        $query_page = $this->request->param('page/d', 1, 'abs');
        $date_format = $this->request->param('date_format', 'Y-m-d');
        $sort_order = 'sort_order ASC, id ASC';

        $cache_key = 'book article list' . $book_id . $query_limit . $query_page . $date_format;

        if (!$this->cache->has($cache_key) || !$result = $this->cache->get($cache_key)) {
            // 书籍信息
            $book = (new ModelBook)
                ->view('book', ['id', 'title', 'keywords', 'description', 'type_id', 'author_id', 'image', 'hits', 'origin', 'status', 'update_time'])
                ->view('book_type', ['id' => 'type_id', 'name' => 'type_name'], 'book_type.id=book.type_id', 'LEFT')
                ->view('book_author', ['author'], 'book_author.id=book.author_id', 'LEFT')
                ->where([
                    ['book.id', '=', $book_id],
                    ['is_pass', '=', '1'],
                ])
                ->find();


            if ($book && $book = $book->toArray()) {
                // 缩略图
                $book['image'] = Image::path($book['image']);

                // 书籍章节
                $result = ModelBookArticle::field(['id', 'book_id', 'title', 'update_time'])
                    ->where($map)
                    ->order($sort_order)
                    ->paginate([
                        'list_rows' => $query_limit,
                        'path' => 'javascript:paging([PAGE]);',
                    ], true);

                if ($result && $list = $result->toArray()) {
                    $list['render'] = $result->render();
                    $list['total'] = number_format($list['total']);
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

                    $this->cache->tag(['book', 'book article list' . $book_id])->set($cache_key, $list);
                }

                $list['book'] = $book;
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
