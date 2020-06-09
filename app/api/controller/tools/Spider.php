<?php

/**
 *
 * API接口层
 * 爬虫
 *
 * @package   NICMS
 * @category  app\api\controller\tools
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2020
 */

declare(strict_types=1);

namespace app\api\controller\tools;

use app\common\library\api\Async;
use app\common\library\Spider as LibSpider;

class Spider extends Async
{

    public function index()
    {
        if ($uri = $this->request->param('uri', false)) {
            $method = $this->request->param('method', 'GET');
            $selector = $this->request->param('selector', '');
            $extract = $this->request->param('extract', '');
            $extract = $extract ? explode(',', $extract) : [];

            usleep(rand(1500000, 2500000));
            $spider = new LibSpider($method, $uri);
            $result = $selector
                ? $spider->fetch($selector, $extract)
                : $spider->getContent();

            return $this->cache(true)->success('spider success', $result);
        }
    }
}
