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
use app\common\model\Config as ModelConfig;
use app\common\model\Article as ModelArticle;
use app\common\model\Category as ModelCategory;

class Siteinfo
{
    private $appName = '';
    private $langSet = '';

    public function __construct()
    {
        $this->appName = app('http')->getName();
        $this->langSet = Lang::getLangSet();
    }

    public function query()
    {
        $cache_key = $this->appName . $this->langSet;
        $cache_key = md5($cache_key);
        if (!Cache::has($cache_key) || !$common = Cache::get($cache_key)) {
            $common = [
                'theme'     => $this->theme(),
                'script'    => $this->script(),
                'footer'    => $this->footer(),
                'copyright' => $this->copyright(),
                'name'      => $this->name(),
            ];

            Cache::tag('SYSTEM')->set($cache_key, $common);
        }

        $cache_key .= app('request')->param('id/f', null) . app('request')->param('cid/f', null);
        $cache_key = md5($cache_key);
        if (!Cache::has($cache_key) || !$result = Cache::get($cache_key)) {
            $result = [
                'title'       => $this->title(),
                'keywords'    => $this->keywords(),
                'description' => $this->description(),
            ];

            Cache::tag('SYSTEM')->set($cache_key, $result);
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
        // 文章描述
        if ($id = app('request')->param('id/f', null)) {
            $result = (new ModelArticle)
                ->where([
                    ['id', '=', $id]
                ])
                ->value('description', '');
        }
        // 栏目描述
        elseif ($cid = app('request')->param('cid/f', null)) {
            $result = (new ModelCategory)
                ->where([
                    ['id', '=', $cid]
                ])
                ->value('description', '');
        } else {
            $result = (new ModelConfig)
                ->where([
                    ['name', '=', $this->appName . '_description'],
                    ['lang', '=', $this->langSet]
                ])
                ->value('value', '');
        }

        return strip_tags(htmlspecialchars_decode($result));
    }

    /**
     * 网站关键词
     * @access private
     * @return string
     */
    private function keywords(): string
    {
        // 文章关键词
        if ($id = app('request')->param('id/f', null)) {
            $result = (new ModelArticle)
                ->where([
                    ['id', '=', $id]
                ])
                ->value('keywords', '');
        }
        // 栏目关键词
        elseif ($cid = app('request')->param('cid/f', null)) {
            $result = (new ModelCategory)
                ->where([
                    ['id', '=', $cid]
                ])
                ->value('keywords', '');
        } else {
            $result = (new ModelConfig)
                ->where([
                    ['name', '=', $this->appName . '_keywords'],
                    ['lang', '=', $this->langSet]
                ])
                ->value('value', '');
        }

        return strip_tags(htmlspecialchars_decode($result));
    }

    /**
     * 网站标题
     * @access private
     * @return string
     */
    private function title(): string
    {
        // 文章名
        if ($id = app('request')->param('id/f', null)) {
            $result = (new ModelArticle)
                ->where([
                    ['id', '=', $id]
                ])
                ->value('title', '');
        }
        // 栏目名
        elseif ($cid = app('request')->param('cid/f', null)) {
            $result = (new ModelCategory)
                ->where([
                    ['id', '=', $cid]
                ])
                ->value('name', 'NICMS');
        } else {
            $result = (new ModelConfig)
                ->where([
                    ['name', '=', $this->appName . '_sitename'],
                    ['lang', '=', $this->langSet]
                ])
                ->value('value', 'NICMS');
        }

        return strip_tags(htmlspecialchars_decode($result));
    }

    /**
     * 网站名称
     * @access private
     * @return string
     */
    private function name(): string
    {
        $result = (new ModelConfig)
            ->where([
                ['name', '=', $this->appName . '_sitename'],
                ['lang', '=', $this->langSet]
            ])
            ->value('value', 'NICMS');

        return strip_tags(htmlspecialchars_decode($result));
    }

    /**
     * 网站版权
     * @access private
     * @return string
     */
    private function copyright(): string
    {
        $copyright = (new ModelConfig)
            ->where([
                ['name', '=', $this->appName . '_copyright'],
                ['lang', '=', $this->langSet]
            ])
            ->value('value', '');

        $beian = (new ModelConfig)
            ->where([
                ['name', '=', $this->appName . '_beian'],
                ['lang', '=', $this->langSet]
            ])
            ->value('value', '');
        $beian = $beian
            ? '<a href="http://www.beian.miit.gov.cn" target="_blank" rel="nofollow">' . $beian . '</a>'
            : '';

        return htmlspecialchars_decode($copyright) . $beian . ' Powered by <a href="http://www.niphp.com" target="_blank" rel="nofollow">nicms</a>';
    }

    /**
     * 网站底部
     * @access private
     * @return string
     */
    private function footer(): string
    {
        $result = (new ModelConfig)
            ->where([
                ['name', '=', $this->appName . '_footer'],
                ['lang', '=', $this->langSet]
            ])
            ->value('value', 'footer');

        return htmlspecialchars_decode($result);
    }

    /**
     * JS脚本
     * @access private
     * @return string
     */
    private function script(): string
    {
        $result = (new ModelConfig)
            ->where([
                ['name', '=', $this->appName . '_script'],
                ['lang', '=', $this->langSet]
            ])
            ->value('value', '');

        return htmlspecialchars_decode($result);
    }

    /**
     * 主题
     * @access private
     * @return string
     */
    private function theme(): string
    {
        $result = (new ModelConfig)
            ->where([
                ['name', '=', $this->appName . '_theme'],
                ['lang', '=', $this->langSet]
            ])
            ->value('value', 'default');

        return strip_tags(htmlspecialchars_decode($result));
    }
}
