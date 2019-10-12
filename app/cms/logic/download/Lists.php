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

use think\facade\Lang;
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
        $list = $this->ArticleList();

        return [
            'debug' => false,
            'cache' => false,
            'msg'   => Lang::get('success'),
            'data'  => [
                'list'         => $list['data'],
                'total'        => $list['total'],
                'per_page'     => $list['per_page'],
                'current_page' => $list['current_page'],
                'last_page'    => $list['last_page'],
                'page'         => $list['render'],
            ]
        ];
    }
}
