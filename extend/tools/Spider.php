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

namespace tools;

use think\facade\Cache;
use think\facade\Request;
use app\common\library\Filter;
use Symfony\Component\BrowserKit\HttpBrowser;
use Symfony\Component\DomCrawler\Crawler;

class Spider
{
    private $client = null;
    private $crawler = null;
    private $xhtml = '';
    public  $statusCode = 200;
    public  $origin = '';
    public  $agent = '';

    public function __construct()
    {
        @ini_set('memory_limit', '16M');
    }

    /**
     * 获得网页内容
     * @access public
     * @return string
     */
    public function content(): string
    {
        if (!$this->xhtml) return '';

        if ($body = $this->select('body', [], false)) {
            $body = htmlspecialchars_decode($body[0], ENT_QUOTES);
            $body = preg_replace('/<\/?body[^<>]*>/i', '', $body);

            $body = preg_replace('/[\'"]+<\/?\w+[^<>]*>[\'"]+/uis', '', $body);

            $body = preg_replace([
                '/\/\*.*?\*\//uis',
                '/<\!\-\-.*?\-\->/uis',
                '/[\'"]+<.*?>[\'"]+/uis',
                '/<script[^<>]*>.*?<\/script>/uis',
                '/<\/?center[^<>]*>/uis',
            ], '', $body);

            $dom = new \DOMDocument('1.0', 'utf-8');
            libxml_use_internal_errors(true);
            $dom->loadHTML('<meta charset="utf-8">' . htmlspecialchars_decode($body, ENT_QUOTES));
            libxml_clear_errors();

            $xpath = new \DOMXPath($dom);
            $element = ['head', 'ul', 'ol', 'li', 'dl', 'dt', 'dd', 'a', 'input', 'button', 'template', 'script', 'style'];
            foreach ($element as $ele) {
                $nodes = $xpath->query('//' . $ele);
                foreach ($nodes as $node) {
                    $node->parentNode->removeChild($node);
                }
            }

            $nodes = $xpath->query('//*[@*]');
            foreach ($nodes as $node) {
                $attributes = [];
                foreach ($node->attributes as $attr) {
                    if (!in_array(strtolower($attr->nodeName), ['src'])) {
                        $attributes[] = $attr->nodeName;
                    }
                    if (false !== stripos(strtolower($node->getAttribute($attr->nodeName)), 'javascript')) {
                        $attributes[] = $attr->nodeName;
                    }
                    foreach ($attributes as $name) {
                        $node->removeAttribute($name);
                    }
                }
            }

            $content = [];
            $nodes = $xpath->query('//div');
            foreach ($nodes as $node) {
                $node = $dom->saveHTML($node);
                $node = Filter::space($node);
                while (preg_match('/<\w+[^<>\/]*><\/\w+>/uis', $node)) {
                    $node = preg_replace('/<\w+[^<>\/]*><\/\w+>/uis', '', $node);
                }

                if (strip_tags($node, '<img>') && 200 < mb_strlen(strip_tags($node, '<img>'), 'utf-8')) {
                    if (!stripos($node, 'copyright')) {
                        $content[] = $node;
                    }
                }
            }
            $content = end($content);
            $content = preg_replace_callback('/<(\/?)(img|table|tr|th|td)([^<>]*)>/si', function ($ele) {
                return '[' . $ele[1] . $ele[2] . $ele[3] . ']';
            }, $content);
            // halt($content);

            preg_match_all('/>.*?</uis', $content, $matches);
            $matches = array_map(function ($value) {
                $value = ltrim($value, '>');
                $value = rtrim($value, '<');
                return trim($value);
            }, (array) $matches[0]);
            $matches = array_filter($matches);
            $content = '<p>' . implode('</p><p>', $matches) . '</p>';

            $content = preg_replace_callback('/\[(\/?)(img|table|tr|th|td)([^\[\]]*)\]/uis', function ($ele) {
                return '<' . $ele[1] . $ele[2] . $ele[3] . '>';
            }, $content);

            $content = htmlspecialchars($content, ENT_QUOTES);
        }

        return !empty($content) ? $content : '';
    }

    /**
     * 获得网页标题
     * @access public
     * @return string
     */
    public function title(): string
    {
        if ($title = $this->select('title')) {
            $title = strip_tags(htmlspecialchars_decode($title[0], ENT_QUOTES));
            $title = preg_replace('/(\d+)\-(\d+)/i', '$1&#45;$2', $title);
            $title = str_replace(['_', '|'], '-', $title);
            list($title) = explode('-', $title);
            $title = Filter::space($title);
        }

        return !empty($title) ? $title : '';
    }

