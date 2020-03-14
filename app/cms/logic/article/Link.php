<?php

/**
 *
 * API接口层
 * 友情链接
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

use app\common\controller\BaseLogic;
use app\common\library\Base64;
use app\common\library\Canvas;
use app\common\library\DataFilter;

class Link extends BaseLogic
{

    /**
     * 查询内容
     * @access public
     * @return array
     */
    public function query(): array
    {


        return [
            'debug' => false,
            'cache' => $result ? 28800     : false,
            'msg'   => $result ? 'details': 'error',
            'data'  => $result ?          : []
        ];
    }
}
