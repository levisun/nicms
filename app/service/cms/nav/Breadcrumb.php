<?php
/**
 *
 * API接口层
 * 面包屑
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

class Breadcrumb extends BaseService
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
        if ($cid = (int)$this->request->param('cid/f')) {
            $cache_key = md5(__METHOD__ . $cid);
            if (!$this->cache->has($cache_key)) {
                $this->parentCate($cid);
                $this->cache->tag('CMS NAV')->set($cache_key, $this->bread,);
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
    private function parentCate($_pid)
    {
        $result = (new ModelCategory)->view('category', ['id', 'name', 'pid', 'aliases', 'image', 'is_channel', 'access_id'])
            ->view('model', ['name' => 'action_name'], 'model.id=category.model_id')
            ->view('level', ['name' => 'level_name'], 'level.id=category.access_id', 'LEFT')
            ->where([
                ['category.is_show', '=', 1],
                ['category.id', '=', $_pid],
                ['category.lang', '=', $this->lang->getLangSet()]
            ])
            ->find()
            ->toArray();

        if ($result) {
            $result['image'] = get_img_url($result['image']);
            $result['flag'] = Base64::flag($result['id'], 7);

            $result['url'] = url('list/' . $result['action_name'] . '/' . $result['id']);
            if ($result['access_id']) {
                $result['url'] = url('channel/' . $result['action_name'] . '/' . $result['id']);
            }
            unset($result['action_name']);

            $this->bread[] = $result;
            if ($result['pid']) {
                $this->parentCate($result['pid']);
            }
        }
    }
}