    /**
     * 获得网页关键词
     * @access public
     * @return string
     */
    public function keywords(): string
    {
        if ($keywords = $this->select('meta:keywords', ['content'])) {
            $keywords = isset($keywords[0]['content'])
                ? strip_tags(htmlspecialchars_decode($keywords[0]['content'], ENT_QUOTES))
                : '';
            $keywords = Filter::space($keywords);
        }

        return !empty($keywords) ? $keywords : '';
    }

    /**
     * 获得网页描述
     * @access public
     * @return string
     */
    public function description(): string
    {
        if ($description = $this->select('meta:description', ['content'])) {
            $description = isset($description[0]['content'])
                ? strip_tags(htmlspecialchars_decode($description[0]['content'], ENT_QUOTES))
                : '';
            $description = Filter::space($description);
        }

        return !empty($description) ? $description : '';
    }

    /**
     * 获得网页链接
     * @access public
     * @return array
     */
    public function links(): array
    {
        if ($links = $this->select('a', ['href'])) {
            $scheme = parse_url($this->origin, PHP_URL_SCHEME);
            $host = $scheme . '://' . parse_url($this->origin, PHP_URL_HOST);

            foreach ($links as $key => $element) {
                $element['href'] = htmlspecialchars_decode($element['href']);

                if ('//' == substr($element['href'], 0, 2)) {
                    $element['href'] = $scheme . ':' . $element['href'];
                } elseif ('/' == substr($element['href'], 0, 1)) {
                    $element['href'] = $host . $element['href'];
                } elseif ('?' == substr($element['href'], 0, 1)) {
                    $element['href'] = $host . $element['href'];
                } elseif ('#' == substr($element['href'], 0, 1)) {
                    unset($links[$key]);
                    continue;
                } elseif ('javascript' == substr($element['href'], 0, 10)) {
                    unset($links[$key]);
                    continue;
                } elseif ('http' == substr($element['href'], 0, 4)) {
                } else {
                    $element['href'] = $host . '/' . $element['href'];
                }

                $links[$key] = $element;
            }
        }

        return !empty($links[0]) ? $links : [];
    }

