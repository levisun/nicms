<?php

declare(strict_types=1);

namespace gather;

use GuzzleHttp\Client;

class Book
{
    private $baseURI = '';

    public function __construct(string $_base_uri)
    {
        $this->baseURI = $_base_uri;
        @ini_set('memory_limit', '64M');
        set_time_limit(1440);
    }

    public function getCat(string $_uri): array
    {
        $data = [];
        if ($content = $this->getResponse($_uri)) {
            preg_match_all('/<a style="" href="\/book\/(.*?)">(.*?)<\/a>/si', $content, $matches);
            if (!empty($matches[1])) {
                foreach ($matches[1] as $key => $uri) {
                    $data[$key] = [
                        'title' => $matches[2][$key],
                        'uri' => $uri
                    ];
                }
            }
        }

        return $data;
    }

    public function getContent(string &$_uri): string
    {
        if ($content = $this->getResponse($_uri)) {
            preg_match('/<div id="content">(.*?)<\/div>/si', $content, $matches);
            if (!empty($matches[1])) {
                $content = trim($matches[1]);
                $content = str_replace(['　', '</br>'], '', $content);
                $pattern = [
                    '/<script>(.*?)<\/script>/si',
                    '/([ \s]+)/si',
                ];
                $content = preg_replace($pattern, '', $content);
                $content = explode('<br/>', $content);
                $content = array_map(function($value){
                    $value = trim($value);
                    $value = htmlspecialchars_decode($value, ENT_QUOTES);
                    $value = strip_tags($value);
                    return $value;
                }, $content);
                $content = array_filter($content);
                $content = implode('<br/>', $content);
            }
        }

        return $content;
    }

    private function getResponse(string &$_uri, string &$_method = 'GET'): string
    {
        usleep(mt_rand(500, 1000));
        $this->client = new Client([
            'base_uri' => $this->baseURI,
        ]);
        $response = $this->client->request($_method, $_uri);
        $content = '';
        if (200 == $response->getStatusCode()) {
            $body = $response->getBody();
            $content = $body->getContents();
        }
        return $content;
    }
}
