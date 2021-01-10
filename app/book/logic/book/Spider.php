<?php

declare(strict_types=1);

namespace app\book\logic\book;

use app\common\controller\BaseLogic;
use app\common\library\Filter;
use app\common\library\tools\Spider as LibSpider;
use app\common\library\tools\Html;
use app\common\model\Book as ModelBook;
use app\common\model\BookArticle as ModelBookArticle;
use app\common\model\BookAuthor as ModelBookAuthor;

class Spider extends BaseLogic
{
    private $bookURI = 'https://www.jx.la';

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
                $author = $spider->request('GET', $this->bookURI . $_uri)->select('div.top>div>p');
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

                $title = $spider->select('div.top>h1');
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

    public function article(int $_book_id = 0, int $_page = 20): array
    {
        usleep(mt_rand(5, 15) * 100000);

        only_execute('spider.lock', false, function () use ($_book_id, $_page) {
            $_book_id = $_book_id ?: $this->request->param('book_id/d', 0, '\app\common\library\Base64::url62decode');
            $_page = $_page ?: $this->request->param('page/d', 20);

            if ($origin = ModelBook::where('id', '=', $_book_id)->cache(true)->value('origin')) {
                $spider = new LibSpider;
                $spider->agent = 'Mozilla/5.0 (Windows NT 6.3; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/86.0.4240.198 Safari/537.36 Edg/86.0.622.69';

                @set_time_limit(0);
                @ini_set('max_execution_time', '0');
                ignore_user_abort(true);

                $total = ModelBookArticle::where('book_id', '=', $_book_id)->count();
                $_page = $total ? ceil($total / $_page) : 1;
                $_page = abs($_page);

                $spider_total = 0;

                for ($i = 0; $i < 2; $i++) {
                    $links = $spider->request('GET', $origin . 'index_' . ($_page + $i) . '.html')->select('ul.section-list');
                    if (empty($links[1])) {
                        trace($origin . 'index_' . ($_page + $i) . '.html' . ' 采集错误', 'info');
                        break;
                    }

                    $links[1] = htmlspecialchars_decode($links[1], ENT_QUOTES);
                    if (false !== preg_match_all('/href="(.*?)"/si', $links[1], $matches) && !empty($matches[1])) {
                        foreach ($matches[1] as $key => $value) {
                            if (4 < $spider_total) {
                                break;
                            }

                            $title = $spider->request('GET', $this->bookURI . $value)->select('h1.title');
                            if (empty($title[0])) {
                                trace($this->bookURI . $value . ' 采集错误', 'info');
                                return;
                            }
                            $title = Filter::strict($title[0]);

                            $has = ModelBookArticle::where('book_id', '=', $_book_id)
                                ->where('title', '=', $title)
                                ->value('id');
                            if (!$has) {
                                $content = $spider->select('div#content');
                                $content = htmlspecialchars_decode($content[0], ENT_QUOTES);
                                $content = Filter::base($content);
                                $content = preg_replace([
                                    '/<div[^<>]*class="posterror"[^<>]*>.*?<\/div>/si',
                                    '/<div[^<>]*>/si',
                                    '/<\/div>/si',
                                ], '', $content);
                                $content = explode('<br>', $content);
                                $content = array_map(function ($value) {
                                    $value = htmlspecialchars_decode($value, ENT_QUOTES);
                                    $value = strip_tags($value);
                                    $value = str_replace(['&ensp;', '&emsp;', '&thinsp;', '&zwnj;', '&zwj;', '&nbsp;'], '', $value);
                                    return trim($value);
                                }, $content);
                                $content = array_filter($content);
                                $content = '<p>' . implode('</p><p>', $content) . '</p>';

                                $next = $spider->request('GET', $this->bookURI . substr($value, 0, -5) . '_2.html')->select('div#content');
                                if (empty($next[0])) {
                                    trace($this->bookURI . substr($value, 0, -5) . '_2.html 采集错误', 'info');
                                    return;
                                }
                                $next = htmlspecialchars_decode($next[0], ENT_QUOTES);
                                $next = Filter::base($next);
                                $next = preg_replace([
                                    '/<div[^<>]*class="posterror"[^<>]*>.*?<\/div>/si',
                                    '/<div[^<>]*>/si',
                                    '/<\/div>/si',
                                ], '', $next);
                                $next = explode('<br>', $next);
                                $next = array_map(function ($value) {
                                    $value = htmlspecialchars_decode($value, ENT_QUOTES);
                                    $value = strip_tags($value);
                                    $value = str_replace(['&ensp;', '&emsp;', '&thinsp;', '&zwnj;', '&zwj;', '&nbsp;'], '', $value);
                                    return trim($value);
                                }, $next);
                                $next = array_filter($next);
                                $next = '<p>' . implode('</p><p>', $next) . '</p>';
                                $content .= $next;

                                $content = Filter::contentEncode($content);
                                if (strip_tags($content)) {
                                    $spider_total++;
                                    $total = ModelBookArticle::where('book_id', '=', $_book_id)->count();
                                    ModelBookArticle::create([
                                        'book_id'    => $_book_id,
                                        'title'      => $title,
                                        'content'    => $content,
                                        'is_pass'    => 1,
                                        'sort_order' => $total + 1,
                                        'show_time'  => time(),
                                    ]);
                                }
                            }
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
            'data'  => []
        ];
    }
}
