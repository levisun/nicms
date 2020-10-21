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
        $map = [
            ['book.delete_time', '=', '0'],
            ['book.lang', '=', $this->lang->getLangSet()]
        ];

        // 安审核条件查询,为空查询所有
        if ($is_pass = $this->request->param('pass/d', 0, 'abs')) {
            $is_pass = $is_pass >= 1 ? 1 : 0;
            $map[] = ['book.is_pass', '=', $is_pass];
        }

        // 搜索
        if ($search_key = $this->request->param('key')) {
            $search_key = (new Participle)->words($search_key, 3);
            if (!empty($search_key)) {
                $map[] = ['book.title', 'regexp', implode('|', $search_key)];
            }
        }

        $query_limit = $this->request->param('limit/d', 20, 'abs');
        $query_limit = $query_limit <= 0 ? 20 : $query_limit;
        $query_limit = $query_limit > 100 ? 20 : $query_limit;

        $date_format = $this->request->param('date_format', 'Y-m-d H:i:s');

        $result = ModelBook::view('book', ['id', 'title', 'type_id', 'is_pass', 'is_com', 'is_hot', 'is_top', 'hits', 'sort_order', 'update_time'])
            ->view('book_type', ['name' => 'type_name'], 'book_type.id=book.type_id', 'LEFT')
            ->view('book_author', ['author'], 'book_author.id=book.author_id', 'LEFT')
            ->where($map)
            ->order('book.is_pass ASC, book.is_top DESC, book.is_hot DESC , book.is_com DESC, book.sort_order DESC, book.update_time DESC')
            ->paginate([
                'list_rows' => $query_limit,
                'path' => 'javascript:paging([PAGE]);',
            ]);

        $list = $result->toArray();
        $list['total'] = number_format($list['total']);
        $list['render'] = $result->render();

        foreach ($list['data'] as $key => $value) {
            $value['url'] = [
                'editor' => url('book/book/editor/' . $value['id']),
                'remove' => url('book/book/remove/' . $value['id']),

                // 栏目链接
                'type_url' => $this->config->get('app.app_host') . url('category/' . $value['type_id']),
                // 文章链接
                'url' => $this->config->get('app.app_host') . url('book/' . $value['id']),
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
        $this->actionLog(__METHOD__, 'admin book added');

        $receive_data = [
            'title'       => $this->request->param('title'),
            'keywords'    => $this->request->param('keywords'),
            'description' => $this->request->param('description'),
            'image'       => $this->request->param('image'),
            'author_id'   => $this->request->param('author_id/d', 1, 'abs'),
            'type_id'     => $this->request->param('type_id/d', 1, 'abs'),
            'is_pass'     => $this->request->param('is_pass/d', 1, 'abs'),
            'is_com'      => $this->request->param('is_com/d', 0, 'abs'),
            'is_top'      => $this->request->param('is_top/d', 0, 'abs'),
            'is_hot'      => $this->request->param('is_hot/d', 0, 'abs'),
            'sort_order'  => $this->request->param('sort_order/d', 0, 'abs'),
            'status'      => $this->request->param('status/d', 0, 'abs'),
            'origin'      => $this->request->param('origin'),
            'update_time' => time(),
            'create_time' => time(),
            'lang'        => $this->lang->getLangSet()
        ];
        if ($result = $this->validate(__METHOD__, $receive_data)) {
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
            $result = ModelBook::view('book', ['id', 'title', 'keywords', 'description', 'type_id', 'is_pass', 'image', 'is_com', 'is_top', 'is_hot', 'sort_order', 'hits', 'author_id', 'update_time', 'lang'])
                ->view('book_type', ['name' => 'type_name'], 'book_type.id=book.type_id', 'LEFT')
                ->view('book_author', ['author'], 'book_author.id=book.author_id', 'LEFT')
                ->where([
                    ['book.id', '=', $id],
                ])
                ->find();

            if ($result && $result = $result->toArray()) {
                $result['image'] = $result['image']
                    ? $this->config->get('app.img_host') . '/' . $result['image']
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
        $this->actionLog(__METHOD__, 'admin book editor');

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
            'author_id'   => $this->request->param('author_id/d', 1, 'abs'),
            'type_id'     => $this->request->param('type_id/d', 1, 'abs'),
            'is_pass'     => $this->request->param('is_pass/d', 1, 'abs'),
            'is_com'      => $this->request->param('is_com/d', 0, 'abs'),
            'is_top'      => $this->request->param('is_top/d', 0, 'abs'),
            'is_hot'      => $this->request->param('is_hot/d', 0, 'abs'),
            'sort_order'  => $this->request->param('sort_order/d', 0, 'abs'),
            'status'      => $this->request->param('status/d', 0, 'abs'),
            'origin'      => $this->request->param('origin'),
            'update_time' => time(),
        ];
        if ($result = $this->validate(__METHOD__, $receive_data)) {
            return $result;
        }

        // 删除旧图片
        $image = ModelBook::where([
            ['id', '=', $id],
        ])->value('image');
        if ($image !== $receive_data['image']) {
            UploadLog::remove($image);
            UploadLog::update($receive_data['image'], 1);
        }

        ModelBook::update($receive_data, ['id' => $id]);

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
        $this->actionLog(__METHOD__, 'admin book remove');

        if (!$id = $this->request->param('id/d', 0, 'abs')) {
            return [
                'debug' => false,
                'cache' => false,
                'code'  => 40001,
                'msg'   => 'error'
            ];
        }

        $image = ModelBook::where([
            ['id', '=', $id],
            ['lang', '=', $this->lang->getLangSet()]
        ])->value('image');

        if (null !== $image && $image) {
            UploadLog::remove($image);
        }

        ModelBook::where([
            ['id', '=', $id]
        ])->delete();

        $this->cache->tag('book')->clear();

        return [
            'debug' => false,
            'cache' => false,
            'msg'   => 'success'
        ];
    }
}
