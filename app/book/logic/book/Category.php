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
        $query_page = $this->request->param('page/d', 1, 'abs');
        if ($query_page > $this->ERPCache()) {
            return [
                'debug' => false,
                'cache' => true,
                'msg'   => 'error',
            ];
        }

        $cache_key = $this->getCacheKey('book category');
        if (!$this->cache->has($cache_key) || !$list = $this->cache->get($cache_key)) {
            // 排序,为空依次安置顶,最热,推荐,自定义顺序,最新发布时间排序
            $sort_order = 'book.attribute DESC, book.sort_order DESC, book.status ASC, book.id DESC';
            if ($this->request->param('sort')) {
                $sort_order = 'book.' . $this->request->param('sort');
            }

            $model = ModelBook::view('book', ['id', 'title', 'type_id', 'hits', 'status', 'update_time'])
                ->view('book_type', ['id' => 'type_id', 'name' => 'type_name'], 'book_type.id=book.type_id', 'LEFT')
                ->view('book_author', ['author'], 'book_author.id=book.author_id', 'LEFT')
                ->order($sort_order)
                ->where('book.is_pass', '=', '1')
                ->where('book.lang', '=', $this->lang->getLangSet());

            // 推荐置顶最热,三选一
            if ($attribute = $this->request->param('attribute/d', 0, 'abs')) {
                $model->where('book.attribute', '=', $attribute);
            }

            // 更新状态
            if ($status = $this->request->param('status/d', 0, 'abs')) {
                $model->where('book.status', '=', $status);
            }

            // 安分类查询,为空查询所有
            if ($book_type_id = $this->request->param('book_type_id', 0, '\app\common\library\Base64::url62decode')) {
                $model->where('book.type_id', '=', $book_type_id);
            }

            $result = $model->paginate([
                'list_rows' => $this->getQueryLimit(),
                'path' => 'javascript:paging([PAGE]);',
            ], true);

            if ($result && $list = $result->toArray()) {
                if (empty($list['data'])) {
                    $this->ERPCache($query_page);
                }

                $list['render'] = $result->render();

                $date_format = $this->request->param('date_format', 'Y-m-d');
                foreach ($list['data'] as $key => $value) {
                    $value['url'] = url('book/' . Base64::url62encode($value['id']));
                    $value['update_time'] = date($date_format, (int) $value['update_time']);

                    $list['data'][$key] = $value;
                }

                $this->cache->tag('book list')->set($cache_key, $list);
            }
        }

        return [
            'debug' => false,
            'cache' => true,
            'msg'   => 'book list data',
            'data'  => [
                'list'         => $list['data'],
                'per_page'     => $list['per_page'],
                'current_page' => $list['current_page'],
                'page'         => $list['render'],
            ]
        ];
    }
}
