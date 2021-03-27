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
use app\common\library\UploadLog;
use app\common\model\Book as ModelBook;
use app\common\model\BookArticle as ModelBookArticle;

class Book extends BaseLogic
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
            $map[] = ['book.is_pass', '=', $is_pass];
        }

        // 搜索
        if ($search_key = $this->request->param('key', null, '\app\common\library\Filter::nonChsAlpha')) {
            $like = explode(' ', $search_key);
            $like = array_map('trim', $like);
            $like = array_filter($like);
            $like = array_unique($like);
            $like = array_slice($like, 0, 3);
            $like = array_map(function ($value) {
                return '%' . $value . '%';
            }, $like);
            $map[] = ['book.title', 'like', $like, 'OR'];
        }

        $date_format = $this->request->param('date_format', 'Y-m-d H:i:s');

        $query_limit = $this->request->param('limit/d', 20, 'abs');
        $query_limit = 100 > $query_limit && 10 < $query_limit ? intval($query_limit / 10) * 10 : 20;

        $query_page = $this->request->param('page/d', 1, 'abs');
        if ($query_page > $this->ERPCache()) {
            return [
                'debug' => false,
                'cache' => true,
                'msg'   => 'error',
            ];
        }

        $result = ModelBook::view('book', ['id', 'title', 'type_id', 'is_pass', 'attribute', 'status', 'hits', 'sort_order', 'update_time'])
            ->view('book_type', ['name' => 'type_name'], 'book_type.id=book.type_id', 'LEFT')
            ->view('book_author', ['author'], 'book_author.id=book.author_id', 'LEFT')
            ->where('book.delete_time', '=', '0')
            ->where('book.lang', '=', $this->lang->getLangSet())
            ->where($map)
            ->order('book.is_pass ASC, book.status ASC, book.attribute DESC, book.sort_order DESC, book.id DESC')
            ->paginate([
                'list_rows' => $query_limit,
                'path' => 'javascript:paging([PAGE]);',
            ], true);

        if ($result && $list = $result->toArray()) {
            $this->ERPCache($query_page);

            $list['render'] = $result->render();

            foreach ($list['data'] as $key => $value) {
                $value['url'] = [
                    'editor' => url('book/book/editor/' . $value['id']),
                    'remove' => url('book/book/remove/' . $value['id']),
                ];

                // 时间格式
                $value['update_time'] = date($date_format, (int) $value['update_time']);

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
     * 添加
     * @access public
     * @return array
     */
    public function added(): array
    {
        $this->actionLog('admin book added');

        $receive_data = [
            'title'       => $this->request->param('title'),
            'keywords'    => $this->request->param('keywords'),
            'description' => $this->request->param('description'),
            'image'       => $this->request->param('image'),
            'author_id'   => $this->request->param('author_id/d', 0, 'abs'),
            'type_id'     => $this->request->param('type_id/d', 0, 'abs'),
            'is_pass'     => $this->request->param('is_pass/d', 0, 'abs'),
            'attribute'   => $this->request->param('attribute/d', 0, 'abs'),
            'sort_order'  => $this->request->param('sort_order/d', 0, 'abs'),
            'status'      => $this->request->param('status/d', 0, 'abs'),
            'origin'      => $this->request->param('origin'),
            'update_time' => time(),
            'create_time' => time(),
            'lang'        => $this->lang->getLangSet()
        ];
        if ($result = $this->validate($receive_data)) {
            return $result;
        }

        ModelBook::create($receive_data);

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
            $result = ModelBook::view('book', ['id', 'title', 'keywords', 'description', 'type_id', 'is_pass', 'image', 'origin', 'attribute', 'status', 'sort_order', 'hits', 'author_id', 'update_time', 'lang'])
                ->view('book_type', ['name' => 'type_name'], 'book_type.id=book.type_id', 'LEFT')
                ->view('book_author', ['author'], 'book_author.id=book.author_id', 'LEFT')
                ->where('book.id', '=', $id)
                ->find();

            if ($result && $result = $result->toArray()) {
                $result['image'] = $result['image']
                    ? $this->config->get('app.img_host') . $result['image']
                    : '';
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
            'title'       => $this->request->param('title'),
            'keywords'    => $this->request->param('keywords'),
            'description' => $this->request->param('description'),
            'image'       => $this->request->param('image'),
            'author_id'   => $this->request->param('author_id/d', 0, 'abs'),
            'type_id'     => $this->request->param('type_id/d', 0, 'abs'),
            'is_pass'     => $this->request->param('is_pass/d', 0, 'abs'),
            'attribute'   => $this->request->param('attribute/d', 0, 'abs'),
            'sort_order'  => $this->request->param('sort_order/d', 0, 'abs'),
            'status'      => $this->request->param('status/d', 0, 'abs'),
            'origin'      => $this->request->param('origin'),
            'update_time' => time(),
        ];
        if ($result = $this->validate($receive_data)) {
            return $result;
        }

        // 删除旧图片
        $image = ModelBook::where('id', '=', $id)->value('image');
        if ($image !== $receive_data['image']) {
            UploadLog::remove($image);
            UploadLog::update($receive_data['image'], 1);
        }

        ModelBook::where('id', '=', $id)->limit(1)->update($receive_data);

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

        $image = ModelBook::where('id', '=', $id)->value('image');

        if (null !== $image && $image) {
            UploadLog::remove($image);
        }

        ModelBook::where('id', '=', $id)->limit(1)->delete();
        ModelBookArticle::where('book_id', '=', $id)->delete();

        $this->cache->tag('book')->clear();

        return [
            'debug' => false,
            'cache' => false,
            'msg'   => 'success'
        ];
    }
}
