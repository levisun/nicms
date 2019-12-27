<?php

/**
 *
 * API接口层
 * 文章列表
 *
 * @package   NICMS
 * @category  app\cms\logic\search
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace app\cms\logic\search;

use app\cms\logic\ArticleBase;

class Article extends ArticleBase
{

    /**
     * 查询列表
     * @access public
     * @param
     * @return array
     */
    public function lists(): array
    {
        $result = $this->ArticleList();

        return [
            'debug' => false,
            'cache' => $result ? true : false,
            'msg'   => $result ? 'article search list data' : 'article search list error',
            'data'  => $result ? [
                'list'         => $result['data'],
                'total'        => $result['total'],
                'per_page'     => $result['per_page'],
                'current_page' => $result['current_page'],
                'last_page'    => $result['last_page'],
                'page'         => $result['render'],
            ] : []
        ];
    }
}
