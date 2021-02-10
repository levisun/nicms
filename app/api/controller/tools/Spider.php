<?php

/**
 *
 * API接口层
 * 爬虫
 *
 * @package   NICMS
 * @category  app\api\controller\tools
 * @author    失眠小枕头 [312630173@qq.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2020
 */

declare(strict_types=1);

namespace app\api\controller\tools;

use app\common\controller\BaseApi;
use app\common\library\tools\Spider as LibSpider;
use app\common\library\Filter;

class Spider extends BaseApi
{

    public function index()
    {
        if (!$this->validate->referer() || !$uri = $this->request->param('uri')) {
            return miss(404, false);
        }

        @set_time_limit(60);
        @ini_set('max_execution_time', '60');

        $method = $this->request->param('method', 'GET');
        $selector = $this->request->param('selector', '');
        $extract = $this->request->param('extract', '');
        $extract = $extract ? explode(',', $extract) : [];
        $type = $this->request->param('type', 'html');

        $uri = Filter::htmlDecode($uri);
        $uri = str_replace('&nbsp;', '', $uri);

        $spider = new LibSpider;
        $spider->request($method, $uri);
        $result = $selector
            ? $spider->select($selector, $extract)
            : ('html' == $type ? $spider->getHtml() : $spider->pageInfo());

        return !empty($result)
            ? $this->cache(1440)->success('spider success', $result)
            : $this->cache(false)->success('spider error', $uri);
    }
}
