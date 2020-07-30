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
use app\common\library\Filter;
use app\common\library\Spider as LibSpider;

class Spider extends Async
{

    public function index()
    {
        // $this->validate->referer() &&
        if ($uri = $this->request->param('uri', false)) {
            @set_time_limit(60);
            @ini_set('max_execution_time', '60');
            usleep(rand(7500000, 15000000));

            $method = $this->request->param('method', 'GET');
            $selector = $this->request->param('selector', '');
            $extract = $this->request->param('extract', '');

            $uri = Filter::decode($uri);
            $uri = str_replace('&nbsp;', '', $uri);

            try {
                $result = $this->request($method, $uri, $selector, $extract);
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

    /**
     * 请求
     * @access private
     * @param  string $_method
     * @param  string $_uri
     * @param  string $_selector
     * @param  string $_extract
     * @return string|array
     */
    private function request(string $_method, string $_uri, string $_selector, string $_extract)
    {
        $spider = new LibSpider;
        if ($spider->request($_method, $_uri)) {
            // 有选择器时
            if ($_selector) {
                // 扩展属性
                $result = $spider->fetch($_selector, $_extract ? explode(',', $_extract) : []);
                shuffle($result);
            } else {
                $result = $spider->html();

                // 解析跳转代码
                $regex = '/http\-equiv="refresh"\scontent=".*?url=(.*?)">/si';
                $result = preg_replace_callback($regex, function ($refresh) use($_method, $_selector, $_extract) {
                    $refresh = trim($refresh[1], '\'"');
                    return $this->request($_method, $refresh, $_selector, $_extract);
                }, htmlspecialchars_decode($result, ENT_QUOTES));
            }

            return $result;
        }

        return '';
    }
}
