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
use app\common\library\DataFilter;
use Symfony\Component\BrowserKit\HttpBrowser;

class Spider extends Async
{

    public function index()
    {
        if ($uri = $this->request->param('uri', false)) {
            usleep(rand(1500000, 2500000));

            $method = $this->request->param('method', 'GET');
            $method = strtoupper($method);
            $client = new HttpBrowser;
            $crawler = $client->request($method, $uri);
            // 正常访问
            if (200 === $client->getInternalResponse()->getStatusCode()) {
                // 有选择器时
                if ($selector = $this->request->param('selector', false)) {
                    // 扩展属性
                    $extract = $this->request->param('extract', '');
                    $extract = $extract ? explode(',', $extract) : [];

                    $result = [];
                    $crawler->filter($selector)->each(function ($node) use (&$extract, &$result) {
                        $result[] = $extract
                            ? DataFilter::encode($node->extract($extract))
                            : DataFilter::encode($node->html());
                    });
                } else {
                    $result = $client->getInternalResponse()->getContent();
                    $result = htmlspecialchars($this->html, ENT_QUOTES);
                }
            }

            return $this->cache(true)->success('spider success', $result);
        }

        return miss(404);
    }
}
