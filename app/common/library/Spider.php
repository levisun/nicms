<?php

declare(strict_types=1);

namespace app\common\library;

use think\facade\Cache;
use think\facade\Request;
use app\common\library\DataFilter;
use GuzzleHttp\Client;

class Spider
{
    private $baseURI = '';

    public function __construct(string $_base_uri)
    {
        $this->baseURI = $_base_uri;
    }


    public function fetch(string $_uri, string $_preg = '', bool $_filter = true)
    {
        $html = $this->request($_uri);

        if ($_preg && $_preg = $this->filter($_preg)) {
            // var_dump($_preg);die();
            if (preg_match_all($_preg, $html, $matches)) {
                foreach ($matches as $key => $item) {
                    if (true === $_filter) {
                        $matches[$key] = array_map(function ($value) {
                            return DataFilter::decode(DataFilter::encode($value));
                        }, $item);
                    }
                }

                return $matches;
            }
        }

        return $html;
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
            $attr = 'id=["\']+.*?' . $attr . '.*?["\']+';
        } elseif (strpos($_pattern, '.')) {
            list($element, $attr) = explode('.', $_pattern, 2);
            $attr = 'class=[\w=\-"\'{}&;:, ]{1,}' . $attr;
        } elseif ($_pattern === 'a') {
            $element = 'a';
            $attr = 'href=["\']+(.*?)["\']+';
        } else {
            $element = $_pattern;
            $attr = '';
        }

        $preg = '/<' . $element . '[\w=\-"\'{}&;:, ]+' . $attr . '[\w=\-"\'{}&;:, ]+>(.*?)<\/' . $element . '>/si';

        if ($_pattern === 'img') {
            $element = 'img';
            $attr = 'src=["\']+(.*?)["\']+';
            $preg = '/<' . $element . '.*?' . $attr . '.*?>/si';
        }

        return $preg;
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
        $key = md5($this->baseURI . $_uri . $_method);

        if (!Cache::has($key) || !$result = Cache::get($key)) {
            $this->client = new Client([
                'base_uri' => $this->baseURI,
            ]);
            $response = $this->client->request($_method, $_uri, [
                'headers' => [
                    'User-Agent' => Request::server('HTTP_USER_AGENT'),
                    'Referer' => $this->baseURI,
                    'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                ]
            ]);
            $result = '';
            if (200 == $response->getStatusCode()) {
                $body = $response->getBody();
                $result = $body->getContents();
                Cache::set($key, $result);
            }
        }

        return $result;
    }
}
