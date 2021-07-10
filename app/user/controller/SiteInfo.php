<?php

/**
 *
 * 网站信息
 *
 * @package   NICMS
 * @category  app\user\controller
 * @author    失眠小枕头 [312630173@qq.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace app\user\controller;

use app\common\controller\BaseSiteInfo;
use think\facade\Cache;
use think\facade\Request;
use app\common\model\Config as ModelConfig;
use app\common\model\Article as ModelArticle;
use app\common\model\Category as ModelCategory;

class SiteInfo extends BaseSiteInfo
{
    protected $appName = 'user';

    public function query(): array
    {
        $cache_key = __METHOD__ . $this->appName . $this->langSet;
        $cache_key .= Request::param('id', 0) . Request::param('category_id', 0);
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

        return strip_tags(htmlspecialchars_decode($keywords));
    }

    /**
     * 网站标题
     * @access protected
     * @return string
     */
    protected function title(): string
    {
        $title = $this->siteName();

        return strip_tags(htmlspecialchars_decode($title));
    }
}
