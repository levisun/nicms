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
use Symfony\Component\BrowserKit\HttpBrowser;
use Symfony\Component\DomCrawler\Crawler;

class Spider
{
    private $client = null;
    private $crawler = null;
    private $result = null;

    /**
     * 发起请求
     * @access public
     * @param  string $_method 请求类型 GET|POST
     * @param  string $_uri    请求地址 http://xxx
     * @return bool
     */
    public function request(string $_method, string $_uri): bool
    {
        $_method = strtoupper($_method);
        $key = md5($_method . $_uri);
        if (!Cache::has($key) || !$this->result = Cache::get($key)) {
            $this->client = new HttpBrowser;
            $this->crawler = $this->client->request($_method, $_uri);

            if (200 === $this->client->getInternalResponse()->getStatusCode()) {
                $this->result = $this->client->getInternalResponse()->getContent();

                // 过滤回车和多余空格
                $pattern = [
                    '~>\s+<~'          => '><',
                    '~>\s+~'           => '>',
                    '~\s+<~'           => '<',
                    '/( ){2,}/si'      => ' ',
                    '/(\s+\n|\r|\n)/s' => '',
                    '/(\t|\0|\x0B)/s'  => '',
                ];
                $this->result = (string) preg_replace(array_keys($pattern), array_values($pattern), $this->result);

                // 检查字符编码
                if (preg_match('/charset=["\']?([\w\-]{1,})["\']?/si', $this->result, $charset)) {
                    $charset = strtoupper($charset[1]);
                    if ($charset !== 'UTF-8') {
                        $this->result = iconv($charset . '//IGNORE', 'UTF-8', (string) $this->result);
                        $this->result = preg_replace_callback('/charset=["\']?([\w\-]{1,})["\']?/si', function ($matches) {
                            return str_replace($matches[1], 'UTF-8', $matches[0]);
                        }, $this->result);
                    }
                }

                Cache::set($key, htmlspecialchars($this->result, ENT_QUOTES));
            } else {
                return false;
            }
        } else {
            $this->crawler = new Crawler;
            $this->crawler->addContent(htmlspecialchars_decode($this->result, ENT_QUOTES));
        }

        return true;
    }

    public function getCrawler()
    {
        return $this->crawler ?: new Crawler;
    }

    /**
     * 获得响应数据
     * @access public
     * @param  string $_filter  CSS选择器,用于筛选数据
     * @param  array  $_extract 扩展属性,用于获得筛选出来标签的属性
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
     * 获得响应HTML文档
     * @access public
     * @return string
     */
    public function html(): string
    {
        return $this->result;
    }
}