    /**
     * 获得响应数据
     * @access public
     * @param  string $_element CSS选择器,用于筛选数据
     * @param  array  $_attr    扩展属性,用于获得筛选出来标签的属性
     * @param  bool   $_filter  过滤信息
     * @return array|boole
     */
    public function select(string $_expression, array $_attr = [], bool $_filter = false)
    {
        if (!$this->xhtml) return false;

        $_expression = (string) preg_replace_callback('/#([\w\-]+)/si', function ($matches) {
            $matches[1] = trim($matches[1]);
            return '[contains(@id,"' . $matches[1] . '")]';
        }, $_expression);

        $_expression = (string) preg_replace_callback('/\.([\w\-]+)/si', function ($matches) {
            $matches[1] = trim($matches[1]);
            return '[contains(@class,"' . $matches[1] . '")]';
        }, $_expression);

        $_expression = (string) preg_replace_callback('/:([\w\-]+)/si', function ($matches) {
            $matches[1] = trim($matches[1]);
            return '[contains(@name,"' . $matches[1] . '")]';
        }, $_expression);

        $_expression = (string) str_replace([' ', '>'], '//', $_expression);

        $_expression = 0 === strpos($_expression, '[')
            ? '*' . trim($_expression, '#.:')
            : trim($_expression, '#.:');

        $result = [];

        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<meta charset="utf-8">' . htmlspecialchars_decode($this->xhtml, ENT_QUOTES));
        libxml_clear_errors();

        $xpath = new \DOMXPath($dom);
        if ($nodeList = $xpath->query('//' . $_expression)) {
            if (!empty($_attr)) {
                $pattern = '(' . implode('|', $_attr) . ')=["\']+(.*?)["\']+';
            }

            foreach ($nodeList as $node) {
                $node = $dom->saveHTML($node);

                // 清除多余标签
                $node = $_filter ? Filter::html($node) : $node;

                if (isset($pattern) && false !== preg_match_all('/' . $pattern . '/uis', $node, $matches) && !empty($matches)) {
                    $node = [
                        'html' => htmlspecialchars($node, ENT_QUOTES)
                    ];
                    for ($i = 0; $i < count($matches[1]); $i++) {
                        $node[$matches[1][$i]] = $matches[2][$i];
                    }
                    $result[] = $node;
                } else {
                    $result[] = htmlspecialchars($node, ENT_QUOTES);
                }
            }
        }

        return !empty($result) ? $result : false;
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
    public function getHtml($_decode = false): string
    {
        return $_decode ? htmlspecialchars_decode($this->xhtml, ENT_QUOTES) : $this->xhtml;
    }

    public function getCrawler()
    {
        $this->crawler = new Crawler;
        if ($this->xhtml) {
            $this->crawler->addContent(htmlspecialchars_decode($this->xhtml, ENT_QUOTES));
        }
        return $this->crawler;
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
            return $this;
        }

        $this->xhtml = '';

        $cache_key = 'spider request' . $_uri . $this->agent;
        if (!Cache::has($cache_key) || !$this->xhtml = Cache::get($cache_key)) {
            // 1000000 = 1s
            // 0.5s~1.5s
            usleep(mt_rand(5, 15) * 100000);

            $this->client = new HttpBrowser;
            $this->client->followRedirects();
            $this->client->setMaxRedirects(5);
            $this->client->followMetaRefresh();
            $this->agent = $this->agent ?: Request::header('user_agent');

            $this->client->setServerParameters([
                'HTTP_HOST'       => parse_url($_uri, PHP_URL_HOST),
                'HTTP_USER_AGENT' => $this->agent,
                'HTTP_REFERER'    => parse_url($_uri, PHP_URL_SCHEME) . '://' . parse_url($_uri, PHP_URL_HOST) . '/',
            ]);

            try {
                $this->client->request(strtoupper($_method), $_uri);
            } catch (\Exception $e) {
                trace($_uri, 'warning');
                trace($e->getFile() . ' ' . $e->getLine() . ' ' . $e->getMessage(), 'warning');
                return $this;
            }

            // 请求失败
            $this->statusCode = $this->client->getInternalResponse()->getStatusCode();
            if (200 !== $this->statusCode) {
                trace($_uri, 'warning');
                return $this;
            }

            // 获得实际URI
            $this->origin = $this->client->getHistory()->current()->getUri();
            trace($this->origin, 'info');

            // 获得HTML文档内容
            $this->xhtml = $this->client->getInternalResponse()->getContent();

            // 检查字符编码
            $headers = $this->client->getInternalResponse()->getHeaders();
            if (isset($headers['content-type'][0])) {
                preg_match('/charset=([\w\-]+)/si', $headers['content-type'][0], $charset);
                $charset = !empty($charset[1])
                    ? strtoupper(trim($charset[1], '"\''))
                    : '';
            }

            if (!$charset) {
                preg_match('/charset=["\']?([\w\-]{1,})["\']?/si', $this->xhtml, $matches);
                $charset = !empty($matches)
                    ? strtoupper(trim($matches[1], '"\''))
                    : '';
            }

            // 转换字符编码
            if ($charset !== 'UTF-8') {
                $charset = 0 === stripos($charset, 'GB') ? 'GBK' : $charset;
                $this->xhtml = @iconv($charset, 'UTF-8//IGNORE', (string) $this->xhtml);
            }
            $this->xhtml = preg_replace_callback('/charset=["\']?([\w\-]{1,})["\']?/si', function ($charset) {
                return str_replace($charset[1], 'UTF-8', $charset[0]);
            }, $this->xhtml);


            // 过滤回车和多余空格
            $this->xhtml = Filter::symbol($this->xhtml);
            $this->xhtml = Filter::php($this->xhtml);

            // 添加访问网址
            $this->xhtml = '<!-- website:' . $this->origin . ' -->' . PHP_EOL . $this->xhtml;

            // 添加单页支持
            if (false !== stripos($this->xhtml, '<base')) {
                $this->xhtml = preg_replace_callback('/<base[^<>]+href=["\']+([^<>"\']+)["\']+[^<>]*>/i', function ($ele) {
                    $base = parse_url($this->origin, PHP_URL_SCHEME) . '://' . parse_url($this->origin, PHP_URL_HOST);
                    $base = false !== stripos($ele[1], 'http') ? $ele[1] : $base . $ele[1];
                    return '<base href="' . $base . '" /><base target="_blank" />';
                }, $this->xhtml);
            } else {
                $base = (string) parse_url($this->origin, PHP_URL_PATH);
                $base = rtrim(dirname($base), '\/')
                    ? str_replace('\\', '/', rtrim(dirname($base), '\/')) . '/'
                    : str_replace('\\', '/', rtrim($base, '\/')) . '/';
                $base = parse_url($this->origin, PHP_URL_SCHEME) . '://' . parse_url($this->origin, PHP_URL_HOST) . $base;
                $this->xhtml = preg_replace('/(<head[^<>]*>)/i', '$1<base href="' . $base . '" /><base target="_blank" />', $this->xhtml);
            }

            $length = mb_strlen(strip_tags($this->xhtml), 'utf-8');

            $this->xhtml = htmlspecialchars($this->xhtml, ENT_QUOTES);

            if (300 < $length) {
                Cache::set($cache_key, $this->xhtml, 28800);
            }
        }

        if ($this->xhtml) {
            preg_match('/<!\-\- website:([^<>]+) \-\->/si', htmlspecialchars_decode($this->getHtml(), ENT_QUOTES), $matches);
            $this->origin = trim($matches[1]);
        }

        return $this;
    }
}
