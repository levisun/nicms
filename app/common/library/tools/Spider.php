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

use think\facade\Cache;
use think\facade\Request;
use app\common\library\Filter;
use Symfony\Component\BrowserKit\HttpBrowser;
use Symfony\Component\DomCrawler\Crawler;

class Spider
{
    private $client = null;
    private $crawler = null;
    private $result = '';
    public $agent = '';

    public function __construct()
    {
        @ini_set('memory_limit', '16M');
    }

    public function pageInfo(int $_length = 0)
    {
        $result = [];
        if (false !== preg_match('/<!\-\- website:([^<>]+)\-\->/si', htmlspecialchars_decode($this->getHtml(), ENT_QUOTES), $matches) && !empty($matches)) {
            $result['url'] = trim($matches[1]);
        }

        $title = $this->select('title');
        $title = strip_tags(htmlspecialchars_decode($title[0], ENT_QUOTES));
        $title = str_replace(['_', '|'], '-', $title);
        list($title) = explode('-', $title, 2);
        $result['title'] = $title;

        $keywords = $this->select('meta:keywords', ['content']);
        $result['keywords'] = isset($keywords[0]) ? strip_tags(htmlspecialchars_decode($keywords[0]['content'], ENT_QUOTES)) : '';

        $description = $this->select('meta:description', ['content']);
        $result['description'] = isset($description[0]) ? strip_tags(htmlspecialchars_decode($description[0]['content'], ENT_QUOTES)) : '';

        $links = $this->select('a', ['href']);
        $host = parse_url($result['url'], PHP_URL_SCHEME) . '://' . parse_url($result['url'], PHP_URL_HOST);
        foreach ($links as $value) {
            $value['href'] = isset($value['href']) ? htmlspecialchars_decode($value['href'], ENT_QUOTES) : '';
            if (false !== strpos($value['href'], 'javascript')) {
                $value['href'] = '';
            }
            if (0 === strpos($value['href'], '#')) {
                $value['href'] = '';
            }
            if (0 === strpos($value['href'], '/')) {
                $value['href'] = $host . $value['href'];
            }
            $result['links'][] = $value['href'];
        }
        $result['links'] = array_unique($result['links']);
        $result['links'] = array_filter($result['links']);

        $imgs = $this->select('img', ['src']);
        foreach ($imgs as $value) {
            $value['src'] = isset($value['src']) ? htmlspecialchars_decode($value['src'], ENT_QUOTES) : '';
            if (0 === strpos($value['src'], '/')) {
                $value['src'] = $host . $value['src'];
            }
            $result['imgs'][] = $value['src'];
        }
        $result['imgs'] = array_unique($result['imgs']);
        $result['imgs'] = array_filter($result['imgs']);

        $body = $this->select('body');
        $body = htmlspecialchars_decode($body[0], ENT_QUOTES);
        $body = preg_replace([
            '/<\/?body[^<>]*>/i',
            '/<!\-\-.*?\-\->/si',
            '/<style[^<>]*>.*?<\/style>/si',
            '/<ul[^<>]*>.*?<\/ul>/si',
            '/<ol[^<>]*>.*?<\/ol>/si',
            '/<a[^<>]*>/si',
            '/<\/a>/si',
            '/<script[^<>]*>.*?<\/script>/si',
            // 百度知道
            '/<span class="[\w\d]{10,}">[\d]{4,}<\/span>/si',
        ], '', $body);

        $pattern = [
            '/>\s+/' => '>',
            '/\s+</' => '<',
            '/(\x{00a0}|\x{0020}|\x{3000}|\x{feff})/u' => ' ',
            '/　/si' => ' ',
            '/ {2,}/si' => ' ',
            '/<article/si' => '<div',
            '/<\/article/si' => '</div',
            '/<h[\d]{1}/si' => '<p',
            '/<\/h[\d]{1}/si' => '</p',
        ];
        $body = preg_replace(array_keys($pattern), array_values($pattern), $body);

        // 清除多余标签
        $body = (string) preg_replace_callback('/<(\/?)([\w]+)([^<>]*)>/si', function ($ele) {
            if (in_array($ele[2], ['div', 'p', 'br', 'span', 'table', 'tr', 'td', 'th'])) {
                return '<' . $ele[1] . $ele[2] . '>';
            } elseif ('img' == $ele[2]) {
                return $ele[0];
            }
        }, $body);
        while (preg_match('/<div[^<>]*><div/si', $body)) {
            $body = preg_replace('/<div[^<>]*><div/si', '<div', $body);
        }
        while (preg_match('/<div[^<>]*><\/div>/si', $body)) {
            $body = preg_replace('/<div[^<>]*><\/div>/si', '', $body);
        }
        while (preg_match('/<\/div><\/div>/si', $body)) {
            $body = preg_replace('/<\/div><\/div>/si', '</div>', $body);
        }

        // 替换图片
        $body = preg_replace_callback('/<img[^<>]+src=([^<>\s]+)[^<>]+>/si', function ($img) {
            return '{TAG:img_src=' . trim($img[1], '"\'') . '}';
        }, $body);

        // 替换表格
        $body = preg_replace_callback('/<table[^<>]*>(.*?)<\/table[^<>]*>/si', function ($table) {
            $table[1] = strip_tags($table[1], '<tr><td><th><br><p>');
            $table[1] = preg_replace_callback('/<(\/)?(tr|td|th)>/si', function ($tr) {
                return '{TAG:' . $tr[1] . $tr[2] . '}';
            }, $table[1]);
            return '{TAG:table}' . $table[1] . '{TAG:/table}';
        }, $body);

        // 标签转回车
        $body = str_ireplace(['<p>', '</p>', '<br>', '<br />', '<br/>'], PHP_EOL, $body);

        // 匹配内容
        $pattern = '/>[^<>]{160,}</si';
        if (false !== preg_match_all($pattern, $body, $matches)) {
            $content = (array) $matches[0];
            foreach ($content as $key => $value) {
                $content[$key] = trim($value, '><') . PHP_EOL;
            }
            $content = implode('', $content);

            // 截取
            if ($_length && $_length < mb_strlen($content, 'utf-8')) {
                if ($position = mb_strpos($content, '{TAG:/table}', $_length, 'utf-8')) {
                    $content = mb_substr($content, 0, $position + 12, 'utf-8');
                } elseif ($position = mb_strpos($content, '。', $_length, 'utf-8')) {
                    $content = mb_substr($content, 0, $position + 1, 'utf-8');
                } elseif ($position = mb_strpos($content, '.', $_length, 'utf-8')) {
                    $content = mb_substr($content, 0, $position + 1, 'utf-8');
                } elseif ($position = mb_strpos($content, ' ', $_length, 'utf-8')) {
                    $content = mb_substr($content, 0, $position + 1, 'utf-8');
                } else {
                    $content = mb_substr($content, 0, $_length, 'utf-8');
                }
            }

            // 恢复格式
            $content = explode('<br />', nl2br((string) $content));
            $content = array_map('trim', $content);
            $content = array_filter($content);
            $content = '<p>' . implode('</p><p>', $content) . '</p>';

            // 清除版权等信息
            $pattern = [
                // 版权
                '/[^<>]*(©|copyright|&copy;)+[^<>]+/i',
                // 备案号
                '/[^<>]*\x{5907}\x{6848}\x{53f7}[^<>]+/u',
                // 许可证
                '/[^<>]*\x{8bb8}\x{53ef}\x{8bc1}[^<>]+/u',
                // 百度
                '/[^<>]*\x{767e}\x{5ea6}[^<>]*/u',
                // 关注
                '/[^<>]*\x{5173}\x{6ce8}[^<>]*/u',
                // 公众号
                '/[^<>]*\x{516c}\x{4f17}\x{53f7}[^<>]*/u',
                // 域名
                '/[^<>]*[htps]{4,5}:\/\/[^\s]*[^<>]*/i',
                '/[\w]+(\.[\w]{2,4})+/i',
                // 无用字符
                '/[a-zA-Z0-9]{10,}/i',

                // 空行
                '/<[\w]+>(<br *\/*>)*<\/[\w]+>/si',
                '/<[\w]+>.{1,2}<\/[\w]+>/si',
            ];
            $content = (string) preg_replace($pattern, '', $content);

            // 恢复表格
            $content = preg_replace_callback('/\{TAG:([\w\/]+)\}/si', function ($table) {
                return '<' . $table[1] . '>';
            }, $content);
            // 恢复图片
            $content = preg_replace_callback('/\{TAG:img_src=([^<>\s]+)\}/si', function ($img) {
                return '<img src="' . trim($img[1], '"\'') . '" />';
            }, $content);
        }
        $result['content'] = htmlspecialchars($content, ENT_QUOTES);

        return $result;
    }

