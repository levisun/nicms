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
        $map = [
            ['book.is_pass', '=', '1'],
            ['book.status', '=', '1'],
            ['book.lang', '=', $this->lang->getLangSet()]
        ];

        if ($com = $this->request->param('com/d', 0, 'abs')) {
            $map[] = ['book.is_com', '=', '1'];
        } elseif ($top = $this->request->param('top/d', 0, 'abs')) {
            $map[] = ['book.is_top', '=', '1'];
        } elseif ($hot = $this->request->param('hot/d', 0, 'abs')) {
            $map[] = ['book.is_hot', '=', '1'];
        }

        if ($type_id = $this->request->param('tid', 0, '\app\common\library\Base64::url62decode')) {
            $map[] = ['book.type_id', '=', $type_id];
        }

        $query_limit = $this->request->param('limit/d', 20, 'abs');

        $query_page = $this->request->param('page/d', 1, 'abs');
        $date_format = $this->request->param('date_format', 'Y-m-d');

        $cache_key = __METHOD__ . date('Ymd') .
            $com . $top . $hot . $type_id .
            $query_limit . $query_page . $date_format;
        $cache_key = md5($cache_key);

        if (!$this->cache->has($cache_key) || !$list = $this->cache->get($cache_key)) {
            $result = (new ModelBook)
                ->view('book', ['id', 'title', 'keywords', 'description', 'type_id', 'author_id', 'hits', 'update_time'])
                ->view('book_type', ['id' => 'type_id', 'name' => 'type_name'], 'book_type.id=book.type_id', 'LEFT')
                ->view('book_author', ['author'], 'book_author.id=book.author_id', 'LEFT')
                ->where($map)
                ->order('book.is_top DESC, book.is_hot DESC , book.is_com DESC, book.sort_order DESC')
                ->paginate([
                    'list_rows' => $query_limit,
                    'path' => 'javascript:paging([PAGE]);',
                ]);

            if ($result) {
                $list = $result->toArray();
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
