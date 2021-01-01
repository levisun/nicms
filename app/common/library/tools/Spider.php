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
    private $xhtml = '';
    public $agent = '';
    public $siteRegex = [];
    public $filterRegex = [];

    public function __construct(array $_filter_regex = [], array $_site_regex = [])
    {
        @ini_set('memory_limit', '16M');

        if (!empty($_filter_regex)) {
            $_filter_regex = array_map(function ($value) {
                return (string) preg_replace_callback('/./u', function (array $matches) {
                    if (3 <= strlen($matches[0])) {
                        $matches[0] = trim(json_encode($matches[0]), '"');
                        $matches[0] = (string) preg_replace_callback('/\\\u([0-9a-f]{4})/si', function ($chs) {
                            return '\x{' . $chs[1] . '}';
                        }, $matches[0]);
                    }
                    return $matches[0];
                }, $value);
            }, $_filter_regex);
        }

        $this->filterRegex = array_merge($this->filterRegex, $_filter_regex);
        $this->siteRegex = array_merge($this->siteRegex, $_site_regex);
    }

    public function pageInfo(int $_length = 0)
    {
        if (!$this->xhtml) return false;

        $result = [];

        preg_match('/<!\-\- website:([^<>]+) \-\->/si', htmlspecialchars_decode($this->getHtml(), ENT_QUOTES), $matches);
        $result['url'] = trim($matches[1]);
        $host = parse_url($result['url'], PHP_URL_SCHEME) . '://' . parse_url($result['url'], PHP_URL_HOST);

        if ($title = $this->select('title')) {
            $title = strip_tags(htmlspecialchars_decode($title[0], ENT_QUOTES));
            $title = str_replace(['_', '|'], '-', $title);
            $title = explode('-', $title);
            if (1 < count($title)) {
                unset($title[count($title) - 1]);
            }
            $result['title'] = implode('-', $title);
        }

        if ($keywords = $this->select('meta:keywords', ['content'])) {
            $result['keywords'] = isset($keywords[0]['content'])
                ? strip_tags(htmlspecialchars_decode($keywords[0]['content'], ENT_QUOTES))
                : '';
        }

        if ($description = $this->select('meta:description', ['content'])) {
            $result['description'] = isset($description[0]['content'])
                ? strip_tags(htmlspecialchars_decode($description[0]['content'], ENT_QUOTES))
                : '';
        }

        if ($links = $this->select('a', ['href'])) {
            foreach ($links as $value) {
                $value['href'] = isset($value['href']) ? htmlspecialchars_decode($value['href'], ENT_QUOTES) : '';
                if (false !== strpos($value['href'], 'javascript')) {
                    $value['href'] = '';
                }
                if (0 === strpos($value['href'], '#')) {
                    $value['href'] = '';
                }
                if (0 === strpos($value['href'], '//')) {
                    $value['href'] = parse_url($host, PHP_URL_SCHEME) . ':' . $value['href'];
                } elseif (0 === strpos($value['href'], '/')) {
                    $value['href'] = $host . $value['href'];
                }
                $result['links'][] = $value['href'];
            }
            $result['links'] = array_unique($result['links']);
            $result['links'] = array_filter($result['links']);
        }

        if ($imgs = $this->select('img', ['src'])) {
            foreach ($imgs as $value) {
                $value['src'] = isset($value['src']) ? htmlspecialchars_decode($value['src'], ENT_QUOTES) : '';
                if (0 === strpos($value['src'], '//')) {
                    $value['src'] = parse_url($host, PHP_URL_SCHEME) . ':' . $value['src'];
                } elseif (0 === strpos($value['src'], '/')) {
                    $value['src'] = $host . $value['src'];
                }
                $result['imgs'][] = $value['src'];
            }
            $result['imgs'] = array_unique($result['imgs']);
            $result['imgs'] = array_filter($result['imgs']);
        }

        $host = parse_url($result['url'], PHP_URL_HOST);
        if (isset($this->siteRegex[$host]) && $article = $this->select($this->siteRegex[$host])) {
            $article = array_map(function ($value) {
                $value = htmlspecialchars_decode($value, ENT_QUOTES);
                $value = Filter::html($value);
                $value = Filter::space($value, false);
                return $value;
            }, $article);
            $article = array_filter($article);
            $article = array_unique($article);
            $article = implode('', $article);
            $article = (string) preg_replace($this->filterRegex, '', $article);
            $article = Filter::htmlAttr($article, true);
            $article = strip_tags($article, '<p><img><table><tr><td><th>');
            $article = preg_replace(['/<img\s?>/i'], '', $article);
            $result['content'] = htmlspecialchars($article, ENT_QUOTES);
        }

        if (empty($result['content']) && $body = $this->select('body', [], false)) {
            $body = htmlspecialchars_decode($body[0], ENT_QUOTES);
            $body = preg_replace('/<\/?body[^<>]*>/i', '', $body);
            $body = Filter::html($body);
            $body = Filter::htmlAttr($body);

            $body = preg_replace([
                '/<ul[^<>]*>.*?<\/ul>/si',
                '/<ol[^<>]*>.*?<\/ol>/si',
                '/<dl[^<>]*>.*?<\/dl>/si',
                '/<a[^<>]*>[^<]*<\/a>/si',
                // 百度知道
                '/<span class="[\w\d]{10,}">[\w\d]{10,}<\/span>/si',
            ], '', $body);

            $pattern = [
                '/<article/si'    => '<div',
                '/<\/article/si'  => '</div',
                '/<h[\d]{1}/si'   => '<p',
                '/<\/h[\d]{1}/si' => '</p',
            ];
            $body = preg_replace(array_keys($pattern), array_values($pattern), $body);

            // 清除多余标签
            $body = (string) preg_replace_callback('/<(\/?)([\w]+)([^<>]*)>/si', function ($ele) {
                if (in_array($ele[2], ['div', 'p', 'br', 'table', 'tr', 'td', 'th'])) {
                    return '<' . $ele[1] . $ele[2] . $ele[3] . '>';
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
            $body = preg_replace_callback('/<img[^<>]+src=([^<>\s]+)[^<>]+>/si', function ($img) use ($host) {
                $img[1] = trim($img[1], '"\'');
                if (0 === strpos($img[1], '//')) {
                    $img[1] = parse_url($host, PHP_URL_SCHEME) . ':' . $img[1];
                } elseif (0 === strpos($img[1], '/')) {
                    $img[1] = $host . $img[1];
                }
                return '{TAG:img_src=' . $img[1] . '}';
            }, $body);

            // 替换表格
            $body = preg_replace_callback('/<table[^<>]*>(.*?)<\/table[^<>]*>/si', function ($table) {
                $table[1] = strip_tags($table[1], '<tr><td><th><br><p>');
                $table[1] = preg_replace_callback('/<(\/)?(tr|td|th)([^<>]*)>/si', function ($tr) {
                    return '{TAG:' . $tr[1] . $tr[2] . $tr[3] .'}';
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
                $content = (string) preg_replace($this->filterRegex, '', $content);

                // 恢复图片
                $content = preg_replace_callback('/\{TAG:img_src=([^<>\{\}\s]*)\}/si', function ($img) {
                    return '<img src="' . trim($img[1], '"\'') . '" />';
                }, $content);

                // 恢复表格
                $content = preg_replace_callback('/\{TAG:(.*?)\}/si', function ($table) {
                    return '<' . $table[1] . '>';
                }, $content);
            }
            $content = (string) preg_replace($this->filterRegex, '', $content);
            $result['content'] = htmlspecialchars($content, ENT_QUOTES);
        }

        return !empty($result) ? $result : false;
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
            return '[contains(@id,"' . trim($matches[1], '#.:') . '")]';
        }, $_expression);

        $_expression = (string) preg_replace_callback('/\.([\w\-]+)/si', function ($matches) {
            $matches[1] = trim($matches[1]);
            return '[contains(@class,"' . trim($matches[1], '#.:') . '")]';
        }, $_expression);

        $_expression = (string) preg_replace_callback('/:([\w\-]+)/si', function ($matches) {
            $matches[1] = trim($matches[1]);
            return '[contains(@name,"' . trim($matches[1], '#.:') . '")]';
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
        $nodeList = $xpath->query('//' . $_expression);

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

        $cache_key = 'spider request' . $_uri;
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
                'HTTP_HOST'            => parse_url($_uri, PHP_URL_HOST),
                'HTTP_USER_AGENT'      => $this->agent,
                'HTTP_REFERER'         => parse_url($_uri, PHP_URL_SCHEME) . '://' . parse_url($_uri, PHP_URL_HOST) . '/',
                'HTTP_ACCEPT'          => Request::header('accept'),
                'HTTP_ACCEPT_LANGUAGE' => Request::header('accept_language'),
                'HTTP_CONNECTION'      => Request::header('connection'),
            ]);

            try {
                $this->client->request(strtoupper($_method), $_uri);
            } catch (\Exception $e) {
                trace($_uri, 'warning');
                trace($e->getFile() . ' ' . $e->getLine() . ' ' . $e->getMessage(), 'warning');
                return $this;
            }

            // 请求失败
            if (200 !== $this->client->getInternalResponse()->getStatusCode()) {
                trace($_uri, 'warning');
                return $this;
            }

            // 获得实际URI
            $_uri = $this->client->getHistory()->current()->getUri();
            trace($_uri, 'info');

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
                preg_match('/charset=["\']?([\w\-]{1,})["\']?/si', $this->xhtml, $charset);
                $charset = !empty($charset)
                    ? strtoupper(trim($charset[1], '"\''))
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
            $this->xhtml = '<!-- website:' . $_uri . ' -->' . PHP_EOL . $this->xhtml;

            // 添加单页支持
            $base = parse_url($_uri, PHP_URL_PATH);
            $base = $base ? str_replace('\\', '/', rtrim(dirname($base), '\/')) . '/' : '';
            $base = parse_url($_uri, PHP_URL_SCHEME) . '://' . parse_url($_uri, PHP_URL_HOST) . $base;
            $this->xhtml = str_replace('<head>', '<head>' . '<base href="' . $base . '" />', $this->xhtml);

            $length = mb_strlen(strip_tags($this->xhtml), 'utf-8');

            $this->xhtml = htmlspecialchars($this->xhtml, ENT_QUOTES);

            if (300 < $length) {
                Cache::set($cache_key, $this->xhtml, 28800);
            }
        }

        return $this;
    }
}
