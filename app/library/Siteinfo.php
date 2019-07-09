<?php
/**
 *
 * 网站信息
 *
 * @package   NICMS
 * @category  app\library
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */
declare (strict_types = 1);

namespace app\library;

use think\facade\Cache;
use think\facade\Lang;
use think\facade\Request;
use app\model\Config as ModelConfig;
use app\model\Article as ModelArticle;
use app\model\Category as ModelCategory;

class Siteinfo
{

    public static function query()
    {
        $cache_key = md5(Request::controller(true) . Lang::getLangSet());
        if (!Cache::has($cache_key) || !$common = Cache::get($cache_key)) {
            $common = [
                'theme'     => self::theme(),
                'script'    => self::script(),
                'footer'    => self::footer(),
                'copyright' => self::copyright(),
                'name'      => self::name(),
            ];
            Cache::tag('SYSTEM')->set($cache_key, $common);
        }

        $result = [
            'title'       => self::title(),
            'keywords'    => self::keywords(),
            'description' => self::description(),
        ];

        return array_merge($common, $result);
    }

    /**
     * 网站描述
     * @access private
     * @static
     * @param
     * @return string
     */
    private static function description(): string
    {
        // 文章描述
        if ($id = Request::param('id/f', null)) {
            $result = (new ModelArticle)
                ->where([
                    ['id', '=', $id]
                ])
                ->value('description', '');
        }
        // 栏目描述
        elseif ($cid = Request::param('cid/f', null)) {
            $result = (new ModelCategory)
                ->where([
                    ['id', '=', $cid]
                ])
                ->value('description', '');
        } else {
            $result = (new ModelConfig)
                ->where([
                    ['name', '=', Request::controller(true) . '_description'],
                    ['lang', '=', Lang::getLangSet()]
                ])
                ->value('value', '');
        }

        return strip_tags(htmlspecialchars_decode($result));
    }

    /**
     * 网站关键词
     * @access private
     * @static
     * @param
     * @return string
     */
    private static function keywords(): string
    {
        // 文章关键词
        if ($id = Request::param('id/f', null)) {
            $result = (new ModelArticle)
                ->where([
                    ['id', '=', $id]
                ])
                ->value('keywords', '');
        }
        // 栏目关键词
        elseif ($cid = Request::param('cid/f', null)) {
            $result = (new ModelCategory)
                ->where([
                    ['id', '=', $cid]
                ])
                ->value('keywords', '');
        } else {
            $result = (new ModelConfig)
                ->where([
                    ['name', '=', Request::controller(true) . '_keywords'],
                    ['lang', '=', Lang::getLangSet()]
                ])
                ->value('value', '');
        }

        return strip_tags(htmlspecialchars_decode($result));
    }

    /**
     * 网站标题
     * @access private
     * @static
     * @param
     * @return string
     */
    private static function title(): string
    {
        // 文章名
        if ($id = Request::param('id/f', null)) {
            $result = (new ModelArticle)
                ->where([
                    ['id', '=', $id]
                ])
                ->value('title', '');
        }
        // 栏目名
        elseif ($cid = Request::param('cid/f', null)) {
            $result = (new ModelCategory)
                ->where([
                    ['id', '=', $cid]
                ])
                ->value('name', 'NICMS');
        } else {
            $result = (new ModelConfig)
                ->where([
                    ['name', '=', Request::controller(true) . '_sitename'],
                    ['lang', '=', Lang::getLangSet()]
                ])
                ->value('value', 'NICMS');
        }

        return strip_tags(htmlspecialchars_decode($result));
    }

    /**
     * 网站名称
     * @access private
     * @static
     * @param
     * @return string
     */
    private static function name(): string
    {
        $result = (new ModelConfig)
            ->where([
                ['name', '=', Request::controller(true) . '_sitename'],
                ['lang', '=', Lang::getLangSet()]
            ])
            ->value('value', 'NICMS');

        return strip_tags(htmlspecialchars_decode($result));
    }

    /**
     * 网站版权
     * @access private
     * @static
     * @param
     * @return string
     */
    private static function copyright(): string
    {
        $copyright = (new ModelConfig)
            ->where([
                ['name', '=', Request::controller(true) . '_copyright'],
                ['lang', '=', Lang::getLangSet()]
            ])
            ->value('value', '');

        $beian = (new ModelConfig)
            ->where([
                ['name', '=', Request::controller(true) . '_beian'],
                ['lang', '=', Lang::getLangSet()]
            ])
            ->value('value', '备案号');

        return htmlspecialchars_decode($copyright) .
            '<p><a href="http://www.miitbeian.gov.cn" target="_blank" rel="nofollow">' . $beian . '</a> Powered by <a href="http://www.niphp.com" target="_blank" rel="nofollow">nicms</a></p>';
    }

    /**
     * 网站底部
     * @access private
     * @static
     * @param
     * @return string
     */
    private static function footer(): string
    {
        $result = (new ModelConfig)
            ->where([
                ['name', '=', Request::controller(true) . '_footer'],
                ['lang', '=', Lang::getLangSet()]
            ])
            ->value('value', 'footer');

        return htmlspecialchars_decode($result);
    }

    /**
     * JS脚本
     * @access private
     * @static
     * @param
     * @return string
     */
    private static function script(): string
    {
        $result = (new ModelConfig)
            ->where([
                ['name', '=', Request::controller(true) . '_script'],
                ['lang', '=', Lang::getLangSet()]
            ])
            ->value('value', '');

        return htmlspecialchars_decode($result);
    }

    /**
     * 主题
     * @access private
     * @static
     * @param
     * @return string
     */
    private static function theme(): string
    {
        $result = (new ModelConfig)
            ->where([
                ['name', '=', Request::controller(true) . '_theme'],
                ['lang', '=', Lang::getLangSet()]
            ])
            ->value('value', 'default');

        return strip_tags(htmlspecialchars_decode($result));
    }
}
