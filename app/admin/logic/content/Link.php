<?php

/**
 *
 * API接口层
 * 友情链接
 *
 * @package   NICMS
 * @category  app\admin\logic\content
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace app\admin\logic\content;

use app\common\controller\BaseLogic;
use app\common\model\Link as ModelLink;

class Link extends BaseLogic
{
    protected $authKey = 'admin_auth_key';

    /**
     * 查询
     * @access public
     * @return array
     */
    public function query(): array
    {
        $query_limit = $this->request->param('limit/d', 10);

        $map = [];
        if ($category_id = $this->request->param('category_id/d')) {
            $map[] = ['link.category_id', '=', $category_id];
        }

        $result = (new ModelLink)
            ->view('link', ['id', 'title', 'logo', 'url', 'category_id', 'type_id'])
            ->view('type', ['name' => 'type_name'], 'type.id=link.type_id')
            ->view('category', ['name' => 'cat_name'], 'category.id=type.category_id')
            ->where($map)
            ->order('link.id DESC')
            ->paginate([
                'list_rows' => $query_limit,
                'path' => 'javascript:paging([PAGE]);',
            ]);

        $list = $result->toArray();
        $list['render'] = $result->render();

        foreach ($list['data'] as $key => $value) {
            $value['url'] = [
                'editor' => url('content/link/editor/' . $value['id']),
                'remove' => url('content/link/remove/' . $value['id']),
            ];
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
    public function added()
    {
        $this->actionLog(__METHOD__, 'admin link added');

        $receive_data = [
            'title'       => $this->request->param('title'),
            'logo'        => $this->request->param('logo'),
            'url'         => $this->request->param('url'),
            'description' => $this->request->param('description'),
            'category_id' => $this->request->param('category_id/d'),
            'model_id'    => $this->request->param('model_id/d'),
            'type_id'     => $this->request->param('type_id/d', 0),
            'admin_id'    => $this->uid,
            'is_pass'     => $this->request->param('is_pass/d', 0),
            'sort_order'  => $this->request->param('sort_order/d', 0),
            'update_time' => time(),
            'create_time' => time(),
            'lang'        => $this->lang->getLangSet()
        ];

        if ($result = $this->validate(__METHOD__, $receive_data)) {
            return $result;
        }

        (new ModelLink)->save($receive_data);

        // $this->cache->tag('cms nav')->clear();

        return [
            'debug' => false,
            'cache' => false,
            'msg'   => 'success',
        ];
    }
}
