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


    /**
     * 网站描述
     * @access public
     * @static
     * @param
     * @return string
     */
    public static function description(): string
    {
        $id = Request::param('id/f', null);
        $cid = Request::param('cid/f', null);

        $cache_key = md5(__METHOD__ . $id . $cid);
        if (!Cache::has($cache_key)) {
            // 文章描述
            if ($id) {
                $result = (new ModelArticle)
                    ->where([
                        ['id', '=', $id]
                    ])
                    ->value('description', '');
            }
            // 栏目描述
            elseif ($cid) {
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

            $result = strip_tags(htmlspecialchars_decode($result));

            Cache::tag(['cms', 'siteinfo'])->set($cache_key, $result);
            return $result;
        } else {
            return Cache::get($cache_key);
        }
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
        $id = Request::param('id/f', null);
        $cid = Request::param('cid/f', null);

        $cache_key = md5(__METHOD__ . $id . $cid);
        if (!Cache::has($cache_key)) {
            // 文章关键词
            if ($id) {
                $result = (new ModelArticle)
                    ->where([
                        ['id', '=', $id]
                    ])
                    ->value('keywords', '');
            }
            // 栏目关键词
            elseif ($cid) {
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

            $result = strip_tags(htmlspecialchars_decode($result));

            Cache::tag(['cms', 'siteinfo'])->set($cache_key, $result);
            return $result;
        } else {
            return Cache::get($cache_key);
        }
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
        $id = Request::param('id/f', null);
        $cid = Request::param('cid/f', null);

        $cache_key = md5(__METHOD__ . $id . $cid);
        if (!Cache::has($cache_key)) {
            // 文章名
            if ($id) {
                $result = (new ModelArticle)
                    ->where([
                        ['id', '=', $id]
                    ])
                    ->value('title', '');
            }
            // 栏目名
            elseif ($cid) {
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

            $result = strip_tags(htmlspecialchars_decode($result));

            Cache::tag(['cms', 'siteinfo'])->set($cache_key, $result);
            return $result;
        } else {
            return Cache::get($cache_key);
        }
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
        $cache_key = md5(__METHOD__);
        if (!Cache::has($cache_key)) {
            $result = (new ModelConfig)
                ->where([
                    ['name', '=', Request::controller(true) . '_sitename'],
                    ['lang', '=', Lang::getLangSet()]
                ])
                ->value('value', 'NICMS');

            $result = strip_tags(htmlspecialchars_decode($result));

            Cache::tag(['cms', 'siteinfo'])->set($cache_key, $result);
            return $result;
        } else {
            return Cache::get($cache_key);
        }
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
        $cache_key = md5(__METHOD__);
        if (!Cache::has($cache_key)) {
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

            $result = htmlspecialchars_decode($copyright) .
                '<p><a href="http://www.miitbeian.gov.cn" target="_blank" rel="nofollow">' . $beian . '</a> Powered by <a href="http://www.niphp.com" target="_blank" rel="nofollow">nicms</a></p>';

            Cache::tag(['cms', 'siteinfo'])->set($cache_key, $result);
            return $result;
        } else {
            return Cache::get($cache_key);
        }
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
        $cache_key = md5(__METHOD__);
        if (!Cache::has($cache_key)) {
            $result = (new ModelConfig)
                ->where([
                    ['name', '=', Request::controller(true) . '_footer'],
                    ['lang', '=', Lang::getLangSet()]
                ])
                ->value('value', 'footer');

            $result = htmlspecialchars_decode($result);

            Cache::tag(['cms', 'siteinfo'])->set($cache_key, $result);
            return $result;
        } else {
            return Cache::get($cache_key);
        }
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
        $cache_key = md5(__METHOD__);
        if (!Cache::has($cache_key)) {
            $result = (new ModelConfig)
                ->where([
                    ['name', '=', Request::controller(true) . '_script'],
                    ['lang', '=', Lang::getLangSet()]
                ])
                ->value('value', '');

            $result = htmlspecialchars_decode($result);

            Cache::tag(['cms', 'siteinfo'])->set($cache_key, $result);
            return $result;
        } else {
            return Cache::get($cache_key);
        }
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
        $cache_key = md5(__METHOD__);
        if (!Cache::has($cache_key)) {
            $result = (new ModelConfig)
                ->where([
                    ['name', '=', Request::controller(true) . '_theme'],
                    ['lang', '=', Lang::getLangSet()]
                ])
                ->value('value', 'default');

            $result = strip_tags(htmlspecialchars_decode($result));

            Cache::tag(['cms', 'siteinfo'])->set($cache_key, $result);
            return $result;
        } else {
            return Cache::get($cache_key);
        }
    }
}
