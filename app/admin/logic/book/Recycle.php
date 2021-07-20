<?php

/**
 *
 * API接口层
 * 文章
 *
 * @package   NICMS
 * @category  app\admin\logic\content
 * @author    失眠小枕头 [312630173@qq.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace app\admin\logic\content;

use app\common\controller\BaseLogic;
use app\common\library\Filter;
use app\common\model\BookArticle as ModelBookArticle;

class Recycle extends BaseLogic
{
    protected $authKey = 'admin_auth_key';

    /**
     * 查询
     * @access public
     * @return array
     */
    public function query(): array
    {
        $model = ModelBookArticle::view('book_article', ['id', 'book_id', 'title', 'is_pass', 'update_time'])
            ->view('book', ['title' => 'book_name'], 'book.id=book_article.book_id')
            ->where('book_article.delete_time', '<>', '0')
            ->order('book_article.is_pass ASC, book_article.id DESC');

        // 安审核条件查询,为空查询所有
        if ($is_pass = $this->request->param('pass/d', 0, 'abs')) {
            $is_pass = $is_pass >= 1 ? 1 : 0;
            $model->where('book_article.is_pass', '=', $is_pass);
        }

        // 搜索
        if ($search_key = $this->request->param('key', null, '\app\common\library\Filter::participle')) {
            $search_key = array_slice($search_key, 0, 3);
            $search_key = array_map(function ($value) {
                return '%' . $value . '%';
            }, $search_key);
            $model->where('book_article.title', 'like', $search_key, 'OR');
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

            foreach ($list['data'] as $key => $value) {
                $value['url'] = [
                    'editor' => url('book/article/editor/' . $value['id']),
                    'remove' => url('book/article/remove/' . $value['id']),
                ];

                // 时间格式
                $value['update_time'] = date($date_format, (int) $value['update_time']);
                $value['title'] = Filter::htmlDecode($value['title']);

                $list['data'][$key] = $value;
            }
        }

        return [
            'debug' => false,
            'cache' => false,
            'msg'   => 'success',
            'data'  => [
                'list'         => $list['data'],
                'per_page'     => $list['per_page'],
                'current_page' => $list['current_page'],
                'page'         => $list['render'],
            ]
        ];
    }

    /**
     * 还原
     * @access public
     * @return array
     */
    public function recover(): array
    {
        if (!$id = $this->request->param('id/d', 0, 'abs')) {
            return [
                'debug' => false,
                'cache' => false,
                'code'  => 40001,
                'msg'   => 'error'
            ];
        }

        $book_id = ModelBookArticle::where('id', '=', $id)->value('book_id');

        if ($book_id) {
            $this->actionLog('admin content recover ID:' . $id);
            ModelBookArticle::where('id', '=', $id)->limit(1)->update([
                'delete_time' => 0
            ]);

            // 清除缓存
            $this->cache->tag('book article list' . $book_id)->clear();
        }

        return [
            'debug' => false,
            'cache' => false,
            'msg'   => 'success'
        ];
    }

    /**
     * 物理删除
     * @access public
     * @return array
     */
    public function remove(): array
    {
        if (!$id = $this->request->param('id/d', 0, 'abs')) {
            return [
                'debug' => false,
                'cache' => false,
                'code'  => 40001,
                'msg'   => 'error'
            ];
        }

        $this->actionLog('admin content remove ID:' . $id);
        ModelBookArticle::where('id', '=', $id)->limit(1)->delete();

        return [
            'debug' => false,
            'cache' => false,
            'msg'   => 'success'
        ];
    }
}
