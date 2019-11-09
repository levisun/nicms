<?php

declare(strict_types=1);

class Books
{
    private $api = 'https://api.zhuishushenqi.com';

    /**
     * 分类
     */
    public function category()
    {
        $result = $this->http($this->api . '/cats/lv2');
        return $result['ok'] ? [
            'male'    => $result['male'],
            'female'  => $result['female'],
            'picture' => $result['picture'],
            'press'   => $result['press'],
        ] : [];
    }

    /**
     * 分类下书籍
     */
    public function book()
    {
        $receive_data = [
            // 性别
            'gender' => app('request')->param('gender', 'male'),
            // 按照不同的类型获取分类下的书籍(hot, new, reputation, over)
            'type'   => app('request')->param('type', 'reputation'),
            // 父分类
            'major'  => app('request')->param('major', '玄幻'),
            // 子分类
            'minor'  => app('request')->param('minor', '东方玄幻'),
            // 起始位置
            'start'  => app('request')->param('start', 0),
            //每页数量
            'limit'  => app('request')->param('limit', 20),
        ];

        $result = $this->http($this->api . '/book/by-categories?' . http_build_query($receive_data));
        return $result['ok'] ? [
            'total' => $result['total'],
            'books' => $result['books'],
        ] : [];
    }

    /**
     * 目录
     */
    public function catalog()
    {
        $result = $this->http($this->api . '/atoc?view=summary&book=' . app('request')->param('id', '5c6babcdc7d89e30c28fc666'));
        $id = isset($result[1]['_id']) ? $result[1]['_id'] : 0;

        $result = $this->http($this->api . '/toc/' . $id . '?view=chapters');

        return $result ? [
            'id'       => $result['_id'],
            'name'     => $result['name'],
            'source'   => $result['source'],
            'book'     => $result['book'],
            'link'     => $result['link'],
            'host'     => $result['host'],
            'updated'  => date('Y-m-d H:i:s', strtotime($result['updated'])),
            'chapters' => array_map(function ($value) {
                $value['en_link'] = urlencode($value['link']);
                return $value;
            }, $result['chapters']),
        ] : [];
    }

    public function details()
    {
        $link = app('request')->param('link');
        $result = $this->http('http://chapter2.zhuishushenqi.com/chapter/' . urlencode($link));
        return $result;
        return $result['ok'] ? [
            'id'        => $result['chapter']['id'],
            'title'     => $result['chapter']['title'],
            'created'   => date('Y-m-d H:i:s', strtotime($result['chapter']['created'])),
            'updated'   => date('Y-m-d H:i:s', strtotime($result['chapter']['updated'])),
            'cpContent' => $result['chapter']['cpContent'],
            'link' => 'http://chapter2.zhuishushenqi.com/chapter/' . urlencode($link),
        ] : [];
        return $result;
    }

    public function search()
    {
        $receive_data = [
            'query' => app('request')->param('query', '重生'),
            // 起始位置
            'start' => app('request')->param('start', 0),
            //每页数量
            'limit' => app('request')->param('limit', 20),
        ];

        $result = $this->http($this->api . '/book/fuzzy-search?query=' . http_build_query($receive_data));
        return $result;
    }

    private function http(string $_url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_URL, $_url);


        $rsp = curl_exec($ch);
        if ($rsp !== false) {
            curl_close($ch);
            return json_decode($rsp, true);
        } else {
            $error = curl_errno($ch);
            curl_close($ch);
            throw new Exception("curl出错，错误码:$error");
        }
    }
}
