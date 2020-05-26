<?php

use GuzzleHttp\Client;

class Spider
{
    private $baseURI = '';

    /**
     * 获得响应数据
     * @access private
     * @param  string  $_uri      请求URI
     * @param  string  $_method   请求类型
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
