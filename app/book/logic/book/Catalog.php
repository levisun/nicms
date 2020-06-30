<?php

/**
 *
 * API接口层
 * 书籍目录
 *
 * @package   NICMS
 * @category  app\cms\logic\book
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace app\cms\logic\book;

use app\common\controller\BaseLogic;
use app\common\library\Base64;
use app\common\library\Image;
use app\common\model\BookArticle as ModelBookArticle;
use app\common\model\BookType as ModelBookType;

class Catalog extends BaseLogic
{

    /**
     * 查询列表
     * @access public
     * @return array
     */
    public function query(): array
    {
        $book_id = $this->request->param('id/d', 0, 'abs');
        $map = [
            ['book_article.is_pass', '=', '1'],
            ['book_article.delete_time', '=', '0'],
            ['book_article.show_time', '<', time()],
            ['book_article.lang', '=', $this->lang->getLangSet()],
            // 安书籍查询
            ['book_article.book_id', '=', $book_id]
        ];

        $query_limit = $this->request->param('limit/d', 20, 'abs');
        $query_page = $this->request->param('page/d', 1, 'abs');
        $date_format = $this->request->param('date_format', 'Y-m-d');
        $sort_order = 'book_article.sort_order DESC, book_article.id DESC';

        $cache_key = 'book article list'. $book_id . $query_limit . $query_page . $date_format;
        $cache_key = md5($cache_key);

        if (!$this->cache->has($cache_key) || !$list = $this->cache->get($cache_key)) {
            $result = ModelBookArticle::view('book_article', ['id', 'book_id', 'title', 'keywords', 'description', 'hits', 'update_time'])
                ->view('book', ['title' => 'book_name', 'image'], 'book.id=book_article.book_id')
                ->view('user', ['username' => 'author'], 'user.id=book.author_id', 'LEFT')
                ->where($map)
                ->order($sort_order)
                ->paginate([
                    'list_rows' => $query_limit,
                    'path' => 'javascript:paging([PAGE]);',
                ]);

            if ($result) {
                $list = $result->toArray();
                $list['render'] = $result->render();
                foreach ($list['data'] as $key => $value) {
                    // 书籍文章列表链接
                    $value['cat_url'] = url('book/' . $value['book_id']);
                    // 书籍文章文章链接
                    $value['url'] = url('details/' . $value['book_id'] . '/' . $value['id']);
                    // 标识符
                    $value['flag'] = Base64::flag($value['book_id'] . $value['id'], 7);
                    // 缩略图
                    $value['image'] = Image::path($value['image']);
                    // 时间格式
                    $value['update_time'] = date($date_format, (int) $value['update_time']);
                    // 作者
                    $value['author'] = $value['author'];

                    $list['data'][$key] = $value;
                }

                $this->cache->tag(['book', 'book article list' . $book_id])->set($cache_key, $list);
            }
        }

        return [
            'debug' => false,
            'cache' => $list ? true : false,
            'msg'   => $list ? 'category' : 'error',
            'data'  => $list ? [
                'list'         => $list['data'],
                'total'        => $list['total'],
                'per_page'     => $list['per_page'],
                'current_page' => $list['current_page'],
                'last_page'    => $list['last_page'],
                'page'         => $list['render'],
            ] : []
        ];
    }
}
