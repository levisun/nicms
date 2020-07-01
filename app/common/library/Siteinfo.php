<?php

/**
 *
 * 网站信息
 *
 * @package   NICMS
 * @category  app\common\library
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace app\common\library;

use think\facade\Cache;
use think\facade\Lang;
use think\facade\Request;
use app\common\library\Filter;
use app\common\model\Config as ModelConfig;
use app\common\model\Article as ModelArticle;
use app\common\model\Category as ModelCategory;

class Siteinfo
{
    private static $appName = '';
    private static $langSet = '';

    public static function query(string $_app_name): array
    {
        self::$appName = $_app_name;
        self::$langSet = Lang::getLangSet();

        $cache_key = md5(self::$appName . self::$langSet);
        if (!Cache::has($cache_key) || !$common = Cache::get($cache_key)) {
            $common = [
                'theme'     => self::theme(),
                'script'    => self::script(),
                'footer'    => self::footer(),
                'copyright' => self::copyright(),
                'name'      => self::siteName(),
            ];

            Cache::tag('system')->set($cache_key, $common);
        }

        $cache_key .= Request::param('id/f', null) . Request::param('cid/f', null);
        $cache_key = md5($cache_key);
        if (!Cache::has($cache_key) || !$result = Cache::get($cache_key)) {
            $result = [
                'title'       => self::title(),
                'keywords'    => self::keywords(),
                'description' => self::description(),
            ];

            Cache::tag('system')->set($cache_key, $result);
        }

        return array_merge($common, $result);
    }

    /**
     * 网站描述
     * @access private
     * @static
     * @return string
     */
    private static function description(): string
    {
        // 默认
        $description = ModelConfig::where([
            ['name', '=', self::$appName . '_description'],
            ['lang', '=', self::$langSet]
        ])->value('value', '');

        if (self::$appName == 'cms') {
            // 文章描述
            if ($id = Request::param('id/d', null)) {
                $result = ModelArticle::where([
                    ['id', '=', $id]
                ])->value('description', '');
                $description = $result ?: $description;
            }
            // 栏目描述
            elseif ($cid = Request::param('cid/d', null)) {
                $result = ModelCategory::where([
                    ['id', '=', $cid]
                ])->value('description', '');
                $description = $result ?: $description;
            }
        }

        return strip_tags(htmlspecialchars_decode($description));
    }

    /**
     * 网站关键词
     * @access private
     * @static
     * @return string
     */
    private static function keywords(): string
    {
        // 默认
        $keywords = ModelConfig::where([
            ['name', '=', self::$appName . '_keywords'],
            ['lang', '=', self::$langSet]
        ])->value('value', '');

        if (self::$appName == 'cms') {
            // 文章关键词
            if ($id = Request::param('id/d', null)) {
                $result = ModelArticle::where([
                    ['id', '=', $id]
                ])->value('keywords', '');
                $keywords = $result ?: $keywords;
            }
            // 栏目关键词
            elseif ($cid = Request::param('cid/d', null)) {
                $result = ModelCategory::where([
                    ['id', '=', $cid]
                ])->value('keywords', '');
                $keywords = $result ?: $keywords;
            }
        }

        return strip_tags(htmlspecialchars_decode($keywords));
    }

    /**
     * 网站标题
     * @access private
     * @static
     * @return string
     */
    private static function title(): string
    {
        $title = '';

        if (self::$appName == 'cms') {
            // 文章名
            if ($id = Request::param('id/d', null)) {
                $article = ModelArticle::where([
                    ['id', '=', $id]
                ])->value('title', '');
                $title .= $article ? $article . '_' : '';
            }

            // 栏目名
            if ($cid = Request::param('cid/d', null)) {
                $category = ModelCategory::where([
                    ['id', '=', $cid]
                ])->value('name', '');
                $title .= $category ? $category . '_' : '';
            }
        }


        // 默认
        $title .= self::siteName();

        return strip_tags(htmlspecialchars_decode($title));
    }

    /**
     * 网站名称
     * @access private
     * @static
     * @return string
     */
    private static function siteName(): string
    {
        $site = ModelConfig::where([
            ['name', '=', self::$appName . '_sitename'],
            ['lang', '=', self::$langSet]
        ])->value('value', 'NICMS');

        return strip_tags(htmlspecialchars_decode($site));
    }

    /**
     * 网站版权
     * @access private
     * @static
     * @return string
     */
    private static function copyright(): string
    {
        $copyright = ModelConfig::where([
            ['name', '=', self::$appName . '_copyright'],
            ['lang', '=', self::$langSet]
        ])->value('value', '');

        $beian = ModelConfig::where([
            ['name', '=', self::$appName . '_beian'],
            ['lang', '=', self::$langSet]
        ])->value('value', '');

        return
            Filter::decode($copyright) . '&nbsp;' .
            Filter::decode($beian) .
            '&nbsp;<a href="/sitemap.xml" target="_blank">sitemap</a>&nbsp;' .
            '&nbsp;Powered&nbsp;by&nbsp;<a href="https://github.com/levisun/nicms" rel="nofollow" target="_blank">NICMS</a>';
    }

    /**
     * 网站底部
     * @access private
     * @static
     * @return string
     */
    private static function footer(): string
    {
        $footer = ModelConfig::where([
            ['name', '=', self::$appName . '_footer'],
            ['lang', '=', self::$langSet]
        ])->value('value', '');

        return htmlspecialchars_decode($footer);
    }

    /**
     * JS脚本
     * @access private
     * @static
     * @return string
     */
    private static function script(): string
    {
        $result = ModelConfig::where([
            ['name', '=', self::$appName . '_script'],
            ['lang', '=', self::$langSet]
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
     * @static
     * @return string
     */
    private static function theme(): string
    {
        if ('admin' === self::$appName) {
            $theme = env('admin.theme', 'default');
        } else {
            $theme = ModelConfig::where([
                ['name', '=', self::$appName . '_theme'],
                ['lang', '=', self::$langSet]
            ])->value('value', 'default');
        }

        return strip_tags(htmlspecialchars_decode($theme));
    }
}
