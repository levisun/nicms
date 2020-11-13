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
use app\common\library\tools\Spider as LibSpider;
use app\common\library\Filter;

class Spider extends Async
{

    public function index()
    {
        if (!$uri = $this->request->param('uri', false)) {
            return miss(404, false);
        }

        @set_time_limit(60);
        @ini_set('max_execution_time', '60');

        $method = $this->request->param('method', 'GET');
        $selector = $this->request->param('selector', '');
        $extract = $this->request->param('extract', '');
        $extract = $extract ? explode(',', $extract) : [];

        $uri = Filter::contentDecode($uri);
        $uri = str_replace('&nbsp;', '', $uri);

        try {
            if (!$this->cache->has($uri) || !$result = $this->cache->get($uri)) {
                $spider = new LibSpider;
                if ($spider->request($method, $uri)) {
                    $result = $selector
                        ? $spider->fetch($selector, $extract)
                        : $spider->html();
                    $this->cache->set($uri, $result);
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
}
