<?php

/**
 *
 * API接口层
 * 文章列表
 *
 * @package   NICMS
 * @category  app\cms\logic\link
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace app\cms\logic\link;

use app\common\controller\BaseLogic;
use app\common\library\Canvas;
use app\common\model\Link as ModelLink;

class Lists extends BaseLogic
{

    /**
     * 查询列表
     * @access public
     * @param
     * @return array
     */
    public function query()
    {
        $map = [
            ['link.is_pass', '=', '1'],
            ['link.lang', '=', $this->lang->getLangSet()]
        ];

        if ($category_id = $this->request->param('cid/d', 0)) {
            $map[] = ['article.category_id', '=', $category_id];
        }

        $cache_key = 'link list' . date('Ymd') . $category_id;
        $cache_key = md5($cache_key);

        if (!$this->cache->has($cache_key) || !$list = $this->cache->get($cache_key)) {
            $list = (new ModelLink)
                ->view('link link', ['id', 'category_id', 'title', 'url', 'logo'])
                ->view('category category', ['name' => 'cat_name'], 'category.id=link.category_id')
                ->view('model model', ['name' => 'action_name'], 'model.id=category.model_id')
                ->view('type type', ['id' => 'type_id', 'name' => 'type_name'], 'type.id=link.type_id', 'LEFT')
                ->where($map)
                ->order('link.sort_order DESC, link.id DESC')
                ->select()
                ->toArray();

            foreach ($list as $key => $value) {
                $value['logo'] = (new Canvas)->image($value['logo']);

                $list[$key] = $value;
            }

            $this->cache->tag([
                'cms',
                'cms link list' . $category_id
            ])->set($cache_key, $list);
        }

        return [
            'debug' => false,
            'cache' => false,
            'msg'   => $list ? 'link' : 'error',
            'data'  => [
                'list'  => $list,
                'total' => count($list),
            ]
        ];
    }
}
