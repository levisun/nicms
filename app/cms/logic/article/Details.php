<?php

/**
 *
 * API接口层
 * 文章内容
 *
 * @package   NICMS
 * @category  app\cms\logic\article
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace app\cms\logic\article;

use app\cms\logic\article\ArticleBase;

class Details extends ArticleBase
{

    /**
     * 查询内容
     * @access public
     * @return array
     */
    public function query(): array
    {
        $result = $this->ArticleDetails();

        return [
            'debug'  => false,
            'cache'  => $result ? true : false,
            'msg'    => $result ? 'details' : 'error',
            'data'   => $result ?: []
        ];
    }
}
