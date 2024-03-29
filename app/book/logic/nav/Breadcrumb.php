<?php

/**
 *
 * API接口层
 * 面包屑
 *
 * @package   NICMS
 * @category  app\book\logic\nav
 * @author    失眠小枕头 [312630173@qq.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace app\book\logic\nav;

use app\common\controller\BaseLogic;
use app\common\library\File;
use app\common\library\Base64;
use app\common\model\BookType as ModelBookType;

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
        if ($cid = $this->request->param('book_type_id', 0, '\app\common\library\Base64::url62decode')) {
            $cache_key = $this->getCacheKey('book nav breadcrumb');
            if (!$this->cache->has($cache_key)) {
                $this->parentCate((int) $cid);
                $this->cache->tag(['book', 'book nav'])->set($cache_key, $this->bread);
            } else {
                $this->bread = $this->cache->get($cache_key);
            }
        }

        return [
            'debug' => false,
            'cache' => 28800,
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
        $result = ModelBookType::where('is_show', '=', 1)
            ->where('id', '=', $_pid)
            ->find();

        if (null !== $result && $result = $result->toArray()) {
            $result['id'] = (int) $result['id'];
            $result['image'] = File::imgUrl((string) $result['image']);
            $result['flag'] = Base64::flag($result['id'], 7);
            $result['url'] = url('list/' . Base64::url62encode($result['id']));

            if ($result['pid']) {
                $this->parentCate((int) $result['pid']);
                if (isset($this->bread[$result['pid']])) {
                    $this->bread[$result['pid']]['child'] = $result['id'];
                }
            }
            $this->bread[$result['id']] = $result;
        }
    }
}
