<?php

/**
 *
 * API接口层
 * 书籍
 *
 * @package   NICMS
 * @category  app\admin\logic\content
 * @author    失眠小枕头 [312630173@qq.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace app\admin\logic\book;

use app\common\controller\BaseLogic;
use app\common\library\tools\Participle;
use app\common\library\Filter;
use app\common\model\BookArticle as ModelBookArticle;

class Article extends BaseLogic
{
    protected $authKey = 'admin_auth_key';

    /**
     * 查询
     * @access public
     * @return array
     */
    public function query(): array
    {
        $map = [];

        // 安审核条件查询,为空查询所有
        if ($is_pass = $this->request->param('pass/d', 0, 'abs')) {
            $is_pass = $is_pass >= 1 ? 1 : 0;
            $map[] = ['is_pass', '=', $is_pass];
        }

        // 搜索
        if ($search_key = $this->request->param('key')) {
            $search_key = (new Participle)->words($search_key, 3);
            if (!empty($search_key)) {
                $map[] = ['title', 'regexp', implode('|', $search_key)];
            }
        }

        $date_format = $this->request->param('date_format', 'Y-m-d H:i:s');
        $query_limit = $this->request->param('limit/d', 20, 'abs');
        $query_limit = 100 > $query_limit ? $query_limit : 20;

        $query_page = $this->request->param('page/d', 1, 'abs');
        if ($query_page > $this->cache->get('admin book article last_page' . $query_limit, $query_page)) {
            return [
                'debug' => false,
                'cache' => true,
                'msg'   => 'error',
            ];
        }

        $total = $this->cache->get('admin book article total', false);
        $total = is_bool($total) ? $total : (int) $total;

        $result = ModelBookArticle::where('delete_time', '=', '0')
            ->where('lang', '=', $this->lang->getLangSet())
            ->where($map)
            ->order('is_pass ASC, sort_order DESC, id DESC')
            ->paginate([
                'list_rows' => $query_limit,
                'path' => 'javascript:paging([PAGE]);',
            ], $total);

        $list = $result->toArray();

        if (!$this->cache->has('admin book article total')) {
            $this->cache->set('admin book article total', $list['total'], 28800);
        }

        if (!$this->cache->has('admin book article last_page' . $query_limit)) {
            $this->cache->set('admin book article last_page' . $query_limit, $list['last_page'], 28800);
        }

        $list['total'] = number_format($list['total']);
        $list['render'] = $result->render();

        foreach ($list['data'] as $key => $value) {
            $value['url'] = [
                'editor' => url('book/article/editor/' . $value['id']),
                'remove' => url('book/article/remove/' . $value['id']),
            ];

            // 时间格式
            $value['update_time'] = date($date_format, (int) $value['update_time']);

            $list['data'][$key] = $value;
        }

        return [
            'debug' => false,
            'cache' => false,
            'msg'   => 'success',
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

    /**
     * 添加
     * @access public
     * @return array
     */
    public function added(): array
    {
        $this->actionLog('admin book added');

        $receive_data = [
            'book_id'    => $this->request->param('book_id/d', 0, 'abs'),
            'title'      => $this->request->param('title'),
            'content'    => $this->request->param('content', '', '\app\common\library\Filter::contentEncode'),
            'is_pass'    => $this->request->param('is_pass/d', 0, 'abs'),
            'sort_order' => $this->request->param('sort_order/d', 0, 'abs'),
            'show_time'  => $this->request->param('show_time/d', 0, 'abs'),
            'update_time' => time(),
            'create_time' => time(),
        ];
        if ($result = $this->validate($receive_data)) {
            return $result;
        }

        ModelBookArticle::create($receive_data);

        $this->cache->tag('book')->clear();

        return [
            'debug' => false,
            'cache' => false,
            'msg'   => 'success',
        ];
    }

    /**
     * 查询
     * @access public
     * @return array
     */
    public function find(): array
    {
        $result = [];
        if ($id = $this->request->param('id/d', 0, 'abs')) {
            $result = ModelBookArticle::where('book.id', '=', $id)->find();

            if ($result && $result = $result->toArray()) {
                $result['show_time'] = $result['show_time'] ? date('Y-m-d', $result['show_time']) : date('Y-m-d');
                $result['content'] = Filter::contentDecode($result['content']);
            }
        }

        return [
            'debug' => false,
            'cache' => false,
            'msg'   => 'success',
            'data'  => $result
        ];
    }

    /**
     * 编辑
     * @access public
     * @return array
     */
    public function editor(): array
    {
        $this->actionLog('admin book editor');

        if (!$id = $this->request->param('id/d', 0, 'abs')) {
            return [
                'debug' => false,
                'cache' => false,
                'code'  => 40001,
                'msg'   => 'error'
            ];
        }

        $receive_data = [
            'book_id'    => $this->request->param('book_id/d', 0, 'abs'),
            'title'      => $this->request->param('title'),
            'content'    => $this->request->param('content', '', '\app\common\library\Filter::contentEncode'),
            'is_pass'    => $this->request->param('is_pass/d', 0, 'abs'),
            'sort_order' => $this->request->param('sort_order/d', 0, 'abs'),
            'show_time'  => $this->request->param('show_time/d', 0, 'abs'),
            'update_time' => time(),
        ];
        if ($result = $this->validate($receive_data)) {
            return $result;
        }

        ModelBookArticle::where('id', '=', $id)->limit(1)->update($receive_data);

        $this->cache->tag('book')->clear();

        return [
            'debug' => false,
            'cache' => false,
            'msg'   => 'success'
        ];
    }

    /**
     * 删除
     * @access public
     * @return array
     */
    public function remove(): array
    {
        $this->actionLog('admin book remove');

        if (!$id = $this->request->param('id/d', 0, 'abs')) {
            return [
                'debug' => false,
                'cache' => false,
                'code'  => 40001,
                'msg'   => 'error'
            ];
        }

        ModelBookArticle::where('id', '=', $id)->limit(1)->delete();

        $this->cache->tag('book')->clear();

        return [
            'debug' => false,
            'cache' => false,
            'msg'   => 'success'
        ];
    }
}
