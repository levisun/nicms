<?php

/**
 *
 * API接口层
 * 下载列表
 *
 * @package   NICMS
 * @category  app\cms\logic\download
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace app\cms\logic\download;

use app\cms\logic\ArticleBase;

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
        $result = $this->ArticleList();

        return [
            'debug' => false,
            'cache' => $result ? true : false,
            'msg'   => $result ? 'download list data' : 'download list error',
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
