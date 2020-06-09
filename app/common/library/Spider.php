<?php

/**
 *
 * 爬虫
 *
 * @package   NICMS
 * @category  app\common\library
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2020
 */

declare(strict_types=1);

namespace app\common\library;

use think\facade\Cache;
use think\facade\Request;
use Symfony\Component\BrowserKit\HttpBrowser;

class Spider
{
    private $client;
    private $crawler;

    public function __construct(string $_method, string $_uri)
    {
        $_method = strtoupper($_method);
        $this->client = new HttpBrowser;
        $this->crawler = $this->client->request($_method, $_uri);
    }

    /**
     * 获得响应数据
     * @access public
     * @param  string $_filter
     * @param  array  $_extract
     * @return array
     */
    public function fetch(string $_filter, array $_extract = []): array
    {
        $content = [];
        $this->crawler->filter($_filter)->each(function ($node) use (&$_extract, &$content) {
            $content[] = $_extract ? $node->extract($_extract) : $node->html();
        });

        return $content;
    }

    /**
     * 获得响应数据
     * @access public
     * @return string
     */
    public function getContent(): string
    {
        $content = '';
        if (200 === $this->client->getInternalResponse()->getStatusCode()) {
            $content = $this->client->getInternalResponse()->getContent();
        }
        return $content;
    }
}
