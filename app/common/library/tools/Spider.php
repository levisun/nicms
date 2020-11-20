<?php

/**
 *
 * 爬虫
 *
 * @package   NICMS
 * @category  app\common\library\tools
 * @author    失眠小枕头 [312630173@qq.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2020
 */

declare(strict_types=1);

namespace app\common\library\tools;

use think\facade\Request;
use app\common\library\Filter;
use Symfony\Component\BrowserKit\HttpBrowser;
use Symfony\Component\DomCrawler\Crawler;

class Spider
{
    private $client = null;
    private $crawler = null;
    private $result = '';
    private $agent = '';

    public function __construct()
    {
        @ini_set('memory_limit', '16M');
    }

    public function __set(string $_name, string $_value)
    {
        $this->$_name = $_value;
    }

    public function __destruct()
    {
        // 1.5至3.5秒间隔
        usleep(rand(1500000, 3500000));
    }

    /**
     * 发起请求
     * @access public
     * @param  string $_method 请求类型 GET|POST
     * @param  string $_uri    请求地址 http://xxx
     * @return mixed
     */
    public function request(string $_method, string $_uri)
    {
        // 非URL地址返回错误
        if (false === filter_var($_uri, FILTER_VALIDATE_URL)) {
            return false;
        }

        $this->client = new HttpBrowser;
        $this->client->followRedirects();
        $this->client->setMaxRedirects(5);
        $this->client->followMetaRefresh();
        $this->agent = $this->agent ?: Request::header('user_agent');

        $this->client->setServerParameters([
            'HTTP_HOST'            => parse_url($_uri, PHP_URL_HOST),
            'HTTP_USER_AGENT'      => $this->agent,
            'HTTP_REFERER'         => parse_url($_uri, PHP_URL_SCHEME) . '://' . parse_url($_uri, PHP_URL_HOST) . '/',
            'HTTP_ACCEPT'          => Request::header('accept'),
            'HTTP_ACCEPT_LANGUAGE' => Request::header('accept_language'),
            'HTTP_CONNECTION'      => Request::header('connection'),
            // 'CLIENT_IP'            => '117.117.117.117',
            // 'X_FORWARDED_FOR'      => '117.117.117.117',
            // 'HTTP_ACCEPT_ENCODING' => Request::header('accept-encoding'),
        ]);

        $this->client->request(strtoupper($_method), $_uri);

        // 请求失败
        if (200 !== $this->client->getInternalResponse()->getStatusCode()) {
            return false;
        }

        // 获得实际URI
        $_uri = $this->client->getHistory()->current()->getUri();

        // 获得HTML文档内容
        $this->result = $this->client->getInternalResponse()->getContent();

        // 检查字符编码
        $headers = $this->client->getInternalResponse()->getHeaders();
        if (isset($headers['content-type'][0]) && preg_match('/charset=([\w\-]+)/si', $headers['content-type'][0], $charset) && !empty($charset)) {
            $charset = strtoupper($charset[1]);
        } elseif (preg_match('/charset=["\']?([\w\-]{1,})["\']?/si', $this->result, $charset) && !empty($charset)) {
            $charset = strtoupper($charset[1]);
        }

        if ($charset !== 'UTF-8') {
            $charset = 0 === stripos($charset, 'GB') ? 'GBK' : $charset;
            $this->result = @iconv($charset, 'UTF-8//IGNORE', (string) $this->result);
        }

        $this->result = preg_replace_callback('/charset=["\']?([\w\-]{1,})["\']?/si', function ($charset) {
            return str_replace($charset[1], 'UTF-8', $charset[0]);
        }, $this->result);


        // 过滤回车和多余空格
        $this->result = Filter::symbol($this->result);
        $this->result = Filter::space($this->result);
        $this->result = Filter::php($this->result);

        // 添加访问网址
        $this->result = '<!-- website:' . $_uri . ' -->' . PHP_EOL . $this->result;

        // 添加单页支持
        $base = parse_url($_uri, PHP_URL_PATH);
        $base = $base ? str_replace('\\', '/', rtrim(dirname($base), '\/')) . '/' : '';
        $base = parse_url($_uri, PHP_URL_SCHEME) . '://' . parse_url($_uri, PHP_URL_HOST) . $base;
        $this->result = str_replace('<head>', '<head>' . '<base href="' . $base . '" />', $this->result);

        $this->result = htmlspecialchars($this->result, ENT_QUOTES);

        return $this;
    }

    public function getCrawler()
    {
        $this->crawler = new Crawler;
        if ($this->result) {
            $this->crawler->addContent(htmlspecialchars_decode($this->result, ENT_QUOTES));
        }
        return $this->crawler;
    }

    public function select(string $_element): array
    {
        $_element = (string) preg_replace_callback('/#([\w\d\-]+)/si', function ($matches) {
            $matches[1] = trim($matches[1]);
            return '[contains(@id,"' . trim($matches[1], '#.:') . '")]';
        }, $_element);

        $_element = (string) preg_replace_callback('/\.([\w\d\-]+)/si', function ($matches) {
            $matches[1] = trim($matches[1]);
            return '[contains(@class,"' . trim($matches[1], '#.:') . '")]';
        }, $_element);

        $_element = (string) preg_replace_callback('/:([\w\d\-]+)/si', function ($matches) {
            $matches[1] = trim($matches[1]);
            return '[contains(@name,"' . trim($matches[1], '#.:') . '")]';
        }, $_element);

        $_element = (string) str_replace([' ', '>'], '/', $_element);

        if (0 === strpos($_element, '[')) {
            $_element = '*' . trim($_element, '#.:');
        }

        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML(htmlspecialchars_decode($this->result, ENT_QUOTES));
        libxml_clear_errors();

        $xpath = new \DOMXPath($dom);
        $nodeList = $xpath->query('//' . $_element);

        $result = [];
        foreach ($nodeList as $node) {
            $node = $dom->saveHTML($node);
            $result[] = htmlspecialchars($node, ENT_QUOTES);
        }

        return $result;
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
        $this->crawler = $this->crawler ?: $this->getCrawler();
        $this->crawler->filter($_selector)->each(function (Crawler $node) use (&$_extract, &$content) {
            $result = $_extract ? $node->extract($_extract) : $node->html();
            $content[] = htmlspecialchars($result, ENT_QUOTES);
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
