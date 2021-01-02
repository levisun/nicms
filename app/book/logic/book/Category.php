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
use app\common\model\Book as ModelBook;

class Category extends BaseLogic
{

    /**
     * 查询列表
     * @access public
     * @return array
     */
    public function query(): array
    {
        $map = [];

        if ($attribute = $this->request->param('attribute/d', 0, 'abs')) {
            $map[] = ['book.attribute', '=', $attribute];
        }

        if ($status = $this->request->param('status/d', 0, 'abs')) {
            $map[] = ['book.status', '=', $status];
        }

        if ($type_id = $this->request->param('tid', 0, '\app\common\library\Base64::url62decode')) {
            $map[] = ['book.type_id', '=', $type_id];
        }

        // 排序,为空依次安置顶,最热,推荐,自定义顺序,最新发布时间排序
        if ($sort_order = $this->request->param('sort')) {
            $sort_order = 'book.' . $sort_order;
        } else {
            $sort_order = 'book.attribute DESC, book.sort_order DESC, book.status ASC, book.id DESC';
        }

        $date_format = $this->request->param('date_format', 'Y-m-d');

        $query_limit = $this->request->param('limit/d', 20, 'abs');
        $query_limit = 100 > $query_limit && 10 < $query_limit ? intval($query_limit / 10) * 10 : 20;

        $query_page = $this->request->param('page/d', 1, 'abs');
        if ($query_page > $this->cache->get('book book category last_page' . $query_limit, $query_page)) {
            return [
                'debug' => false,
                'cache' => true,
                'msg'   => 'error',
            ];
        }

        $total = $this->cache->get('book book category total', false);
        $total = is_bool($total) ? (bool) $total : (int) $total;

        $cache_key = 'book list' . $attribute . $status . $type_id . $sort_order . $query_limit . $query_page . $date_format;

        if (!$this->cache->has($cache_key) || !$list = $this->cache->get($cache_key)) {
            $result = ModelBook::view('book', ['id', 'title', 'keywords', 'description', 'type_id', 'author_id', 'hits', 'status', 'update_time'])
                ->view('book_type', ['id' => 'type_id', 'name' => 'type_name'], 'book_type.id=book.type_id', 'LEFT')
                ->view('book_author', ['author'], 'book_author.id=book.author_id', 'LEFT')
                ->where('book.is_pass', '=', '1')
                ->where('book.lang', '=', $this->lang->getLangSet())
                ->where($map)
                ->order($sort_order)
                ->paginate([
                    'list_rows' => $query_limit,
                    'path' => 'javascript:paging([PAGE]);',
                ], $total);

            if ($result) {
                $list = $result->toArray();

                if (!$this->cache->has('book book category total')) {
                    $this->cache->set('book book category total', $list['total'], 28800);
                }

                if (!$this->cache->has('book book category last_page' . $query_limit)) {
                    $this->cache->set('book book category last_page' . $query_limit, $list['last_page'], 28800);
                }

                $list['total'] = number_format($list['total']);
                $list['render'] = $result->render();

                foreach ($list['data'] as $key => $value) {
                    $value['url'] = url('book/' . Base64::url62encode($value['id']));
                    $value['update_time'] = date($date_format, (int) $value['update_time']);

                    $list['data'][$key] = $value;
                }

                $this->cache->tag('book')->set($cache_key, $list);
            }
        }

        return [
            'debug' => false,
            'cache' => true,
            'msg'   => 'book list data',
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
}
