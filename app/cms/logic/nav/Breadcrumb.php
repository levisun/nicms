<?php

/**
 *
 * API接口层
 * 面包屑
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

class Breadcrumb extends BaseLogic
{
    private $bread = [];

    /**
     * 面包屑
     * @access public
     * @param
     * @return array
     */
    public function query(): array
    {
        if ($cid = $this->request->param('cid/d')) {
            $cache_key = md5(__METHOD__ . $cid);
            if (!$this->cache->has($cache_key)) {
                $this->parentCate((int) $cid);
                $this->cache->tag('cms nav')->set($cache_key, $this->bread);
            } else {
                $this->bread = $this->cache->get($cache_key);
            }
        }

        return [
            'debug' => false,
            'cache' => true,
            'msg'   => 'nav breadcrumb data',
            'data'  => $this->bread
        ];
    }

    /**
     * 获得父导航
     * @access private
     * @param  int     $_pid 父ID
     * @param
     * @return array
     */
    private function parentCate(int $_pid)
    {
        $result = (new ModelCategory)->view('category', ['id', 'name', 'pid', 'aliases', 'image', 'is_channel', 'access_id'])
            ->view('model', ['name' => 'action_name'], 'model.id=category.model_id')
            ->view('level', ['name' => 'level_name'], 'level.id=category.access_id', 'LEFT')
            ->where([
                ['category.is_show', '=', 1],
                ['category.id', '=', $_pid],
                ['category.lang', '=', $this->lang->getLangSet()]
            ])
            ->find();

        if (null !== $result && $result = $result->toArray()) {
            $result['id'] = (int) $result['id'];
            $result['image'] = (new Canvas)->image($result['image']);
            $result['flag'] = Base64::flag($result['id'], 7);
            $result['url'] = url('list/' . $result['id']);
            if ($result['access_id']) {
                $result['url'] = url('channel/' . $result['id']);
            }
            unset($result['action_name']);

            if ($result['pid']) {
                $this->parentCate((int) $result['pid']);
                $this->bread[$result['pid']]['child'] = $result['id'];
            }
            $this->bread[$result['id']] = $result;
        }
    }
}
