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

use think\facade\Cache;
use think\facade\Lang;
use think\facade\Request;
use app\common\library\Filter;
use app\common\model\Config as ModelConfig;
use app\common\model\Book as ModelBook;
use app\common\model\BookArticle as ModelBookArticle;

class SiteInfo
{
    private $appName = 'book';
    private $langSet = '';

    public function query(): array
    {
        $this->langSet = Lang::getLangSet();

        $cache_key = $this->appName . $this->langSet;
        if (!Cache::has($cache_key) || !$common = Cache::get($cache_key)) {
            $common = [
                'theme'     => $this->theme(),
                'script'    => $this->script(),
                'footer'    => $this->footer(),
                'copyright' => $this->copyright(),
                'name'      => $this->siteName(),
            ];

            Cache::tag('system')->set($cache_key, $common);
        }

        $cache_key .= Request::param('id', 0) . Request::param('book_id', 0);
        if (!Cache::has($cache_key) || !$result = Cache::get($cache_key)) {
            $result = [
                'title'       => $this->title(),
                'keywords'    => $this->keywords(),
                'description' => $this->description(),
            ];

            // Cache::tag('system')->set($cache_key, $result);
        }

        return array_merge($common, $result);
    }

    /**
     * 网站描述
     * @access private
     * @return string
     */
    private function description(): string
    {
        // 默认
        $description = ModelConfig::where([
            ['name', '=', $this->appName . '_description'],
            ['lang', '=', $this->langSet]
        ])->value('value', '');

        if ($book_id = Request::param('book_id', 0, '\app\common\library\Base64::url62decode')) {
            $result = ModelBook::where('id', '=', $book_id)->value('description', '');
            $description = $result ?: $description;
        }

        return strip_tags(htmlspecialchars_decode($description));
    }

    /**
     * 网站关键词
     * @access private
     * @return string
     */
    private function keywords(): string
    {
        // 默认
        $keywords = ModelConfig::where([
            ['name', '=', $this->appName . '_keywords'],
            ['lang', '=', $this->langSet]
        ])->value('value', '');

        if ($book_id = Request::param('book_id', 0, '\app\common\library\Base64::url62decode')) {
            $result = ModelBook::where('id', '=', $book_id)->value('keywords', '');
            $keywords = $result ?: $keywords;
        }

        return strip_tags(htmlspecialchars_decode($keywords));
    }

    /**
     * 网站标题
     * @access private
     * @return string
     */
    private function title(): string
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

    /**
     * 网站名称
     * @access private
     * @return string
     */
    private function siteName(): string
    {
        $site = ModelConfig::where([
            ['name', '=', $this->appName . '_sitename'],
            ['lang', '=', $this->langSet]
        ])->value('value', '腐朽的书屋');

        return strip_tags(htmlspecialchars_decode($site));
    }

    /**
     * 网站版权
     * @access private
     * @return string
     */
    private function copyright(): string
    {
        $copyright = ModelConfig::where([
            ['name', '=', $this->appName . '_copyright'],
            ['lang', '=', $this->langSet]
        ])->value('value', '');

        $beian = ModelConfig::where([
            ['name', '=', $this->appName . '_beian'],
            ['lang', '=', $this->langSet]
        ])->value('value', '');

        return
            Filter::contentDecode($copyright) . '&nbsp;' .
            Filter::contentDecode($beian) . '&nbsp;' .
            '<a href="/sitemap.xml" target="_blank">sitemap</a>&nbsp;' .
            '&nbsp;Powered&nbsp;by&nbsp;<a href="https://github.com/levisun/nicms" rel="nofollow" target="_blank">NICMS</a>';
    }

    /**
     * 网站底部
     * @access private
     * @return string
     */
    private function footer(): string
    {
        $footer = ModelConfig::where([
            ['name', '=', $this->appName . '_footer'],
            ['lang', '=', $this->langSet]
        ])->value('value', '');

        return htmlspecialchars_decode($footer);
    }

    /**
     * JS脚本
     * @access private
     * @return string
     */
    private function script(): string
    {
        $result = ModelConfig::where([
            ['name', '=', $this->appName . '_script'],
            ['lang', '=', $this->langSet]
        ])->value('value', '');

        $result = preg_replace([
            '/(\s+\n|\r)/s',
            '/(\t|\0|\x0B)/s',
            '/( ){2,}/s',
        ], '', $result);

        return $result
            ? '<script type="text/javascript">' . htmlspecialchars_decode($result) . '</script>'
            : '';
    }

    /**
     * 主题
     * @access private
     * @return string
     */
    private function theme(): string
    {
        $theme = ModelConfig::where([
            ['name', '=', $this->appName . '_theme'],
            ['lang', '=', $this->langSet]
        ])->value('value', 'default');

        return strip_tags(htmlspecialchars_decode($theme));
    }
}
