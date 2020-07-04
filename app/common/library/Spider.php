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
use app\common\library\Filter;
use Symfony\Component\BrowserKit\HttpBrowser;
use Symfony\Component\DomCrawler\Crawler;

class Spider
{
    private $crawler = null;
    private $result = '';

    public function __construct()
    {
        @set_time_limit(600);
        @ini_set('max_execution_time', '600');
        @ini_set('memory_limit', '16M');
    }

    /**
     * 发起请求
     * @access public
     * @param  string $_method 请求类型 GET|POST
     * @param  string $_uri    请求地址 http://xxx
     * @return bool
     */
    public function request(string $_method, string $_uri): bool
    {
        // 非URL地址返回错误
        if (false === filter_var($_uri, FILTER_VALIDATE_URL)) {
            return false;
        }

        $_method = strtoupper($_method);
        $key = md5($_method . $_uri);

        if (!Cache::has($key) || !$this->result = Cache::get($key)) {
            $client = new HttpBrowser;
            $client->request($_method, $_uri);

            // 请求失败
            if (200 !== $client->getInternalResponse()->getStatusCode()) {
                return false;
            }

            // 获得HTML文档内容
            $this->result = $client->getInternalResponse()->getContent();

            // 过滤回车和多余空格
            $this->result = Filter::symbol($this->result);
            $this->result = Filter::space($this->result);

            // 检查字符编码
            if (preg_match('/charset=["\']?([\w\-]{1,})["\']?/si', $this->result, $charset)) {
                $charset = strtoupper($charset[1]);
                if ($charset !== 'UTF-8') {
                    $charset = 0 === stripos($charset, 'GB') ? 'GBK' : $charset;
                    $this->result = @iconv($charset, 'UTF-8//IGNORE', (string) $this->result);
                    $this->result = preg_replace_callback('/charset=["\']?([\w\-]{1,})["\']?/si', function ($matches) {
                        return str_replace($matches[1], 'UTF-8', $matches[0]);
                    }, $this->result);
                }
            }

            $this->result = htmlspecialchars($this->result, ENT_QUOTES);

            Cache::set($key, $this->result, 28800);
        }

        // 重新附加DOM文档
        $this->crawler = new Crawler;
        $this->crawler->addContent(htmlspecialchars_decode($this->result, ENT_QUOTES));

        return true;
    }

    public function getCrawler()
    {
        $this->crawler = new Crawler;
        if ($this->result) {
            $this->crawler->addContent(htmlspecialchars_decode($this->result, ENT_QUOTES));
        }
        return $this->crawler;
    }

    /**
     * 获得响应数据
     * @access public
     * @param  string $_selector CSS选择器,用于筛选数据
     * @param  array  $_extract  扩展属性,用于获得筛选出来标签的属性
     * @return array
     */
    public function fetch(string $_selector, array $_extract = []): array
    {
        $content = [];
        $this->crawler->filter($_selector)->each(function (Crawler $node) use (&$_extract, &$content) {
            $result = $_extract ? $node->extract($_extract) : $node->html();
            $content[] = Filter::encode($result);
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
