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

namespace app\common\controller;

use think\facade\Cache;
use think\facade\Lang;
use think\facade\Request;

use app\common\library\Filter;
use app\common\model\Config as ModelConfig;

abstract class BaseSiteInfo
{
    protected $appName = 'cms';
    protected $langSet = '';
    protected $siteConfig = [];

    /**
     * 构造方法
     * @access public
     * @return void
     */
    public function __construct()
    {
        $this->langSet = Lang::getLangSet();

        $cache_key = __METHOD__ . $this->appName . $this->langSet;
        if (!Cache::has($cache_key) || !$common = Cache::get($cache_key)) {
            $common = [
                'theme'     => $this->theme(),
                'script'    => $this->script(),
                'footer'    => $this->footer(),
                'copyright' => $this->copyright(),
                'name'      => $this->siteName(),
            ];

            Cache::tag('request')->set($cache_key, $common, 28800);
        }

        $view_path = 'theme/' . app('http')->getName() . '/' . $common['theme'] . '/';
        $common['url']  = config('app.static_host') . $view_path;

        // 移动端目录
        if (Request::isMobile() && is_dir($common['view_path'] . 'mobile')) {
            $view_path .= 'mobile/';
            $common['url'] .= 'mobile/';
        } elseif (false !== stripos(Request::server('HTTP_USER_AGENT'), 'MicroMessenger') && is_dir($common['view_path'] . 'wechat')) {
            $view_path .= 'wechat/';
            $common['url'] .= 'wechat/';
        }
        $common['view_path'] = public_path($view_path);

        $common['tpl_replace_string'] = [
            '__APP_NAME__'    => config('app.app_name'),
            '__STATIC__'      => config('app.static_host') . 'static/',
            '__URL__'         => Request::baseUrl(true),
            '__LANG__'        => app('lang')->getLangSet(),
            '__API_HOST__'    => config('app.api_host'),
            '__IMG_HOST__'    => config('app.img_host'),
            '__STATIC_HOST__' => config('app.static_host'),

            '__NAME__'        => $common['name'],
            '__FOOTER_MSG__'  => $common['footer'],
            '__COPYRIGHT__'   => $common['copyright'],
            '__SCRIPT__'      => $common['script'],

            '__THEME__'       => $common['url'],
            '__CSS__'         => $common['url'] . 'css/',
            '__IMG__'         => $common['url'] . 'img/',
            '__JS__'          => $common['url'] . 'js/',
        ];

        $this->siteConfig = $common;
    }

    /**
     * 网站描述
     * @access protected
     * @return string
     */
    protected function description()
    {
        # code...
    }

    /**
     * 网站关键词
     * @access protected
     * @return string
     */
    protected function keywords()
    {
        # code...
    }

    /**
     * 网站标题
     * @access protected
     * @return string
     */
    protected function title()
    {
        # code...
    }

    /**
     * 网站名称
     * @access protected
     * @return string
     */
    protected function siteName(): string
    {
        $site = ModelConfig::where('name', '=', $this->appName . '_sitename')
            ->where('lang', '=', $this->langSet)
            ->value('value', 'nicms');

        return strip_tags(htmlspecialchars_decode($site));
    }

    /**
     * 主题
     * @access protected
     * @return string
     */
    protected function theme(): string
    {
        $theme = ModelConfig::where('name', '=', $this->appName . '_theme')
            ->where('lang', '=', $this->langSet)
            ->value('value', 'default');

        return strip_tags(htmlspecialchars_decode($theme));
    }

    /**
     * 网站版权
     * @access protected
     * @return string
     */
    protected function copyright(): string
    {
        $copyright = ModelConfig::where('name', '=', 'copyright')
            ->where('lang', '=', $this->langSet)
            ->value('value', '');

        $beian = ModelConfig::where('name', '=', 'beian')
            ->where('lang', '=', $this->langSet)
            ->value('value', '');

        return
            Filter::htmlDecode($copyright) . '&nbsp;' .
            Filter::htmlDecode($beian) . '&nbsp;' .
            '&nbsp;Powered&nbsp;by&nbsp;<a href="https://github.com/levisun/nicms" rel="nofollow" target="_blank">NICMS</a>';
    }

    /**
     * 网站底部
     * @access protected
     * @return string
     */
    protected function footer(): string
    {
        $footer = ModelConfig::where('name', '=', 'footer')
            ->where('lang', '=', $this->langSet)
            ->value('value', '');

        return htmlspecialchars_decode($footer);
    }

    /**
     * JS脚本
     * @access protected
     * @return string
     */
    protected function script(): string
    {
        $result = ModelConfig::where('name', '=', 'script')
            ->where('lang', '=', $this->langSet)
            ->value('value', '');

        $result = preg_replace([
            '/(\s+\n|\r)/s',
            '/(\t|\0|\x0B)/s',
            '/( ){2,}/s',
        ], '', $result);

        return $result
            ? '<script type="text/javascript">' . htmlspecialchars_decode($result) . '</script>'
            : '';
    }
}
