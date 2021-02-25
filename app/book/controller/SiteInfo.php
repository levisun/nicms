<?php

/**
 *
 * 网站信息
 *
 * @package   NICMS
 * @category  app\book\controller;
 * @author    失眠小枕头 [312630173@qq.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace app\book\controller;;

use app\common\controller\BaseSiteInfo;
use think\facade\Cache;
use think\facade\Request;
use app\common\model\Config as ModelConfig;
use app\common\model\Book as ModelBook;
use app\common\model\BookArticle as ModelBookArticle;

class SiteInfo extends BaseSiteInfo
{
    protected $appName = 'book';

    public function query(): array
    {
        $cache_key = $this->appName . $this->langSet;
        $cache_key .= Request::param('id', 0) . Request::param('book_id', 0);
        if (!Cache::has($cache_key) || !$result = Cache::get($cache_key)) {
            $result = [
                'title'       => $this->title(),
                'keywords'    => $this->keywords(),
                'description' => $this->description(),
            ];

            Cache::tag('request')->set($cache_key, $result);
        }

        return array_merge($this->siteConfig, $result);
    }

    /**
     * 网站描述
     * @access protected
     * @return string
     */
    protected function description(): string
    {
        // 默认
        $description = ModelConfig::where('name', '=', $this->appName . '_description')
            ->where('lang', '=', $this->langSet)
            ->value('value', '');

        if ($book_id = Request::param('book_id', 0, '\app\common\library\Base64::url62decode')) {
            $result = ModelBook::where('id', '=', $book_id)->value('description', '');
            $description = $result ?: $description;
        }

        return strip_tags(htmlspecialchars_decode($description));
    }

    /**
     * 网站关键词
     * @access protected
     * @return string
     */
    protected function keywords(): string
    {
        // 默认
        $keywords = ModelConfig::where('name', '=', $this->appName . '_keywords')
            ->where('lang', '=', $this->langSet)
            ->value('value', '');

        if ($book_id = Request::param('book_id', 0, '\app\common\library\Base64::url62decode')) {
            $result = ModelBook::where('id', '=', $book_id)->value('keywords', '');
            $keywords = $result ?: $keywords;
        }

        return strip_tags(htmlspecialchars_decode($keywords));
    }

    /**
     * 网站标题
     * @access protected
     * @return string
     */
    protected function title(): string
    {
        $title = '';

        // 文章名
        if ($id = Request::param('id', 0, '\app\common\library\Base64::url62decode')) {
            $article = ModelBookArticle::where('id', '=', $id)->value('title', '');
            $title .= $article ? $article . '-' : '';
        }

        // 栏目名
        if ($book_id = Request::param('book_id', 0, '\app\common\library\Base64::url62decode')) {
            $book_name = ModelBook::where('id', '=', $book_id)->value('title', '');
            $title .= $book_name ? $book_name . '-' : '';
        }

        // 默认
        $title .= $this->siteName();

        return strip_tags(htmlspecialchars_decode($title));
    }
}
