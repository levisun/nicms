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
        $this->dom->loadHTML('<meta charset="utf-8">' . $_xml);
        $this->xml = $this->dom->saveHTML();
        libxml_clear_errors();
        $this->xpath = new \DOMXPath($this->dom);

        @set_time_limit(0);
        @ini_set('max_execution_time', '0');
        @ini_set('memory_limit', '128M');
    }

    public function V20210717()
    {
        $savePath = runtime_path('testing');
        is_dir($savePath) or mkdir($savePath, 0755, true);
        $name = md5($this->xml);

        // file_put_contents($savePath . $name . '.html', $this->xml);

        $this->xml = preg_replace('/<!\-{2}[^<>]+\-{2}>/i', '', $this->xml);

        $nodes = $this->xpath->query('//li');
        foreach ($nodes as $node) {
            $node = $this->dom->saveHTML($node);
            $txt = strip_tags($node);
            $trans = str_replace('&amp;', '&', trim($txt));
            $trans = $this->trans($trans, 'en', 'zh');
            // var_dump($node, $txt, $trans);
            $this->xml = str_replace($node, str_replace($txt, $trans, strip_tags($node, '<li>')), $this->xml);
        }

        $nodes = $this->xpath->query('//p');
        foreach ($nodes as $node) {
            $node = $this->dom->saveHTML($node);
            $str_node = preg_replace(['/<\/?a[^<>]*>/i', '/<\/?(span|i|b|u|strong)>/i'], '', $node);
            $this->xml = str_replace($node, $str_node, $this->xml);
            preg_replace_callback('/<([\w\d]+)([^<>]*)>[^<>]+<\/[\w\d]+>/is', function ($item) {
                $txt = strip_tags($item[0]);
                $trans = str_replace('&amp;', '&', trim($txt));
                $trans = $this->trans(preg_replace('/[\r\n]+/i', ' ', $trans), 'en', 'zh');
                // var_dump($item[0], $txt, $trans);
                $this->xml = str_replace($item[0], str_replace($txt, $trans, $item[0]), $this->xml);
            }, $str_node);
        }

        for ($i = 1; $i <= 6; $i++) {
            $nodes = $this->xpath->query('//h' . $i);
            foreach ($nodes as $node) {
                $node = $this->dom->saveHTML($node);
                $txt = strip_tags($node);
                $trans = str_replace('&amp;', '&', trim($txt));
                $trans = $this->trans($trans, 'en', 'zh');
                // var_dump($node, $txt, $trans);
                $this->xml = str_replace($node, str_replace($txt, $trans, strip_tags($node, '<li>')), $this->xml);
            }
        }

        $nodes = $this->xpath->query('//div');
        foreach ($nodes as $node) {
            $node = $this->dom->saveHTML($node);
            preg_replace_callback('/<(\/?[\w\d]+)[^<>]*>([\w\d\s\t!@#$%\^&\*\(\)\+=:;,\.\?\'\-’]+)</is', function ($item) {
                if (trim($item[2]) && !in_array($item[1], ['script', 'img', 'input'])) {
                    $txt = strip_tags($item[2]);
                    $trans = str_replace('&amp;', '&', trim($txt));
                    $trans = $this->trans($trans, 'en', 'zh');
                    // var_dump($item[0], $txt, $trans);
                    $this->xml = str_replace($item[0], str_replace($txt, $trans, $item[0]), $this->xml);
                }
            }, $node);
        }

        $nodes = $this->xpath->query('//input');
        foreach ($nodes as $node) {
            $node = $this->dom->saveHTML($node);
            preg_replace_callback('/placeholder=["\']+([^"\']+)["\']+/is', function ($item) {
                $txt = strip_tags($item[1]);
                $trans = str_replace('&amp;', '&', trim($txt));
                $trans = $this->trans($trans, 'en', 'zh');
                // var_dump($item[0], $txt, $trans);
                $this->xml = str_replace($item[0], str_replace($txt, $trans, $item[0]), $this->xml);
            }, $node);
        }

        // preg_match_all('/<\/?([\w\d]+)[^<>]*>([\w\d\s\t!@#$%&\^\*\(\)\+=:;,\.\?\'\-’]+)</is', $this->xml, $matches);
        // halt($matches);

        preg_replace_callback('/<\/?([\w\d]+)[^<>]*>([\w\d\s\t!@#$%&\^\*\(\)\+=:;,\.\?\'\-’]+)</is', function ($item) {
            if (trim($item[2]) && !in_array($item[1], ['script', 'img', 'input', 'li', 'p', 'div'])) {
                $txt = strip_tags($item[2]);
                $trans = str_replace('&amp;', '&', trim($txt));
                $trans = $this->trans($trans, 'en', 'zh');
                // var_dump($item[0], $txt, $trans);
                $this->xml = str_replace($item[0], str_replace($txt, $trans, $item[0]), $this->xml);
            }
        }, $this->xml);

        // file_put_contents($savePath . $name . '_zh.html', $this->xml);

        echo $this->xml;
        die();
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
            foreach ($result['trans_result'] as $key => $value) {
                $translation[] = $value['dst'];
            }
            return implode("\n", $translation);
        } else {
            return $_txt;
        }
    }
}
