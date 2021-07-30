<?php

/**
 * 网页翻译
 * 自己抽时间写的. 曹那个人做什么都急得要死, 压根没有耐心去完善一个功能.
 */

declare(strict_types=1);

namespace tools;

class WebTranslation
{
    private $xml = '';
    private $dom = null;
    private $xpath = null;

    public $text = [];
    public $test = '';

    public function __construct(string $_xml)
    {
        $this->dom = new \DOMDocument('1.0', 'utf-8');
        libxml_use_internal_errors(true);
        $pattern = [
            '/<!\-{2}[^<>]+\-{2}>/i' => '',
            '/&nbsp;/i'              => '',
            '/&#\d{4,};/i'           => '',
            '/>\s+</is'              => '><',
        ];
        $_xml = preg_replace(array_keys($pattern), array_values($pattern), $_xml);
        $this->dom->loadHTML('<meta charset="utf-8">' . $_xml);
        $this->xml = $this->dom->saveHTML();
        libxml_clear_errors();
        $this->xpath = new \DOMXPath($this->dom);

        @set_time_limit(0);
        @ini_set('max_execution_time', '0');
        @ini_set('memory_limit', '128M');
    }

    public function web()
    {
        // 翻译li,th,td,dt,dd,h1-6标签内容
        $expression = [
            'li',
            'th', 'td',
            'dt', 'dd',
            'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
        ];
        // 不用过滤的标签
        $allowed_tags = '';
        foreach ($expression as $ele) {
            $allowed_tags .= '<' . $ele . '>';
        }
        // 循环翻译内容
        foreach ($expression as $ele) {
            // 获得标签及内容
            $nodes = $this->xpath->query('//' . $ele);
            foreach ($nodes as $node) {
                $node = $this->dom->saveHTML($node);

                // 如果内容中有div标签跳出不翻译
                if (stripos($node, '<div')) continue;

                // 过滤所有标签,获得纯文字内容并翻译
                $txt = strip_tags($node);
                $trans = str_replace('&amp;', '&', trim($txt));
                $trans = $this->trans($trans, 'en', 'zh');
                // var_dump($node, $txt, $trans);

                // 替换原文
                $this->xml = str_replace($node, str_replace($txt, $trans, strip_tags($node, $allowed_tags)), $this->xml);
            }
        }

        // 翻译input标签内容
        $nodes = $this->xpath->query('//input');
        foreach ($nodes as $node) {
            $node = $this->dom->saveHTML($node);
            // 获得内容
            $pattern = '/placeholder=["\']+([^\x{4e00}-\x{9fa5}"\']+)["\']+/ius';
            // preg_match_all($pattern, $this->xml, $matches);
            // halt($matches);
            preg_replace_callback($pattern, function ($item) {
                // 过滤所有标签,获得纯文字内容并翻译
                $txt = strip_tags($item[1]);
                $trans = str_replace('&amp;', '&', trim($txt));
                $trans = $this->trans($trans, 'en', 'zh');
                // var_dump($item[0], $txt, $trans);

                // 替换原文
                $this->xml = str_replace($item[0], str_replace($txt, $trans, $item[0]), $this->xml);
            }, $node);
        }

        // http://www.caiji.com/spider/run/5.1.1/Answers.html?type=get_article&title=Mobile%20phone%20shows%203G%2C%204G%2C%20H%2C%20H%2B%2C%20E%20and%20G%20in%20the%20bar%20-%20What%20does%20this%20mean%3F&host=https%3A%2F%2Fwww.askingbox.com%2Fquestion%2Fmobile-phone-shows-3g-h-h-e-and-g-in-the-bar-what-does-this-mean

        // 翻译p标签内容
        $nodes = $this->xpath->query('//p');
        foreach ($nodes as $node) {
            $node = $this->dom->saveHTML($node);
            // 如果内容中有div标签跳出不翻译
            if (stripos($node, '<div')) continue;
            // if (stripos($node, '<span')) continue;

            // 过滤a,i,b,u,strong标签并原文替换
            $str_node = preg_replace(['/<\/?a[^<>]*>/i', '/<\/?(i|b|u|strong)>/i'], '', $node);
            $this->xml = str_replace($node, $str_node, $this->xml);

            // 获得内容
            $pattern = '/<([\w\d]+)([^<>]*)>[^<>\x{4e00}-\x{9fa5}]+<\/[\w\d]+>/ius';
            // preg_match_all($pattern, $this->xml, $matches);
            // halt($matches);
            preg_replace_callback($pattern, function ($item) {
                // 过滤所有标签,获得纯文字内容并翻译
                $txt = strip_tags($item[0]);
                $trans = str_replace('&amp;', '&', trim($txt));
                $trans = $this->trans(preg_replace('/[\r\n]+/i', ' ', $trans), 'en', 'zh');
                // var_dump($item[0], $txt, $trans);

                // 替换原文
                $this->xml = str_ireplace($item[0], str_replace($txt, $trans, $item[0]), $this->xml);
            }, $str_node);
        }



        // 翻译div标签内容
        $nodes = $this->xpath->query('//div');
        foreach ($nodes as $node) {
            $node = $this->dom->saveHTML($node);

            // 获得内容
            $pattern = '/<(\/?[\w\d]+)[^<>]*>([\w\d\s\t!@#$%\^&\*\(\)\+=:;,\.\?\'\-\|\{\}\[\]’]+)</is';
            $pattern = '/<(\/?[\w\d]+)[^<>]*>([^<>\x{4e00}-\x{9fa5}]+)</ius';
            // preg_match_all($pattern, $this->xml, $matches);
            // halt($matches);
            preg_replace_callback($pattern, function ($item) {
                // 跳过script,img,input标签
                if (trim($item[2]) && !in_array($item[1], ['script', 'img', 'input'])) {
                    // 过滤所有标签,获得纯文字内容并翻译
                    $txt = strip_tags($item[2]);
                    $trans = str_replace('&amp;', '&', trim($txt));
                    $trans = $this->trans($trans, 'en', 'zh');
                    // var_dump($item, $txt, $trans);

                    // 替换原文
                    $this->xml = str_replace($item[0], str_replace($txt, $trans, $item[0]), $this->xml);
                }
            }, $node);
        }



        // halt($matches);

        // 翻译未翻译标签内容
        // $pattern = '/<(\/?[\w\d]+)[^<>\{\}\[\]]*>([^<>\x{4e00}-\x{9fa5}]+)</ius';
        $pattern = '/<\/?([\w\d]+)[^<>]*>([\w\d\s\t!@#$%\^&\*\(\)\+=:;,\.\?\'\-\|\{\}\[\]’]+)</is';
        // preg_match_all($pattern, $this->xml, $matches);
        // var_dump($matches);die();
        preg_replace_callback($pattern, function ($item) {
            if (trim($item[2]) && !in_array($item[1], ['script', 'img'])) {
                $txt = strip_tags($item[2]);
                $trans = str_replace('&amp;', '&', trim($txt));
                $trans = $this->trans($trans, 'en', 'zh');
                // var_dump($item[0], $txt, $trans);
                $this->xml = str_replace($item[0], str_replace($txt, $trans, $item[0]), $this->xml);
            }
        }, $this->xml);

        return $this->xml;
    }

