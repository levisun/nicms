<?php

/**
 *
 * API接口层
 * 友情链接列表
 *
 * @package   NICMS
 * @category  app\cms\logic\link
 * @author    失眠小枕头 [312630173@qq.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace app\cms\logic\link;

use app\common\controller\BaseLogic;
use app\common\library\tools\File;
use app\common\model\Link as ModelLink;

class Catalog extends BaseLogic
{

    /**
     * 查询列表
     * @access public
     * @param
     * @return array
     */
    public function query()
    {
        $map = [];

        if ($category_id = $this->request->param('category_id', 0, '\app\common\library\Base64::url62decode')) {
            $map[] = ['link.category_id', '=', $category_id];
        }

        $cache_key = $this->getCacheKey('cms link catalog');
        if (!$this->cache->has($cache_key) || !$list = $this->cache->get($cache_key)) {
            $list = ModelLink::view('link link', ['id', 'category_id', 'title', 'url', 'logo'])
                ->view('category category', ['name' => 'cat_name'], 'category.id=link.category_id')
                ->view('model model', ['name' => 'action_name'], 'model.id=category.model_id')
                ->view('type type', ['id' => 'type_id', 'name' => 'type_name'], 'type.id=link.type_id', 'LEFT')
                ->where('link.is_pass', '=', '1')
                ->where('link.lang', '=', $this->lang->getLangSet())
                ->where($map)
                ->order('link.type_id DESC, link.sort_order DESC, link.id DESC')
                ->select()
                ->toArray();

            foreach ($list as $key => $value) {
                $value['logo'] = File::imgUrl($value['logo']);
                $list[$key] = $value;
            }

            $this->cache->tag('cms link list' . $category_id)->set($cache_key, $list);
        }

        return [
            'debug' => false,
            'cache' => true,
            'msg'   => isset($list) ? 'link' : 'error',
            'data'  => isset($list) ? [
                'list'  => $list,
                'total' => count($list),
            ] : []
        ];
    }
}
