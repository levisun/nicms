<?php

declare(strict_types=1);

namespace app\book\logic\book;

use app\common\controller\BaseLogic;
use app\common\library\Filter;
use app\common\library\tools\Spider as LibSpider;
use app\common\model\Book as ModelBook;
use app\common\model\BookArticle as ModelBookArticle;
use app\common\model\BookAuthor as ModelBookAuthor;

class Spider extends BaseLogic
{
    // private $bookURI = 'https://www.jx.la';
    // private $bookURI = 'https://www.biquge.com.cn';
    private $bookURI = 'https://www.sobiquge.com';

    public function __destruct()
    {
    }

    public function book(string $_uri): int
    {
        usleep(mt_rand(5, 15) * 100000);

        $book_id = 0;
        only_execute('spider.lock', false, function () use ($_uri, &$book_id) {
            $book_id = ModelBook::where('origin', '=', $this->bookURI . $_uri)->value('id');

            if (!$book_id) {
                $spider = new LibSpider;
                $spider->agent = 'Mozilla/5.0 (Windows NT 6.3; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/86.0.4240.198 Safari/537.36 Edg/86.0.622.69';
                $author = $spider->request('GET', $this->bookURI . $_uri)->select('div#info>p');
                $author = substr($author[0], strpos($author[0], ':') + 1);
                $author = Filter::strict($author);

                $book_author = new ModelBookAuthor;
                $author_id = $book_author->where('author', '=', $author)->value('id');
                if (!$author_id) {
                    $book_author->save([
                        'author' => $author
                    ]);
                    $author_id = (int) $book_author->id;
                }

                $title = $spider->select('div#info>h1');
                $title = Filter::strict($title[0]);

                $keywords = $title . ',' . $author;
                $description = $title . '最新章节由网友提供，《' . $title . '》情节跌宕起伏、扣人心弦，是一本情节与文笔俱佳的小说，免费提供' . $title . '最新清爽干净的文字章节在线阅读。';

                $book = new ModelBook;

                $book->save([
                    'title'       => $title,
                    'keywords'    => $keywords,
                    'description' => $description,
                    'origin'      => $this->bookURI . $_uri,
                    'author_id'   => $author_id,
                    'is_pass'     => 1,
                ]);

                $book_id = (int) $book->id;
            }
        });

        return $book_id;
    }

    public function article(int $_book_id = 0): array
    {
        $result = [];
        only_execute('spider.lock', false, function () use (&$_book_id, &$result) {
            $_book_id = $_book_id ?: $this->request->param('book_id/d', 0, '\app\common\library\Base64::url62decode');

            if ($origin = ModelBook::where('id', '=', $_book_id)->cache(true)->value('origin')) {
                $spider = new LibSpider;
                $spider->agent = 'Mozilla/5.0 (Windows NT 6.3; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/86.0.4240.198 Safari/537.36 Edg/86.0.622.69';

                @set_time_limit(0);
                @ini_set('max_execution_time', '0');
                ignore_user_abort(true);
                $list = $spider->request('GET', $origin)->select('dd>a', ['href']);

                $total = ModelBookArticle::where('book_id', '=', $_book_id)
                    ->where('delete_time', '=', '0')
                    ->count();
                $title = ModelBookArticle::where('book_id', '=', $_book_id)
                    ->where('delete_time', '=', '0')
                    ->where('title', 'not like', '%请假条%')
                    ->order('id DESC')
                    ->value('title');

                $list = 1 < $total ? array_slice($list, $total - 10) : $list;
                // halt($list, $total);
                foreach ($list as $key => $value) {
                    $needle = Filter::strict($value['html']);
                    if ($title && false !== mb_strpos($title, $needle, 0, 'utf-8')) {
                        $total = $key;
                        break;
                    }
                }

                $list = array_slice($list, $total, mt_rand(2, 3));
                // halt($list);
                foreach ($list as $key => $value) {
                    $needle = Filter::strict($value['html']);
                    if ($title && false !== mb_strpos($title, $needle, 0, 'utf-8')) {
                        continue;
                    }

                    $url = $this->bookURI . '/' . Filter::strict($value['href']);

                    if (!$title = $spider->request('GET', $url)->select('div.bookname>h1')) {
                        continue;
                    }
                    $title = Filter::strict($title[0]);

                    if (!$content = $spider->select('div#content')) {
                        continue;
                    }
                    $content = Filter::htmlDecode($content[0]);
                    $content = strip_tags($content, '<p><br>');
                    $content = str_replace(['<p>', '</p>'], '<br>', $content);
                    $content = explode('<br>', $content);
                    $content = array_map(function ($value) {
                        return Filter::strict($value);
                    }, $content);
                    $content = array_filter($content);
                    $content = array_unique($content);
                    $content = '<p>' . implode('</p><p>', $content) . '</p>';

                    if (strip_tags($content)) {
                        $total = ModelBookArticle::where('book_id', '=', $_book_id)->count();
                        if (!ModelBookArticle::where('title', '=', $title)->where('book_id', '=', $_book_id)->value('id')) {
                            ModelBookArticle::create([
                                'book_id'    => $_book_id,
                                'title'      => $title,
                                'content'    => Filter::htmlEncode($content),
                                'is_pass'    => 1,
                                'sort_order' => $total + 1,
                                'show_time'  => time(),
                            ]);
                            $result[] = $title;
                            sleep(mt_rand(60, 100));
                        }
                    }
                }

                ignore_user_abort(false);
            }
        });

        return [
            'debug' => false,
            'cache' => false,
            'msg'   => 'spider book',
            'data'  => $result
        ];
    }
}
