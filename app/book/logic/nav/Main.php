<?php

/**
 *
 * API接口层
 * 主导航
 *
 * @package   NICMS
 * @category  app\book\logic\nav
 * @author    失眠小枕头 [levisun.mail@gmail.com]
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

class Main extends BaseLogic
{

    /**
     * 主导航
     * @access public
     * @param
     * @return array
     */
    public function query(): array
    {
        $cache_key = md5('book nav main' . $this->lang->getLangSet());
        if (!$this->cache->has($cache_key) || !$result = $this->cache->get($cache_key)) {
            $result = ModelBookType::where([
                    ['is_show', '=', 1],
                    ['pid', '=', 0],
                    ['lang', '=', $this->lang->getLangSet()]
                ])
                ->order('sort_order ASC, id DESC')
                ->select()
                ->toArray();

            foreach ($result as $key => $value) {
                $value['id'] = (int) $value['id'];
                $value['child'] = $this->child($value['id']);
                $value['image'] = Image::path((string) $value['image']);
                $value['flag'] = Base64::flag($value['id'], 7);
                $value['url'] = url('list/' . $value['id']);

                $result[$key] = $value;
            }
            $this->cache->tag(['book', 'book nav'])->set($cache_key, $result);
        }

        return [
            'debug' => false,
            'cache' => 28800,
            'msg'   => 'nav main data',
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
        $result = ModelBookType::where([
                ['is_show', '=', 1],
                ['pid', '=', $_pid],
            ])
            ->order('sort_order ASC, id DESC')
            ->select()
            ->toArray();

        foreach ($result as $key => $value) {
            $value['id'] = (int) $value['id'];
            $value['child'] = $this->child($value['id']);
            $value['image'] = Image::path((string) $value['image']);
            $value['flag'] = Base64::flag($value['id'], 7);
            $value['url'] = url('list/' . $value['id']);

            $result[$key] = $value;
        }

        return $result ? $result : [];
    }
}
