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

use think\facade\Lang;
use think\facade\Request;
use app\model\Config as ModelConfig;
use app\model\Article as ModelArticle;
use app\model\Category as ModelCategory;

class Siteinfo
{


    /**
     * 网站描述
     * @access public
     * @static
     * @param
     * @return string
     */
    public static function description(): string
    {
        $result = '';

        // 文章描述
        if ($id = Request::param('id/f', null)) {
            $result = (new ModelArticle)->where([
                ['id', '=', $id]
            ])
                ->value('description', '');
        }
        // 栏目描述
        elseif ($cid = Request::param('cid/f', null)) {
            $result = (new ModelCategory)->where([
                ['id', '=', $cid]
            ])
                ->value('description', '');
        } else {
            $result .= (new ModelConfig)->where([
                ['name', '=', Request::controller(true) . '_description'],
                ['lang', '=', Lang::getLangSet()]
            ])
                ->cache(__METHOD__ . Request::controller(true) . '_description' . Lang::getLangSet(), null, 'SITEINFO')
                ->value('value', '');
        }

        return $result;
    }

    /**
     * 网站关键词
     * @access public
     * @static
     * @param
     * @return string
     */
    public static function keywords(): string
    {
        $result = '';

        // 文章关键词
        if ($id = Request::param('id/f', false)) {
            $result = (new ModelArticle)
                ->where([
                    ['id', '=', $id]
                ])
                ->value('keywords', '');
        }
        // 栏目关键词
        elseif ($cid = Request::param('cid/f', false)) {
            $result = (new ModelCategory)
                ->where([
                    ['id', '=', $cid]
                ])
                ->value('keywords', '');
        } else {
            $result .= (new ModelConfig)
                ->where([
                    ['name', '=', Request::controller(true) . '_keywords'],
                    ['lang', '=', Lang::getLangSet()]
                ])
                ->cache(__METHOD__ . Request::controller(true) . '_keywords' . Lang::getLangSet(), null, 'SITEINFO')
                ->value('value', '');
        }

        return strip_tags($result);
    }

    /**
     * 网站标题
     * @access public
     * @static
     * @param
     * @return string
     */
    public static function title(): string
    {
        $result = '';

        // 文章名
        if ($id = Request::param('id/f', false)) {
            $result = (new ModelArticle)
                ->where([
                    ['id', '=', $id]
                ])
                ->value('title', '');
        }
        // 栏目名
        elseif ($cid = Request::param('cid/f', false)) {
            $result = (new ModelCategory)
                ->where([
                    ['id', '=', $cid]
                ])
                ->value('name', 'NICMS');
        } else {
            $result .= (new ModelConfig)
                ->where([
                    ['name', '=', Request::controller(true) . '_sitename'],
                    ['lang', '=', Lang::getLangSet()]
                ])
                ->cache(__METHOD__ . Request::controller(true) . '_sitename' . Lang::getLangSet(), null, 'SITEINFO')
                ->value('value', 'NICMS');
        }

        return strip_tags($result);
    }

    /**
     * 网站名称
     * @access public
     * @static
     * @param
     * @return string
     */
    public static function name(): string
    {
        return (new ModelConfig)
            ->where([
                ['name', '=', Request::controller(true) . '_sitename'],
                ['lang', '=', Lang::getLangSet()]
            ])
            ->cache(__METHOD__ . Request::controller(true) . '_sitename' . Lang::getLangSet(), null, 'SITEINFO')
            ->value('value', 'NICMS');
    }

    /**
     * 网站版权
     * @access public
     * @static
     * @param
     * @return string
     */
    public static function copyright(): string
    {
        $copyright = (new ModelConfig)
            ->where([
                ['name', '=', Request::controller(true) . '_copyright'],
                ['lang', '=', Lang::getLangSet()]
            ])
            ->cache(__METHOD__ . Request::controller(true) . '_copyright' . Lang::getLangSet(), null, 'SITEINFO')
            ->value('value', '');

        $beian = (new ModelConfig)
            ->where([
                ['name', '=', Request::controller(true) . '_beian'],
                ['lang', '=', Lang::getLangSet()]
            ])
            ->cache(__METHOD__ . Request::controller(true) . '_beian' . Lang::getLangSet(), null, 'SITEINFO')
            ->value('value', '备案号');

        return htmlspecialchars_decode($copyright) .
            '<p><a href="http://www.miitbeian.gov.cn" target="_blank" rel="nofollow">' . $beian . '</a> Powered by <a href="http://www.niphp.com" target="_blank" rel="nofollow">nicms</a></p>';
    }

    /**
     * 网站底部
     * @access public
     * @static
     * @param
     * @return string
     */
    public static function footer(): string
    {
        $result = (new ModelConfig)
            ->where([
                ['name', '=', Request::controller(true) . '_footer'],
                ['lang', '=', Lang::getLangSet()]
            ])
            ->cache(__METHOD__ . Request::controller(true) . '_footer' . Lang::getLangSet(), null, 'SITEINFO')
            ->value('value', 'footer');

        return htmlspecialchars_decode($result);
    }

    /**
     * JS脚本
     * @access public
     * @static
     * @param
     * @return string
     */
    public static function script(): string
    {
        $result = (new ModelConfig)
            ->where([
                ['name', '=', Request::controller(true) . '_script'],
                ['lang', '=', Lang::getLangSet()]
            ])
            ->cache(__METHOD__ . Request::controller(true) . '_script' . Lang::getLangSet(), null, 'SITEINFO')
            ->value('value', '');

        return htmlspecialchars_decode($result);
    }

    /**
     * 主题
     * @access public
     * @static
     * @param
     * @return string
     */
    public static function theme(): string
    {
        return (new ModelConfig)
            ->where([
                ['name', '=', Request::controller(true) . '_theme'],
                ['lang', '=', Lang::getLangSet()]
            ])
            ->cache(__METHOD__ . Request::controller(true) . '_theme' . Lang::getLangSet(), null, 'SITEINFO')
            ->value('value', 'default');
    }
}
