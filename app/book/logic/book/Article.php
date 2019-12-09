<?php

/**
 *
 * API接口层
 * 文章列表
 *
 * @package   NICMS
 * @category  app\book\logic\article
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace app\book\logic\book;

use app\common\controller\BaseLogic;
use app\common\model\BookArticle as ModelBookArticle;
use app\common\library\Base64;
use app\common\library\DataFilter;
use gather\Book as GatherBook;

class Article extends BaseLogic
{

    /**
     * 查询列表
     * @access public
     * @return array
     */
    public function query(): array
    {
        $result = false;

        $cid = $this->request->param('cid/d');
        $sort_order = $this->request->param('id/d');
        $title = $this->request->param('t');
        $title = Base64::decrypt($title, date('Ymd'));
        $title = DataFilter::filter($title);

        if ($cid && $sort_order && $title) {
            $cache_key = md5(__METHOD__ . date('Ymd') . $cid . $sort_order . $title);
            if (!$this->cache->has($cache_key) || !$result = $this->cache->get($cache_key)) {
                $result = (new ModelBookArticle)
                    ->field('id, title, content')
                    ->where([
                        ['book_id', '=', $cid],
                        ['title', '=', $title]
                    ])
                    ->find();

                if ($result) {
                    $result = $result->toArray();
                    $result['content'] = DataFilter::deContent($result['content']);
                } else {
                    $uri = $this->request->param('u');
                    $uri = Base64::decrypt($uri, date('Ymd'));

                    if ($content = (new GatherBook)->getContent($uri)) {
                        $ModelBookArticle = new ModelBookArticle;
                        $ModelBookArticle
                            ->data([
                                'book_id'    => $cid,
                                'is_pass'    => 1,
                                'title'      => $title,
                                'content'    => DataFilter::content($content),
                                'sort_order' => $sort_order,
                                'show_time'  => time(),
                            ])
                            ->save();

                        $result = [
                            'id'      => $ModelBookArticle->id,
                            'title'   => $title,
                            'content' => $content
                        ];
                    }
                }

                $this->cache->tag('book')->set($cache_key, $result);
            }

            $cache_key = md5('app\book\logic\book\Catalog::query' . date('Ymd') . $cid);
            $catalog = $this->cache->get($cache_key);
            $catalog = $catalog['list'];

            $result['next'] = !empty($catalog[$sort_order]) ? $catalog[$sort_order] : false;
            $result['prev'] = !empty($catalog[$sort_order - 2]) ? $catalog[$sort_order - 2] : false;
        }

        return [
            'debug' => false,
            'cache' => false,
            'msg'   => 'article',
            'data'  => $result
        ];
    }
}
