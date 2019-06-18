<?php
/**
 *
 * API接口层
 * 文章列表
 *
 * @package   NICMS
 * @category  app\service\cms\article
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */
declare (strict_types = 1);

namespace app\service\cms\article;

use app\service\cms\ArticleBase;

class Lists extends ArticleBase
{

    /**
     * 查询列表
     * @access public
     * @param
     * @return array
     */
    public function query(): array
    {
        $result = $this->lists();

        return [
            'debug' => false,
            'cache' => $result ? true : false,
            'msg'   => $result ? 'article list data' : 'article list error',
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
