<?php
/**
 *
 * API接口层
 * 侧导航
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
use think\facade\Request;
use app\library\Base64;
use app\model\Category as ModelCategory;

class Sidebar
{

    /**
     * 侧导航
     * @access public
     * @param
     * @return array
     */
    public function query(): array
    {
        if ($cid = Request::param('cid/f', null)) {
            $result =
            (new ModelCategory)->view('category', ['id', 'name', 'aliases', 'image', 'is_channel', 'access_id'])
            ->view('model', ['name' => 'action_name'], 'model.id=category.model_id')
            ->view('level', ['name' => 'level_name'], 'level.id=category.access_id', 'LEFT')
            ->where([
                ['category.is_show', '=', 1],
                ['category.id', '=', $cid],
                ['category.lang', '=', Lang::getLangSet()]
            ])
            ->cache(__METHOD__ . $cid, null, 'NAV')
            ->find()
            ->toArray();


            $result['image'] = getImgUrl($result['image']);
            $result['flag'] = Base64::flag($result['id'], 7);
            $result['child'] = $this->child($result['id']);
            if (empty($result['child'])) {
                unset($result['child']);
            }

            $result['url'] = url('list/' . $result['action_name'] . '/' . $result['id']);
            if ($result['access_id']) {
                $result['url'] = url('channel/' . $result['action_name'] . '/' . $result['id']);
            }
            unset($result['action_name']);

            return [
                'debug' => false,
                'msg'   => Lang::get('success'),
                'data'  => $result
            ];
        } else {
            return [
                'debug' => false,
                'msg'   => Lang::get('error')
            ];
        }
    }

    /**
     * 获得子导航
     * @access private
     * @param  int    $_id      ID
     * @param  int    $_type_id 类型
     * @return array
     */
    private function child(int $_id)
    {
        $result =
        (new ModelCategory)->view('category', ['id', 'name', 'aliases', 'image', 'is_channel', 'access_id'])
        ->view('model', ['name' => 'action_name'], 'model.id=category.model_id')
        ->view('level', ['name' => 'level_name'], 'level.id=category.access_id', 'LEFT')
        ->where([
            ['category.is_show', '=', 1],
            ['category.pid', '=', $_id],
            ['category.lang', '=', Lang::getLangSet()]
        ])
        ->order('category.sort_order ASC, category.id DESC')
        ->cache(__METHOD__ . $_id, null, 'NAV')
        ->select()
        ->toArray();

        foreach ($result as $key => $value) {
            $value['image'] = getImgUrl($value['image']);
            $value['flag'] = Base64::flag($value['id'], 7);
            $value['child'] = $this->child($value['id']);

            $value['url'] = url('list/' . $value['action_name'] . '/' . $value['id']);
            if ($value['access_id']) {
                $value['url'] = url('channel/' . $value['action_name'] . '/' . $value['id']);
            }
            unset($value['action_name']);

            $result[$key] = $value;
        }

        return $result ? : [];
    }
}
