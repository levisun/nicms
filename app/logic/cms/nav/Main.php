<?php
/**
 *
 * API接口层
 * 主导航
 *
 * @package   NICMS
 * @category  app\logic\cms\nav
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */
declare (strict_types = 1);

namespace app\logic\cms\nav;

use think\facade\Lang;
use app\library\Base64;
use app\model\Category as ModelCategory;

class Main
{

    /**
     * 主导航
     * @access public
     * @param
     * @return array
     */
    public function query(): array
    {
        $result = (new ModelCategory)->view('category', ['id', 'name', 'aliases', 'image', 'is_channel', 'access_id'])
            ->view('model', ['name' => 'action_name'], 'model.id=category.model_id')
            ->view('level', ['name' => 'level_name'], 'level.id=category.access_id', 'LEFT')
            ->where([
                ['category.is_show', '=', 1],
                ['category.type_id', '=', 2],
                ['category.pid', '=', 0],
                ['category.lang', '=', Lang::getLangSet()]
            ])
            ->order('category.sort_order ASC, category.id DESC')
            ->cache(__METHOD__, null, 'NAV')
            ->select()
            ->toArray();

        foreach ($result as $key => $value) {
            $value['image'] = get_img_url($value['image']);
            $value['flag'] = Base64::flag($value['id'], 7);
            $value['url'] = url('list/' . $value['action_name'] . '/' . $value['id']);
            if ($value['access_id']) {
                $value['url'] = url('channel/' . $value['action_name'] . '/' . $value['id']);
            }
            $value['child'] = $this->child($value['id'], 2);
            unset($value['action_name']);

            $result[$key] = $value;
        }

        return [
            'debug' => false,
            'cache' => true,
            'msg'   => Lang::get('success'),
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
    private function child(int $_pid, int $_type_id)
    {
        $result = (new ModelCategory)->view('category', ['id', 'name', 'aliases', 'image', 'is_channel', 'access_id'])
            ->view('model', ['name' => 'action_name'], 'model.id=category.model_id')
            ->view('level', ['name' => 'level_name'], 'level.id=category.access_id', 'LEFT')
            ->where([
                ['category.is_show', '=', 1],
                ['category.type_id', '=', $_type_id],
                ['category.pid', '=', $_pid],
                ['category.lang', '=', Lang::getLangSet()]
            ])
            ->order('category.sort_order ASC, category.id DESC')
            ->cache(__METHOD__ . $_pid . $_type_id, null, 'NAV')
            ->select()
            ->toArray();

        foreach ($result as $key => $value) {
            $value['image'] = get_img_url($value['image']);
            $value['flag'] = Base64::flag($value['id'], 7);
            $value['url'] = url('list/' . $value['action_name'] . '/' . $value['id']);
            if ($value['access_id']) {
                $value['url'] = url('channel/' . $value['action_name'] . '/' . $value['id']);
            }
            $value['child'] = $this->child($value['id'], 2);
            unset($value['action_name']);

            $result[$key] = $value;
        }

        return $result ? $result : false;
    }
}
