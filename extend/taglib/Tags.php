<?php

/**
 *
 * 模板标签
 * 不再提供具体功能标签,建议使用Vue+API实现.
 * API接口使用与方法名请参考[\app\api\README.md]文档.
 *
 * @package   NICMS
 * @category  extend\taglib
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace taglib;

class Tags
{

    /**
     * foot标签解析
     * 输出HTML底部部内容
     * 格式： {tags:foot /}
     * @access public
     * @static
     * @param  array $_attr   标签属性
     * @param  array $_config 模板配置
     * @return string
     */
    public static function tpljs(array $_attr, array $_config): string
    {
        $tpljs = '';

        // JS引入
        foreach ($_config['tpl_config']['js'] as $js) {
            // defer
            $tpljs .= str_replace('\'', '"', $js) . PHP_EOL;
        }

        return $tpljs;
    }

    /**
     * meta标签解析
     * 输出HTML头部内容
     * 格式： {tags:meta /}
     * @access public
     * @static
     * @param  array $_attr   标签属性
     * @param  array $_config 模板配置
     * @return string
     */
    public static function head(array $_attr, array $_config): string
    {
        $head = '';

        // meta标签
        if (!empty($_config['tpl_config']['meta'])) {
            foreach ($_config['tpl_config']['meta'] as $meta) {
                $head .= str_replace('\'', '"', $meta) . PHP_EOL;
            }
        }
        // link标签
        if (!empty($_config['tpl_config']['link'])) {
            foreach ($_config['tpl_config']['link'] as $link) {
                $head .= str_replace('\'', '"', $link) . PHP_EOL;
            }
        }

        list($root) = explode('.', request()->rootDomain(), 2);

        return
            '<!DOCTYPE html>' .
            '<html lang="<?php echo app("lang")->getLangSet();?>">' .
            '<head>' .
            '<meta charset="utf-8" />' .
            '<meta name="fragment" content="!" />' .                                // 支持蜘蛛ajax
            '<meta name="robots" content="all" />' .                                // 蜘蛛抓取
            '<meta name="revisit-after" content="1 days" />' .                      // 蜘蛛重访
            '<meta name="renderer" content="webkit" />' .                           // 强制使用webkit渲染
            '<meta name="force-rendering" content="webkit" />' .
            '<meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,minimum-scale=1,user-scalable=no" />' .

            '<meta name="author" content="levisun.mail@gmail.com" />' .
            '<meta name="generator" content="nicms" />' .
            '<meta name="copyright" content="2013-<?php echo date("Y");?> nicms all rights reserved" />' .

            '<meta http-equiv="Window-target" content="_top">' .
            '<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />' .

            '<meta http-equiv="Cache-Control" content="no-siteapp" />' .            // 禁止baidu转码
            '<meta http-equiv="Cache-Control" content="no-transform" />' .

            '<meta http-equiv="x-dns-prefetch-control" content="on" />' .           // DNS缓存

            '<meta name="csrf-appid" content="' . $_config['tpl_config']['api_appid'] . '" />' .
            '<meta name="csrf-appsecret" content="' . $_config['tpl_config']['api_appsecret'] . '" />' .
            '<meta name="csrf-root" content="' . $root . '" />' .
            '<meta name="csrf-version" content="' . $_config['tpl_config']['api_version'] . '" />' .
            '<?php echo authorization_meta();?>' .
            '<?php echo token_meta();?>' .

            '<link rel="dns-prefetch" href="' . config('app.api_host') . '" />' .
            '<link rel="dns-prefetch" href="' . config('app.img_host') . '" />' .
            '<link rel="dns-prefetch" href="' . config('app.cdn_host') . '" />' .

            '<link href="' . config('app.cdn_host') . '/favicon.ico" rel="shortcut icon" type="image/x-icon" />' .

            // 网站标题 关键词 描述
            '<title>__TITLE__</title>' .
            '<meta name="keywords" content="__KEYWORDS__" />' .
            '<meta name="description" content="__DESCRIPTION__" />' .
            '<meta property="og:title" content="__NAME__">' .
            '<meta property="og:type" content="article">' .
            '<meta property="og:url" content="' . request()->url(true) . '">' .
            '<meta property="og:image" content="">' .

            $head .
            '<script type="text/javascript">var NICMS = {
                "domain": "//' . request()->subDomain() . '.' . request()->rootDomain() . '",
                "rootDomain": "//' . request()->rootDomain() . '",
                "url": "' . request()->baseUrl(true) . '",
                "api": {
                    "url": "' . config('app.api_host') . '",
                    "param": {__REQUEST_PARAM__}
                },
                "cdn": {
                    "static": "__STATIC__",
                    "theme": "__THEME__",
                    "css": "__CSS__",
                    "img": "__IMG__",
                    "js": "__JS__"
                }
            }</script></head>';
    }
}
