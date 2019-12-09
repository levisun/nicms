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
            $cache_key = md5(__METHOD__ . date('Ymd') . $id);
            if (!$this->cache->has($cache_key) || !$result = $this->cache->get($cache_key)) {
                $result = (new ModelBook)
                    ->view('book', ['id', 'title', 'keywords', 'description', 'type_id', 'author_id', 'origin', 'hits', 'update_time'])
                    ->where([
                        ['book.id', '=', $id]
                    ])
                    ->find();
                if ($result) {
                    $result = $result->toArray();

                    $origin = (new GatherBook)->getItems(parse_url($result['origin'], PHP_URL_PATH));
                    unset($result['origin']);

                    foreach ($origin as $key => $value) {
                        $value['index'] = $key + 1;
                        $value['url']  = url('article/' . $result['id'] . '/' . $value['index']);
                        $value['url'] .= '?t=' . urlencode(Base64::encrypt($value['title'], date('Ymd'))) .
                            '&u=' . urlencode(Base64::encrypt($value['uri'], date('Ymd')));
                        unset($value['uri']);

                        $origin[$key] = $value;
                    }
                    $result['list'] = $origin;

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
