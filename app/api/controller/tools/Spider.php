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

use app\common\library\api\Async;
use app\common\library\Filter;
use app\common\library\Spider as LibSpider;

class Spider extends Async
{

    public function index()
    {
        if ($this->validate->referer() && $uri = $this->request->param('uri', false)) {
            @set_time_limit(60);
            @ini_set('max_execution_time', '60');

            $method = $this->request->param('method', 'GET');
            $selector = $this->request->param('selector', '');
            $extract = $this->request->param('extract', '');

            $uri = Filter::decode($uri);
            $uri = str_replace('&nbsp;', '', $uri);

            try {
                $spider = new LibSpider;
                if ($spider->request($method, $uri)) {
                    // 有选择器时
                    if ($selector) {
                        // 扩展属性
                        $result = $spider->fetch($selector, $extract ? explode(',', $extract) : []);
                    } else {
                        $result = $spider->html();
                    }
                }
            } catch (\Exception $e) {
                trace($uri, 'error');
                trace($e->getFile() . $e->getLine() . $e->getMessage(), 'error');
            }

            return !empty($result)
                ? $this->cache(1440)->success('spider success', $result)
                : $this->cache(false)->success('spider error', $uri);
        }

        return miss(404, false);
    }
}