    /**
     * 获得响应数据
     * @access public
     * @param  string $_element CSS选择器,用于筛选数据
     * @param  array  $_attr    扩展属性,用于获得筛选出来标签的属性
     * @return array
     */
    public function select(string $_element, array $_attr = []): array
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

        $_element = (string) str_replace([' ', '>'], '//', $_element);

        if (0 === strpos($_element, '[')) {
            $_element = '*' . trim($_element, '#.:');
        }

        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML(htmlspecialchars_decode($this->result, ENT_QUOTES));
        libxml_clear_errors();

        $xpath = new \DOMXPath($dom);
        $nodeList = $xpath->query('//' . $_element);

        if (!empty($_attr)) {
            $pattern = '(' . implode('|', $_attr) . ')=["\']+(.*?)["\']+';
        }

        $result = [];
        foreach ($nodeList as $node) {
            $node = $dom->saveHTML($node);
            if (isset($pattern) && false !== preg_match_all('/' . $pattern . '/si', $node, $matches) && !empty($matches)) {
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
    public function getHtml(): string
    {
        return $this->result;
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

        $cache_key = 'spider request' . $_uri;
        if (!Cache::has($cache_key) || !$this->result = Cache::get($cache_key)) {
            usleep(rand(100, 150) * 10000);

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
            trace($_uri, 'info');

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

            $length = mb_strlen(strip_tags($this->result), 'utf-8');

            $this->result = htmlspecialchars($this->result, ENT_QUOTES);

            if (300 < $length) {
                Cache::set($cache_key, $this->result, 28800);
            }
        }

        return $this;
    }
}
