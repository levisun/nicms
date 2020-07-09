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
        // $this->validate->referer() &&
        if ($uri = $this->request->param('uri', false)) {
            @set_time_limit(60);
            @ini_set('max_execution_time', '60');
            usleep(rand(5500000, 10000000));

            $method = $this->request->param('method', 'GET');
            $selector = $this->request->param('selector', '');
            $extract = $this->request->param('extract', '');

            $cache_key = md5($uri . $method . $selector . $extract);

            if (!$this->cache->has($cache_key) || !$result = $this->cache->get($cache_key)) {
                try {
                    $spider = new LibSpider;
                    $uri = str_replace('&nbsp;', '', $uri);
                    if ($spider->request($method, $uri)) {
                        // 有选择器时
                        if ($selector) {
                            // 扩展属性
                            $extract = $extract ? explode(',', $extract) : [];

                            $result = $spider->fetch($selector, $extract);
                        } else {
                            $result = $spider->html();
                        }

                        $this->cache->set($cache_key, $result);
                    }
                } catch (\Exception $e) {
                    // halt($e->getFile() . $e->getLine() . $e->getMessage());
                    //throw $th;
                }
            }

            return !empty($result)
                ? $this->cache(28800)->success('spider success', $result)
                : $this->cache(false)->success('spider error');
        }

        return miss(404, false);
    }
}
