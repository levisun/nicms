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
use app\common\library\tools\File;
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
        if ($search_key = $this->request->param('key', null, '\app\common\library\Filter::nonChsAlpha')) {
            $like = explode(' ', $search_key);
            $like = array_map('trim', $like);
            $like = array_filter($like);
            $like = array_unique($like);
            $like = array_map(function ($value) {
                return '%' . $value . '%';
            }, $like);
            $map[] = ['article.title', 'like', $like, 'OR'];
        }

        $date_format = $this->request->param('date_format', 'Y-m-d');

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

        if (!$this->cache->has($this->getCacheKey()) || !$list = $this->cache->get($this->getCacheKey())) {
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
                $book['image'] = File::pathToUrl($book['image']);

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
                    ], $this->getTotalCache());

                if ($result && $list = $result->toArray()) {
                    $this->setTotalPageCache($list['total'], $list['last_page']);

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
                    $this->cache->tag(['book', 'book article list'])->set($this->getCacheKey(), $list);
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
