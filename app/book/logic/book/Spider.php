<?php

declare(strict_types=1);

namespace app\book\logic\book;

use app\common\controller\BaseLogic;
use app\common\library\Filter;
use app\common\library\Spider as LibSpider;
use app\common\model\Book as ModelBook;
use app\common\model\BookAuthor as ModelBookAuthor;

class Spider extends BaseLogic
{
    private $bookURI = 'https://www.jx.la';


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

        $has = ModelBook::where([
            ['origin', '=', $_uri]
        ])->value('id');
        if (!$has) {
            $book_author = new ModelBookAuthor;
            $author_id = $book_author->where([
                ['author', '=', $author]
            ])->value('id');
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
        if ($book_id = $this->request->param('book_id/d', 0, 'abs')) {
            $origin = ModelBook::where([
                ['id', '=', $book_id]
            ])->value('origin');

            $spider = new LibSpider;
            $spider->request('GET', $origin);
            $links = $spider->fetch('dd');
            foreach ($links as $key => $value) {
                if ($key < 12) {
                    continue;
                }
                $value = htmlspecialchars_decode($value, ENT_QUOTES);
                if (preg_match('/href="(.*?)"/si', $value, $matches)) {
                    $spider->request('GET', $this->bookURI . $matches[1]);
                    $title = $spider->fetch('div.bookname h1');
                    $title = Filter::safe($title[0]);

                    $content = $spider->fetch('div#content');
                    $content = htmlspecialchars_decode($content[0], ENT_QUOTES);
                    // $content = explode('<br>', $content);
                    // $content = array_map(function($value){
                    //     // $value = mb_substr($value, 4);
                    //     $value = htmlspecialchars_decode($value, ENT_QUOTES);
                    //     $value = strip_tags($value);
                    //     return trim($value);
                    // }, $content);
                    // $content = array_filter($content);
                    // $content = '<p>' . implode('</p><p>', $content) . '</p>';
                    var_dump($content);
                    die();
                }
                print_r($matches);
                die();
            }
        }

        return [];
    }
}
