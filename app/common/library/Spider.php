<?php

declare(strict_types=1);

namespace app\common\library;

use app\common\library\DataFilter;
use GuzzleHttp\Client;

class Spider
{
    private $baseURI = '';

    private $preg = '';

    public function __construct(string $_base_uri)
    {
        $this->baseURI = $_base_uri;
    }


    public function fetch(string $_uri, bool $_filter = true)
    {
        $html = $this->request($_uri);

        if ($this->preg && preg_match_all($this->preg, $html, $matches)) {
            foreach ($matches as $key => $item) {
                $matches[$key] = array_map(function ($value) use ($_filter) {
                    return $_filter ? DataFilter::decode(DataFilter::encode($value)) : $value;
                }, $item);
            }

            return $matches;
        } else {
            return $html;
        }
    }

    /**
     * 设定过滤规则
     * 过滤出指定数据
     * @access private
     * @param  string  $_pattern
     * @return
     */
    public function filter(string $_pattern)
    {
        $_pattern = str_replace(['"', '\''], '', $_pattern);
        $_pattern = str_replace('-', '\-', $_pattern);
        if (strpos($_pattern, '#')) {
            list($element, $attr) = explode('#', $_pattern, 2);
            $attr = 'id=["\']+' . $attr . '["\']+';
        } elseif (strpos($_pattern, '.')) {
            list($element, $attr) = explode('.', $_pattern, 2);
            $attr = 'class=["\']+' . $attr . '["\']+';
        } elseif ($_pattern === 'a') {
            $element = 'a';
            $attr = 'href=["\']+(.*?)["\']+';
        } else {
            $element = $_pattern;
            $attr = '';
        }

        $this->preg = '/<' . $element . '.*?' . $attr . '.*?>(.*?)<\/' . $element . '.*?>/si';

        if ($_pattern === 'img') {
            $element = 'img';
            $attr = 'src=["\']+(.*?)["\']+';
            $this->preg = '/<' . $element . '.*?' . $attr . '.*?>/si';
        }

        return $this;
    }

    /**
     * 获得响应数据
     * @access private
     * @param  string  $_uri      请求URI
     * @param  string  $_method   请求类型
     * @return string
     */
    private function request(string &$_uri, string &$_method = 'GET'): string
    {
        $this->client = new Client([
            'base_uri' => $this->baseURI,
        ]);
        $response = $this->client->request($_method, $_uri, [
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 6.3; Win64; x64; rv:71.0) Gecko/20100101 Firefox/71.0',
                'Referer' => $this->baseURI,
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            ]
        ]);
        $content = '';
        if (200 == $response->getStatusCode()) {
            $body = $response->getBody();
            $content = $body->getContents();
        }
        return $content;
    }
}
