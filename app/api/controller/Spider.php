<?php

/**
 *
 * API接口层
 * 爬虫
 *
 * @package   NICMS
 * @category  app\api\controller
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2020
 */

declare(strict_types=1);

namespace app\cms\logic;

use app\common\library\Spider as LibSpider;

class Spider
{
    private $base_uri = 'https://www.jx.la/';

    public function getUrl()
    {
        $result = new LibSpider($this->base_uri);
        $result->fetch();
    }
}
