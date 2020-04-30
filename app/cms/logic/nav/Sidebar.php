<?php

/**
 *
 * API接口层
 * 侧导航
 *
 * @package   NICMS
 * @category  app\cms\logic\nav
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace app\cms\logic\nav;

use app\common\controller\BaseLogic;
use app\common\library\Base64;
use app\common\library\Canvas;
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
        if ($cid = $this->request->param('cid/d', 0, 'abs')) {
            $cache_key = md5(__METHOD__ . $cid);
            if (!$this->cache->has($cache_key) || !$result = $this->cache->get($cache_key)) {
                $id = $this->parent((int) $cid);
                $result = ModelCategory::view('category', ['id', 'name', 'aliases', 'image', 'is_channel', 'access_id'])
                    ->view('model', ['name' => 'action_name'], 'model.id=category.model_id')
                    ->view('level', ['name' => 'level_name'], 'level.id=category.access_id', 'LEFT')
                    ->where([
                        ['category.is_show', '=', 1],
                        ['category.id', '=', $id],
                    ])
                    ->find();

                if (null !== $result && $result = $result->toArray()) {
                    $result['id'] = (int) $result['id'];
                    $result['child'] = $this->child($result['id']);
                    $result['image'] = Canvas::image((string) $result['image']);
                    $result['flag'] = Base64::flag($result['id'], 7);
                    if (in_array($result['action_name'], ['article', 'picture', 'download'])) {
                        $result['url'] = url('list/' . $result['id']);
                    } else {
                        $result['url'] = url($result['action_name'] . '/' . $result['id']);
                    }
                    if ($result['access_id']) {
                        $result['url'] = url('channel/' . $result['id']);
                    }
                    unset($result['action_name']);
                }

                $this->cache->tag(['cms', 'cms nav'])->set($cache_key, $result);
            }
        }

        return [
            'debug' => false,
            'cache' => 28800,
            'msg'   => 'sidebar',
            'data'  => $result ? $result : []
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
            ->where([
                ['category.is_show', '=', 1],
                ['category.pid', '=', $_id],
            ])
            ->order('category.sort_order ASC, category.id DESC')
            ->select()
            ->toArray();

        foreach ($result as $key => $value) {
            $value['id'] = (int) $value['id'];
            $value['child'] = $this->child($value['id']);
            $value['image'] = Canvas::image((string) $value['image']);
            $value['flag'] = Base64::flag($value['id'], 7);
            if (in_array($value['action_name'], ['article', 'picture', 'download'])) {
                $value['url'] = url('list/' . $value['id']);
            } else {
                $value['url'] = url($value['action_name'] . '/' . $value['id']);
            }
            if ($value['access_id']) {
                $value['url'] = url('channel/' . $value['action_name'] . '/' . $value['id']);
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
        $result = ModelCategory::where([
            ['id', '=', $_id],
        ])->value('pid', 0);

        return $result ? $this->parent((int) $result) : $_id;
    }
}
