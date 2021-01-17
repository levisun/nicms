<?php

/**
 *
 * API接口层
 * 侧导航
 *
 * @package   NICMS
 * @category  app\cms\logic\nav
 * @author    失眠小枕头 [312630173@qq.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace app\cms\logic\nav;

use app\common\controller\BaseLogic;
use app\common\library\tools\File;
use app\common\library\Base64;
use app\common\model\Category as ModelCategory;

class Sidebar extends BaseLogic
{

    /**
     * 侧导航
     * @access public
     * @param
     * @return array
     */
    public function query(): array
    {
        if ($category_id = $this->request->param('category_id', 0, '\app\common\library\Base64::url62decode')) {
            $cache_key = 'nav sidebar' . $category_id;
            if (!$this->cache->has($cache_key) || !$result = $this->cache->get($cache_key)) {
                $id = $this->parent((int) $category_id);
                $result = ModelCategory::view('category', ['id', 'name', 'aliases', 'image', 'is_channel', 'access_id'])
                    ->view('model', ['name' => 'action_name'], 'model.id=category.model_id')
                    ->view('level', ['name' => 'level_name'], 'level.id=category.access_id', 'LEFT')
                    ->where('category.is_show', '=', 1)
                    ->where('category.id', '=', $id)
                    ->find();

                if ($result && $result = $result->toArray()) {
                    $result['id'] = (int) $result['id'];
                    $result['child'] = $this->child($result['id']);
                    $result['image'] = File::pathToUrl((string) $result['image']);
                    $result['flag'] = Base64::flag($result['id'], 7);
                    if (in_array($result['action_name'], ['article', 'picture', 'download'])) {
                        $result['url'] = url('list/' . Base64::url62encode($result['id']));
                    } else {
                        $result['url'] = url($result['action_name'] . '/' . Base64::url62encode($result['id']));
                    }
                    if ($result['access_id']) {
                        $result['url'] = url('channel/' . Base64::url62encode($result['id']));
                    }
                    unset($result['action_name']);

                    $this->cache->tag('cms nav')->set($cache_key, $result);
                }
            }
        }

        return [
            'debug' => false,
            'cache' => 28800,
            'msg'   => 'sidebar',
            'data'  => isset($result) ? $result : []
        ];
    }

    /**
     * 获得子导航
     * @access private
     * @param  int    $_id      ID
     * @return array
     */
    private function child(int $_id): array
    {
        $result = ModelCategory::view('category', ['id', 'name', 'aliases', 'image', 'is_channel', 'access_id'])
            ->view('model', ['name' => 'action_name'], 'model.id=category.model_id')
            ->view('level', ['name' => 'level_name'], 'level.id=category.access_id', 'LEFT')
            ->where('category.is_show', '=', 1)
            ->where('category.pid', '=', $_id)
            ->order('category.sort_order ASC, category.id DESC')
            ->select()
            ->toArray();

        foreach ($result as $key => $value) {
            $value['id'] = (int) $value['id'];
            $value['child'] = $this->child($value['id']);
            $value['image'] = File::pathToUrl((string) $value['image']);
            $value['flag'] = Base64::flag($value['id'], 7);
            if (in_array($value['action_name'], ['article', 'picture', 'download'])) {
                $value['url'] = url('list/' . Base64::url62encode($value['id']));
            } else {
                $value['url'] = url($value['action_name'] . '/' . Base64::url62encode($value['id']));
            }
            if ($value['access_id']) {
                $value['url'] = url('channel/' . $value['action_name'] . '/' . Base64::url62encode($value['id']));
            }

            unset($value['action_name']);

            $result[$key] = $value;
        }

        return $result ? $result : [];
    }

    /**
     * 获得父级导航ID
     * @access private
     * @param  int    $_id ID
     * @return array
     */
    private function parent(int $_id)
    {
        $result = ModelCategory::where('id', '=', $_id)->value('pid', 0);

        return $result ? $this->parent((int) $result) : $_id;
    }
}
