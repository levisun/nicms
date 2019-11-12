<?php

/**
 *
 * API接口层
 * 其它导航
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

class Other extends BaseLogic
{

    /**
     * 其它导航
     * @access public
     * @param
     * @return array
     */
    public function query(): array
    {
        $cache_key = md5(__METHOD__ . $this->lang->getLangSet());
        if (!$this->cache->has($cache_key) || !$result = $this->cache->get($cache_key)) {
            $result = (new ModelCategory)->view('category c', ['id', 'name', 'aliases', 'image', 'is_channel', 'access_id'])
                ->view('model', ['name' => 'action_name'], 'model.id=category.model_id')
                ->view('level', ['name' => 'level_name'], 'level.id=category.access_id', 'LEFT')
                ->where([
                    ['category.is_show', '=', 1],
                    ['category.type_id', '=', 4],
                    ['category.pid', '=', 0],
                    ['category.lang', '=', $this->lang->getLangSet()]
                ])
                ->order('category.sort_order ASC, category.id DESC')
                ->select()
                ->toArray();

            foreach ($result as $key => $value) {
                $value['id'] = (int) $value['id'];
                $value['child'] = $this->child($value['id'], 4);
                $value['image'] = (new Canvas)->image($value['image']);
                $value['flag'] = Base64::flag($value['id'], 7);
                $value['url'] = url('list/' . $value['action_name'] . '/' . $value['id']);
                if ($value['access_id']) {
                    $value['url'] = url('channel/' . $value['action_name'] . '/' . $value['id']);
                }
                unset($value['action_name']);

                $result[$key] = $value;
            }
            $this->cache->tag('cms')->set($cache_key, $result);
        }

        return [
            'debug' => false,
            'cache' => true,
            'msg'   => 'nav other data',
            'data'  => $result
        ];
    }

    /**
     * 获得子导航
     * @access private
     * @param  int    $_pid     父ID
     * @param  int    $_type_id 类型
     * @return array
     */
    private function child(int $_pid, int $_type_id): array
    {
        $result = (new ModelCategory)->view('category', ['id', 'name', 'aliases', 'image', 'is_channel', 'access_id'])
            ->view('model', ['name' => 'action_name'], 'model.id=category.model_id')
            ->view('level', ['name' => 'level_name'], 'level.id=category.access_id', 'LEFT')
            ->where([
                ['category.is_show', '=', 1],
                ['category.type_id', '=', $_type_id],
                ['category.pid', '=', $_pid],
                ['category.lang', '=', $this->lang->getLangSet()]
            ])
            ->order('category.sort_order ASC, category.id DESC')
            ->select()
            ->toArray();

        foreach ($result as $key => $value) {
            $value['id'] = (int) $value['id'];
            $value['child'] = $this->child($value['id'], 4);
            $value['image'] = (new Canvas)->image($value['image']);
            $value['flag'] = Base64::flag($value['id'], 7);
            $value['url'] = url('list/' . $value['action_name'] . '/' . $value['id']);
            if ($value['access_id']) {
                $value['url'] = url('channel/' . $value['action_name'] . '/' . $value['id']);
            }
            unset($value['action_name']);


            $result[$key] = $value;
        }

        return $result ? $result : [];
    }
}
