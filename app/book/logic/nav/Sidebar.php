<?php

/**
 *
 * API接口层
 * 侧导航
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
use app\common\library\Base64;
use app\common\library\Image;
use app\common\model\BookType as ModelBookType;

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
        if ($cid = $this->request->param('tid', 0, '\app\common\library\Base64::url62decode')) {
            $cache_key = 'book nav sidebar' . $cid;
            if (!$this->cache->has($cache_key) || !$result = $this->cache->get($cache_key)) {
                $id = $this->parent((int) $cid);
                $result = ModelBookType::where([
                        ['is_show', '=', 1],
                        ['id', '=', $id],
                    ])
                    ->find();

                if (null !== $result && $result = $result->toArray()) {
                    $result['id'] = (int) $result['id'];
                    $result['child'] = $this->child($result['id']);
                    $result['image'] = Image::path((string) $result['image']);
                    $result['flag'] = Base64::flag($result['id'], 7);
                    $result['url'] = url('list/' . Base64::url62encode($result['id']));
                }

                $this->cache->tag(['book', 'book nav'])->set($cache_key, $result);
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
     * @param  int    $_id ID
     * @return array
     */
    private function child(int $_id): array
    {
        $result = ModelBookType::where([
                ['is_show', '=', 1],
                ['pid', '=', $_id],
            ])
            ->order('sort_order ASC, id DESC')
            ->select()
            ->toArray();

        foreach ($result as $key => $value) {
            $value['id'] = (int) $value['id'];
            $value['child'] = $this->child($value['id']);
            $value['image'] = Image::path((string) $value['image']);
            $value['flag'] = Base64::flag($value['id'], 7);
            $value['url'] = url('list/' . Base64::url62encode($value['id']));

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
        $result = ModelBookType::where([
            ['id', '=', $_id],
        ])->value('pid', 0);

        return $result ? $this->parent((int) $result) : $_id;
    }
}
