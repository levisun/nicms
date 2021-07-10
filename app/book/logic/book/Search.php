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
        $query_page = $this->request->param('page/d', 1, 'abs');
        if ($query_page > $this->ERPCache()) {
            return [
                'debug' => false,
                'cache' => true,
                'msg'   => 'error',
            ];
        }

        $cache_key = $this->getCacheKey('book search');
        if (!$this->cache->has($cache_key) || !$list = $this->cache->get($cache_key)) {
            // 书籍信息
            $model = ModelBook::view('book', ['id', 'title', 'keywords', 'description', 'type_id', 'author_id', 'image', 'hits', 'origin', 'status', 'update_time'])
                ->view('book_type', ['id' => 'type_id', 'name' => 'type_name'], 'book_type.id=book.type_id', 'LEFT')
                ->view('book_author', ['author'], 'book_author.id=book.author_id', 'LEFT')
                ->where('is_pass', '=', 1);

            // 搜索
            if ($search_key = $this->request->param('key', null, '\app\common\library\Filter::participle')) {
                $search_key = array_slice($search_key, 0, 3);
                $search_key = array_map(function ($value) {
                    return '%' . $value . '%';
                }, $search_key);
                $model->where('title', 'like', $search_key, 'OR');
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
                    // 缩略图
                    $value['image'] = File::imgUrl($value['image']);

                    $value['url'] = url('book/' . Base64::url62encode($value['id']));
                    $value['update_time'] = date($date_format, (int) $value['update_time']);

                    $list['data'][$key] = $value;
                }

                $this->cache->tag(['book', 'book article list'])->set($cache_key, $list);
            }
        }

        return [
            'debug' => false,
            'cache' => isset($list) ? true : false,
            'msg'   => isset($list) ? 'list' : 'error',
            'data'  => isset($list) ? [
                'book'         => $list['book'],
                'list'         => $list['data'],
                'per_page'     => $list['per_page'],
                'current_page' => $list['current_page'],
                'page'         => isset($list['render']) ? $list['render'] : '',
            ] : []
        ];
    }
}
