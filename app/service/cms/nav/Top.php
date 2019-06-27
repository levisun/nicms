<?php
/**
 *
 * API接口层
 * 顶导航
 *
 * @package   NICMS
 * @category  app\service\cms\nav
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */
declare (strict_types = 1);

namespace app\service\cms\nav;

use app\library\Base64;
use app\service\BaseService;
use app\model\Category as ModelCategory;

class Top extends BaseService
{

    /**
     * 顶导航
     * @access public
     * @param
     * @return array
     */
    public function query(): array
    {
        $cache_key = md5(__METHOD__ . $this->lang->getLangSet());
        if (!$this->cache->has($cache_key) || !$result = $this->cache->get($cache_key)) {
            $result = (new ModelCategory)->view('category c', ['id', 'name', 'aliases', 'image', 'is_channel', 'access_id'])
                ->view('model m', ['name' => 'action_name'], 'm.id=c.model_id')
                ->view('level level', ['name' => 'level_name'], 'level.id=c.access_id', 'LEFT')
                ->where([
                    ['c.is_show', '=', 1],
                    ['c.type_id', '=', 1],
                    ['c.pid', '=', 0],
                    ['c.lang', '=', $this->lang->getLangSet()]
                ])
                ->order('c.sort_order ASC, c.id DESC')
                ->select()
                ->toArray();

            foreach ($result as $key => $value) {
                $value['image'] = get_img_url($value['image']);
                $value['flag'] = Base64::flag($value['id'], 7);
                $value['url'] = url('list/' . $value['action_name'] . '/' . $value['id']);
                if ($value['access_id']) {
                    $value['url'] = url('channel/' . $value['action_name'] . '/' . $value['id']);
                }
                $value['child'] = $this->child($value['id'], 1);
                unset($value['action_name']);

                $result[$key] = $value;
            }
            $this->cache->tag('cms_nav')->set($cache_key, $result);
        }

        return [
            'debug' => false,
            'cache' => true,
            'msg'   => 'nav top data',
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
        $result = (new ModelCategory)->view('category c', ['id', 'name', 'aliases', 'image', 'is_channel', 'access_id'])
            ->view('model m', ['name' => 'action_name'], 'm.id=c.model_id')
            ->view('level level', ['name' => 'level_name'], 'level.id=c.access_id', 'LEFT')
            ->where([
                ['c.is_show', '=', 1],
                ['c.type_id', '=', $_type_id],
                ['c.pid', '=', $_pid],
                ['c.lang', '=', $this->lang->getLangSet()]
            ])
            ->order('c.sort_order ASC, c.id DESC')
            ->select()
            ->toArray();

        foreach ($result as $key => $value) {
            $value['image'] = get_img_url($value['image']);
            $value['flag'] = Base64::flag($value['id'], 7);
            $value['url'] = url('list/' . $value['action_name'] . '/' . $value['id']);
            if ($value['access_id']) {
                $value['url'] = url('channel/' . $value['action_name'] . '/' . $value['id']);
            }
            $value['child'] = $this->child($value['id'], 1);
            unset($value['action_name']);

            $result[$key] = $value;
        }

        return $result ? $result : [];
    }
}
