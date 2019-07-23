<?php

/**
 *
 * API接口层
 * 侧导航
 *
 * @package   NICMS
 * @category  app\service\cms\nav
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace app\service\cms\nav;

use app\library\Base64;
use app\service\BaseService;
use app\model\Category as ModelCategory;

class Sidebar extends BaseService
{

    /**
     * 侧导航
     * @access public
     * @param
     * @return array
     */
    public function query(): array
    {
        $cid = (int) $this->request->param('cid/f');
        if ($cid) {
            $id = $this->parent($cid);
            $cache_key = md5(__METHOD__ . $id);
            if (!$this->cache->has($cache_key) || !$result = $this->cache->get($cache_key)) {
                $result = (new ModelCategory)
                    ->view('category', ['id', 'name', 'aliases', 'image', 'is_channel', 'access_id'])
                    ->view('model', ['name' => 'action_name'], 'model.id=category.model_id')
                    ->view('level', ['name' => 'level_name'], 'level.id=category.access_id', 'LEFT')
                    ->where([
                        ['category.is_show', '=', 1],
                        ['category.id', '=', $id],
                        ['category.lang', '=', $this->lang->getLangSet()]
                    ])
                    ->find()
                    ->toArray();

                $result['image'] = get_img_url($result['image']);
                $result['flag'] = Base64::flag($result['id'], 7);
                $result['url'] = url('list/' . $result['action_name'] . '/' . $result['id']);
                if ($result['access_id']) {
                    $result['url'] = url('channel/' . $result['action_name'] . '/' . $result['id']);
                }
                unset($result['action_name']);

                $result['child'] = $this->child($result['id']);

                $this->cache->tag('CMS NAV')->set($cache_key, $result);
            }
        }

        return [
            'debug' => false,
            'cache' => true,
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
    private function child(int $_id)
    {
        $result = (new ModelCategory)
            ->view('category', ['id', 'name', 'aliases', 'image', 'is_channel', 'access_id'])
            ->view('model', ['name' => 'action_name'], 'model.id=category.model_id')
            ->view('level', ['name' => 'level_name'], 'level.id=category.access_id', 'LEFT')
            ->where([
                ['category.is_show', '=', 1],
                ['category.pid', '=', $_id],
                ['category.lang', '=', $this->lang->getLangSet()]
            ])
            ->order('category.sort_order ASC, category.id DESC')
            ->select()
            ->toArray();

        foreach ($result as $key => $value) {
            $value['image'] = get_img_url($value['image']);
            $value['flag'] = Base64::flag($value['id'], 7);
            $value['url'] = url('list/' . $value['action_name'] . '/' . $value['id']);
            if ($value['access_id']) {
                $value['url'] = url('channel/' . $value['action_name'] . '/' . $value['id']);
            }
            $value['child'] = $this->child($value['id']);

            unset($value['action_name']);

            $result[$key] = $value;
        }

        return $result ? $result : false;
    }

    /**
     * 获得父级导航ID
     * @access private
     * @param  int    $_id      ID
     * @return array
     */
    private function parent(int $_id)
    {
        $result = (new ModelCategory)
            ->where([
                ['id', '=', $_id],
            ])
            ->value('pid');

        return $result ? $this->parent($result) : $_id;
    }
}
