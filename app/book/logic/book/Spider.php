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
    private $bookURI = 'https://www.jx.la';

    public function __destruct()
    {
        ignore_user_abort(false);
    }

    public function JXBookAdded(string $_uri)
    {
        $spider = new LibSpider;
        $spider->request('GET', $this->bookURI . $_uri);
        $title = $spider->fetch('div#info h1');
        $title = Filter::safe($title[0]);

        $author = $spider->fetch('div#info p');
        $author = substr($author[0], strpos($author[0], ':') + 1);
        $author = Filter::safe($author);

        $keywords = $title . ',' . $author;
        $description = $title . '最新章节由网友提供，《' . $title . '》情节跌宕起伏、扣人心弦，是一本情节与文笔俱佳的小说，免费提供' . $title . '最新清爽干净的文字章节在线阅读。';

        $has = ModelBook::where('origin', '=', $_uri)->value('id');
        if (!$has) {
            $book_author = new ModelBookAuthor;
            $author_id = $book_author->where('author', '=', $author)->value('id');
            if (!$author_id) {
                $book_author->save([
                    'author' => $author
                ]);
                $author_id = $book_author->id;
            }

            ModelBook::create([
                'title'       => $title,
                'keywords'    => $keywords,
                'description' => $description,
                'origin'      => $_uri,
                'author_id'   => $author_id,
                'is_pass'     => 1,
            ]);
        }
    }

    public function jxbookarticle()
    {
        if ($book_id = $this->request->param('book_id/d', 0, '\app\common\library\Base64::url62decode')) {
            $origin = ModelBook::where('id', '=', $book_id)->value('origin');

            $spider = new LibSpider;

            @set_time_limit(0);

            $spider->request('GET', $origin);
            $links = $spider->fetch('dd');

            $count = ModelBookArticle::where('book_id', '=', $book_id)->count();
            if (count($links) <= $count) {
                return [];
            }

            foreach ($links as $key => $value) {
                if ($key < 12) {
                    continue;
                }

                $has = ModelBookArticle::where([
                    ['book_id', '=', $book_id],
                    ['sort_order', '=', $key],
                ])->value('id');
                if ($has) {
                    continue;
                }

                $value = htmlspecialchars_decode($value, ENT_QUOTES);
                if (preg_match('/href="(.*?)"/si', $value, $matches)) {
                    usleep(rand(1000000, 1500000));
                    $spider->request('GET', $this->bookURI . $matches[1]);
                    $title = $spider->fetch('div.bookname h1');
                    $title = Filter::safe($title[0]);

                    $content = $spider->fetch('div#content');
                    $content = htmlspecialchars_decode($content[0], ENT_QUOTES);
                    $content = explode('<br>', $content);
                    $content = array_map(function ($value) {
                        $value = htmlspecialchars_decode($value, ENT_QUOTES);
                        $value = strip_tags($value);
                        return trim($value);
                    }, $content);
                    $content = array_filter($content);
                    $content = '<p>' . implode('</p><p>', $content) . '</p>';
                    $content = Filter::contentEncode($content);

                    $has = ModelBookArticle::where([
                        ['book_id', '=', $book_id],
                        ['title', '=', $title],
                    ])->value('id');
                    if (!$has) {
                        ModelBookArticle::create([
                            'book_id'    => $book_id,
                            'title'      => $title,
                            'content'    => $content,
                            'is_pass'    => 1,
                            'sort_order' => $key,
                            'show_time'  => time(),
                        ]);
                    }
                }
            }
        }

        return [];
    }
}