    private function trans(string $_txt, string $_from, string $_to): string
    {
        $cache_key = md5($_txt . $_from . $_to);
        if (!cache('?' . $cache_key) || !$translation = cache($cache_key)) {
            $_txt = trim($_txt);
            if (2000 <= mb_strlen($_txt, 'utf-8')) {
                $temp = explode("\n", $_txt);
                $result = '';
                $lang = '';
                foreach ($temp as $value) {
                    if (2000 > mb_strlen($lang, 'utf-8') + mb_strlen($value, 'utf-8')) {
                        $lang .= $value . "\n";
                    } else {
                        $result .= $this->baidu($lang, $_from, $_to) . "\n";
                        $lang = $value . "\n";
                    }
                }

                if ($lang) {
                    $result .= $this->baidu($lang, $_from, $_to);
                }

                $translation = rtrim($result);
            } else {
                $translation = $this->baidu($_txt, $_from, $_to);
            }

            cache($cache_key, $translation);
        }

        return $translation;
    }

    private function baidu(string $_txt, string $_from, string $_to): string
    {
        if (trim($_txt)) {

            require_once('baidu_transapi.php');
            $code = 54003;
            while (54003 === $code) {
                $result = translate($_txt, $_from, $_to);
                sleep(1);
                $code = isset($result['error_code']) ? intval($result['error_code']) : 0;
                if (54003 === $code) {
                    sleep(3);
                } elseif (0 !== $code) {
                    // trace(json_encode($result), 'alert');
                }
            }

            if (!isset($result['trans_result'])) {
                // trace(json_encode($result), 'alert');
            }

            $translation = [];
            if (isset($result['trans_result'])) {
                foreach ($result['trans_result'] as $key => $value) {
                    $translation[] = $value['dst'];
                }
            }
            return implode("\n", $translation);
        } else {
            return $_txt;
        }
    }
}
