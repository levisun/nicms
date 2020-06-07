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
            usleep(rand(1000000, 1500000));

            $uri = urldecode($uri);
            $base_uri  = parse_url($uri, PHP_URL_SCHEME) . '://';
            $base_uri .= parse_url($uri, PHP_URL_HOST) . '/';
            $url_path  = parse_url($uri, PHP_URL_PATH);
            $url_query = parse_url($uri, PHP_URL_QUERY);
            $url_query = $url_query ? '?' . $url_query : '';
            $uri = $url_path . $url_query;
            unset($url_path, $url_query);

            $spider = new LibSpider($base_uri);

            $preg = $this->request->param('preg', '');
            $filter = (bool) $this->request->param('filter', true);
            if ($result = $spider->fetch($uri, $preg, $filter)) {
                // 请勿开启缓存
                return $this->cache(true)->success('spider success', $result);
            } else {
                return $this->error('spider error');
            }
        }
    }
}
