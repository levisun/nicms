<?php

class Translation
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
    }

    public function find()
    {
        $nodes = $this->xpath->query('//div//*');
        foreach ($nodes as $node) {
            $this->nodes($node);
        }
        return $this->dom->saveHTML();
    }

    public function nodes(&$_node)
    {
        if ('#text' == $_node->nodeName && trim($_node->textContent) && 1 < mb_strlen(trim($_node->textContent), 'utf-8')) {
            $_node->textContent = '中文';
        } elseif ('#text' != $_node->nodeName && null !== $_node->childNodes) {
            foreach ($_node->childNodes as $child) {
                $this->nodes($child);
            }
        }
    }

    private function nodesV1(&$_node)
    {
        if (null !== $_node->childNodes) {
            foreach ($_node->childNodes as $child) {
                $this->nodes($child);
            }
        } elseif ('#text' == $_node->nodeName && trim($_node->textContent) && 1 < mb_strlen(trim($_node->textContent), 'utf-8')) {
            if (null !== $_node->nextSibling) {
                halt($_node, $_node->nextSibling->textContent);
            }

            preg_match('/[\w\d ]+/i', $_node->textContent, $matches);
            if (count($matches)) {
                $_node->textContent = $_node->textContent;
                // $_node->textContent = $this->trans($_node->textContent, 'en', 'zh');
            }
        }
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
