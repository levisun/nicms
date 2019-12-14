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
use app\common\model\Book as ModelBook;
use app\common\model\BookArticle as ModelBookArticle;
use app\common\library\Base64;
use gather\Book as GatherBook;

class Catalog extends BaseLogic
{

    /**
     * 查询列表
     * @access public
     * @return array
     */
    public function query(): array
    {
        $result = false;
        if ($id = $this->request->param('id/d')) {
            $cache_key = md5(__METHOD__ . $id);
            if (!$this->cache->has($cache_key) || !$result = $this->cache->get($cache_key)) {
                $result = (new ModelBook)
                    ->view('book', ['id', 'title', 'keywords', 'description', 'type_id', 'author_id', 'origin', 'hits', 'update_time'])
                    ->where([
                        ['book.id', '=', $id]
                    ])
                    ->find();
                if ($result) {
                    $result = $result->toArray();

                    $list = (new ModelBookArticle)
                        ->field('id, title')
                        ->where([
                            ['book_id', '=', $result['id']]
                        ])
                        ->order('sort_order ASC')
                        ->select();
                    if ($list && $list = $list->toArray()) {
                        foreach ($list as $key => $value) {
                            $value['sort_order'] = $key + 1;
                            $value['url']  = url('article/' . $result['id'] . '/' . $value['id']) .
                                '?o=' . urlencode(Base64::encrypt((string) $value['sort_order'], date('Ymd')));
                            unset($value['sort_order']);
                            $list[$key] = $value;
                        }
                    }



                    $origin = (new GatherBook)->getItems(parse_url($result['origin'], PHP_URL_PATH));
                    unset($result['origin']);
                    if (count($list) <= count($origin)) {
                        foreach ($origin as $key => $value) {
                            if (empty($list[$key])) {
                                $value['id'] = $key + 1;
                                $value['url']  = url('article/' . $result['id'] . '/' . $value['id']) .
                                    '?o=' . urlencode(Base64::encrypt((string) $value['id'], date('Ymd'))) .
                                    '&t=' . urlencode(Base64::encrypt($value['title'], date('Ymd'))) .
                                    '&u=' . urlencode(Base64::encrypt($value['uri'], date('Ymd')));
                                unset($value['uri']);

                                $list[$key] = $value;
                            }
                        }
                    }
                    unset($origin);


                    $result['list'] = $list;

                    $this->cache->tag('book')->set($cache_key, $result);
                }
            }
        }

        return [
            'debug' => false,
            'cache' => true,
            'msg'   => $result ? 'itmes' : 'error',
            'data'  => $result ?: []
        ];
    }
}
