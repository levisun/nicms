<?php

/**
 *
 * API接口层
 * 其它导航
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
use app\common\library\Base64;
use app\common\library\Image;
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
        $cache_key = 'nav other' . $this->lang->getLangSet();
        if (!$this->cache->has($cache_key) || !$result = $this->cache->get($cache_key)) {
            $result = ModelCategory::view('category c', ['id', 'name', 'aliases', 'image', 'is_channel', 'access_id'])
                ->view('model', ['name' => 'action_name'], 'model.id=category.model_id')
                ->view('level', ['name' => 'level_name'], 'level.id=category.access_id', 'LEFT')
                ->where([
                    ['category.is_show', '=', 1],
                    ['category.type_id', '=', 4],
                    ['category.pid', '=', 0],
                    ['category.lang', '=', $this->lang->getLangSet()]
                ])
                ->order('category.sort_order ASC, category.id DESC')
                ->select();

            if ($result && $result = $result->toArray()) {
                foreach ($result as $key => $value) {
                    $value['id'] = (int) $value['id'];
                    $value['child'] = $this->child($value['id']);
                    $value['image'] = Image::path((string) $value['image']);
                    $value['flag'] = Base64::flag($value['id'], 7);
                    if (in_array($value['action_name'], ['article', 'picture', 'download'])) {
                        $value['url'] = url('list/' . Base64::url62encode($value['id']));
                    } else {
                        $value['url'] = url($value['action_name'] . '/' . Base64::url62encode($value['id']));
                    }
                    if ($value['access_id']) {
                        $value['url'] = url('channel/' . Base64::url62encode($value['id']));
                    }
                    unset($value['action_name']);

                    $result[$key] = $value;
                }
                $this->cache->tag('cms nav')->set($cache_key, $result);
            }
        }

        return [
            'debug' => false,
            'cache' => 28800,
            'msg'   => 'nav other data',
            'data'  => $result
        ];
    }

    /**
     * 获得子导航
     * @access private
     * @param  int    $_pid     父ID
     * @return array
     */
    private function child(int $_pid): array
    {
        $result = ModelCategory::view('category', ['id', 'name', 'aliases', 'image', 'is_channel', 'access_id'])
            ->view('model', ['name' => 'action_name'], 'model.id=category.model_id')
            ->view('level', ['name' => 'level_name'], 'level.id=category.access_id', 'LEFT')
            ->where([
                ['category.is_show', '=', 1],
                ['category.type_id', '=', 4],
                ['category.pid', '=', $_pid],
            ])
            ->order('category.sort_order ASC, category.id DESC')
            ->select()
            ->toArray();

        foreach ($result as $key => $value) {
            $value['id'] = (int) $value['id'];
            $value['child'] = $this->child($value['id']);
            $value['image'] = Image::path((string) $value['image']);
            $value['flag'] = Base64::flag($value['id'], 7);
            if (in_array($value['action_name'], ['article', 'picture', 'download'])) {
                $value['url'] = url('list/' . Base64::url62encode($value['id']));
            } else {
                $value['url'] = url($value['action_name'] . '/' . Base64::url62encode($value['id']));
            }
            if ($value['access_id']) {
                $value['url'] = url('channel/' . Base64::url62encode($value['id']));
            }
            unset($value['action_name']);


            $result[$key] = $value;
        }

        return $result ? $result : [];
    }
}
