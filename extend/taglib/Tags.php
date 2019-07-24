<?php

/**
 *
 * 模板标签
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
     * foreach标签解析
     * 输出HTML底部部内容
     * 格式1： {tags:foreach name="变量名" [key="键" value="值"]}循环体{/foreach}
     * 格式2： {tags:foreach 变量名 键 => 值}循环体{/foreach}
     * @access public
     * @static
     * @param  array  $_attr    标签属性
     * @param  string $_content 循环体
     * @param  array  $_config  模板配置
     * @return string
     */
    public static function foreach(array $_attr = [], string $_content, array $_config)
    {
        $params  = isset($_attr['name']) ? '$' . $_attr['name'] . ' as ' : '';
        if ($params) {
            $params .= isset($_attr['key']) ? '$' . $_attr['key'] . ' => ' : '$key => ';
            $params .= isset($_attr['value']) ? '$' . $_attr['value'] : '$value';
            return '<?php foreach (' . $params . ') {' . $_content . '} ?>';
        } elseif (isset($_attr['expression'])) {
            return '<?php foreach (' . $_attr['expression'] . ') {' . $_content . '} ?>';
        } else {
            return '<!-- 无法解析:foreach ' . htmlspecialchars_decode($_content) . ' -->';
        }
    }

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
    public static function foot(array $_attr = [], array $_config)
    {
        $path = $_config['view_path'] . request()->controller(true) . DIRECTORY_SEPARATOR . $_config['view_theme'];

        $config = '';
        if (request()->isMobile() && is_file($path . 'mobile' . DIRECTORY_SEPARATOR . 'config.json')) {
            $config = file_get_contents($path . 'mobile' . DIRECTORY_SEPARATOR . 'config.json');
        } elseif (is_file($path . 'config.json')) {
            $config = file_get_contents($path . 'config.json');
        }

        $foot = '';
        if ($config && $config = json_decode(strip_tags($config), true)) {
            // JS引入
            foreach ($config['js'] as $js) {
                $foot .= '<script type="text/javascript" src="' . $js . '?v=' . $config['theme_version'] . '"></script>';
            }
        }

        return $foot;
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
    public static function meta(array $_attr = [], array $_config): string
    {
        $path = $_config['view_path'] . request()->controller(true) . DIRECTORY_SEPARATOR . $_config['view_theme'];

        $config = '';
        if (request()->isMobile() && is_file($path . 'mobile' . DIRECTORY_SEPARATOR . 'config.json')) {
            $config = file_get_contents($path . 'mobile' . DIRECTORY_SEPARATOR . 'config.json');
        } elseif (is_file($path . 'config.json')) {
            $config = file_get_contents($path . 'config.json');
        }
        $head = '';
        if ($config && $config = json_decode(strip_tags($config), true)) {
            // 自定义meta标签
            if (!empty($config['meta'])) {
                foreach ($config['meta'] as $m) {
                    $head .= '<meta ' . $m['type'] . ' ' . $m['content'] . ' />';
                }
            }
            // 自定义link标签
            if (!empty($config['link'])) {
                foreach ($config['link'] as $m) {
                    $head .= '<link rel="' . $m['rel'] . '" href="' . $m['href'] . '" />';
                }
            }

            // CSS引入
            if (!empty($config['css'])) {
                foreach ($config['css'] as $css) {
                    $head .= '<link rel="stylesheet" type="text/css" href="' . $css . '?v=' . $config['theme_version'] . '" />';
                }
            }
        }

        list($root) = explode('.', request()->rootDomain(), 2);

        return
            '<!DOCTYPE html>' .
            '<html lang="<?php echo request()->cookie("__lang");?>">' .
            '<head>' .
            '<meta charset="utf-8" />' .
            '<meta name="fragment" content="!" />' .                                // 支持蜘蛛ajax
            '<meta name="robots" content="all" />' .                                // 蜘蛛抓取
            '<meta name="revisit-after" content="1 days" />' .                      // 蜘蛛重访
            '<meta name="renderer" content="webkit" />' .                           // 强制使用webkit渲染
            '<meta name="force-rendering" content="webkit" />' .
            '<meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,minimum-scale=1,user-scalable=no" />' .

            '<meta name="generator" content="nicms" />' .
            '<meta name="author" content="levisun.mail@gmail.com" />' .
            '<meta name="copyright" content="2013-' . date('Y') . ' nicms all rights reserved" />' .

            '<meta http-equiv="Window-target" content="_blank">' .
            '<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />' .

            '<meta http-equiv="Cache-Control" content="no-siteapp" />' .            // 禁止baidu转码
            '<meta http-equiv="Cache-Control" content="no-transform" />' .

            '<meta http-equiv="x-dns-prefetch-control" content="on" />' .           // DNS缓存
            '<link rel="dns-prefetch" href="' . app()->config->get('app.api_host') . '" />' .
            '<link rel="dns-prefetch" href="' . app()->config->get('app.cdn_host') . '" />' .

            '<link href="' . app()->config->get('app.cdn_host') . '/favicon.ico" rel="shortcut icon" type="image/x-icon" />' .

            // 网站标题 关键词 描述
            '<title>__TITLE__</title>' .
            '<meta name="keywords" content="__KEYWORDS__" />' .
            '<meta name="description" content="__DESCRIPTION__" />' .
            '<meta property="og:title" content="__NAME__">' .
            '<meta property="og:type" content="website">' .
            '<meta property="og:url" content="' . request()->url(true) . '">' .
            '<meta property="og:image" content="">' .

            $head .

            '<script type="text/javascript">var NICMS=' . json_encode([
                'domain' => '//' . request()->subDomain() . '.' . request()->rootDomain(),
                'url'    => request()->baseUrl(true),
                'param'  => request()->param(),
                'api'    => [
                    'url'           => app()->config->get('app.api_host'),
                    'root'          => $root,
                    'version'       => $config['api_version'],
                    'appid'         => $config['api_appid'],
                    'appsecret'     => $config['api_appsecret'],
                    'authorization' => '{__AUTHORIZATION__}',
                    'param'         => request()->param()
                ],
                'cdn' => [
                    'static' => '__STATIC__',
                    'theme'  => '__THEME__',
                    'css'    => '__CSS__',
                    'img'    => '__IMG__',
                    'js'     => '__JS__',
                ]
            ]) .
            '</script>';
    }
}
