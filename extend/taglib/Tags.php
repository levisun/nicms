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

        // // JS引入
        // foreach ($_config['tpl_config']['js'] as $js) {
        //     // defer
        //     $tpljs .= str_replace('\'', '"', $js) . PHP_EOL;
        // }

        return $tpljs;
    }

    public static function foreach(array $_attr, string $_tags_content, array $_config): string
    {
        $parseStr  = '<?php ';
        $parseStr .= 'foreach (' . $_attr['expression'] . ') { ?>';
        $parseStr .= $_tags_content;
        $parseStr .= '<?php } ?>';
        return $parseStr;

        var_dump($_attr);
        die();


        $parseStr .= 'if (!is_null($result[\'data\'])) {';
        $parseStr .= '$nav = $result[\'data\'];';
        $parseStr .= '$count = count($nav);';
        $parseStr .= 'foreach ($nav as $key => $vo) { ?>';
        $parseStr .= $_tags_content;
        $parseStr .= '<?php } unset($result, $nav, $count, $key, $vo); } ?>';

        return $parseStr;
    }

    /**
     * nav标签解析
     * 输出导航内容
     * 格式
     * {tags:nav type=main}
     * {/nav}
     * @access public
     * @static
     * @param  array $_attr   标签属性
     * @param  array $_config 模板配置
     * @return string
     */
    public static function nav(array $_attr, string $_tags_content, array $_config): string
    {
        $_attr['type'] = empty($_attr['type']) ? $_attr['type'] : 'main';

        switch ($_attr['type']) {
            case 'breadcrumb':
            case 'Breadcrumb':
                $_attr['type'] = '\app\cms\logic\nav\Breadcrumb';
                break;

            case 'foot':
            case 'Foot':
                $_attr['type'] = '\app\cms\logic\nav\Foot';
                break;

            case 'other':
            case 'Other':
                $_attr['type'] = '\app\cms\logic\nav\Other';
                break;

            case 'sidebar':
            case 'Sidebar':
                $_attr['type'] = '\app\cms\logic\nav\Sidebar';
                break;

            case 'top':
            case 'Top':
                $_attr['type'] = '\app\cms\logic\nav\Top';
                break;

            default:
                $_attr['type'] = '\app\cms\logic\nav\Main';
                break;
        }

        $parseStr  = '<?php $result = app(\'' . $_attr['type'] . '\')->query();';
        $parseStr .= 'if (!is_null($result[\'data\'])) {';
        $parseStr .= '$nav = $result[\'data\'];';
        $parseStr .= '$count = count($nav);';
        $parseStr .= 'foreach ($nav as $key => $vo) { ?>';
        $parseStr .= $_tags_content;
        $parseStr .= '<?php } unset($result, $nav, $count, $key, $vo); } ?>';

        return $parseStr;
    }

    public static function isset(array $_attr, string $_tags_content, array $_config)
    {
        $parseStr  = '<?php ';
        $parseStr .= 'if (isset(' . $_attr['expression'] . ') && !empty(' . $_attr['expression'] . ')) { ?>';
        $parseStr .= $_tags_content;
        $parseStr .= '<?php } ?>';
        return $parseStr;
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
                $head .= str_replace('\'', '"', $meta);
            }
        }
        // link标签
        if (!empty($_config['tpl_config']['link'])) {
            foreach ($_config['tpl_config']['link'] as $link) {
                // 过滤多余空格
                $link = preg_replace('/( ){2,}/si', '', $link);
                // 替换引号
                $link = str_replace(['\'', '/>'], ['"', '>'], $link);

                $link = false === stripos($link, 'preload') && false === stripos($link, 'prefetch')
                    ? str_replace('rel="', 'rel="preload ', $link)
                    : $link;


                // 添加异步加载属性
                // $link = false === stripos($link, 'media')
                //     ? str_replace('">', '" media="none" onload="if(media!=\'all\')media=\'all\'">', $link)
                //     : $link;
                $head .= $link;
            }
        }

        list($root) = explode('.', request()->rootDomain(), 2);

        return
            '<!DOCTYPE html>' .
            '<html lang="<?php echo app(\'lang\')->getLangSet();?>">' .
            '<head>' .
            '<meta charset="UTF-8" />' .

            // 网站标题 关键词 描述
            '<title>__TITLE__</title>' .
            '<meta name="keywords" content="__KEYWORDS__" />' .
            '<meta name="description" content="__DESCRIPTION__" />' .

            '<meta property="og:title" content="__NAME__">' .
            '<meta property="og:type" content="website">' .
            '<meta property="og:url" content="<?php echo request()->baseUrl(true);?>">' .
            '<meta property="og:image" content="">' .

            '<meta name="fragment" content="!" />' .                                // 支持蜘蛛ajax
            '<meta name="robots" content="all" />' .                                // 蜘蛛抓取
            '<meta name="googlebot" content="all" />' .
            '<meta name="baiduspider" content="all" />' .
            '<meta name="revisit-after" content="1 days" />' .                      // 蜘蛛重访

            '<meta name="renderer" content="webkit" />' .                           // 强制使用webkit渲染
            '<meta name="force-rendering" content="webkit" />' .
            '<meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,minimum-scale=1,user-scalable=no" />' .
            '<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />' .

            '<meta name="author" content="levisun.mail@gmail.com" />' .
            '<meta name="generator" content="nicms" />' .
            '<meta name="copyright" content="2013-<?php echo date(\'Y\');?> nicms all rights reserved" />' .

            '<meta http-equiv="Window-target" content="_top">' .

            '<meta http-equiv="Cache-Control" content="no-siteapp" />' .            // 禁止baidu转码
            '<meta http-equiv="Cache-Control" content="no-transform" />' .

            '<meta name="csrf-root" content="' . $root . '" />' .
            '<meta name="csrf-version" content="' . $_config['tpl_config']['api_version'] . '" />' .
            '<meta name="csrf-appid" content="' . $_config['tpl_config']['api_appid'] . '" />' .
            '<?php echo app_secret_meta(' . $_config['tpl_config']['api_appid'] . ');?>' .
            '<?php echo authorization_meta();?>' .
            '<?php echo token_meta();?>' .

            '<meta http-equiv="x-dns-prefetch-control" content="on" />' .           // DNS缓存
            '<link rel="dns-prefetch" href="<?php echo config(\'app.api_host\');?>" />' .
            '<link rel="dns-prefetch" href="<?php echo config(\'app.img_host\');?>" />' .
            '<link rel="dns-prefetch" href="<?php echo config(\'app.cdn_host\');?>" />' .

            '<link href="<?php echo config(\'app.img_host\');?>/favicon.ico" rel="shortcut icon" type="image/x-icon" />' .

            $head .
            '<script type="text/javascript">var NICMS = {' .
            'domain:"//<?php echo request()->subDomain() . "." . request()->rootDomain();?>",' .
            'rootDomain:"//<?php echo request()->rootDomain();?>",' .
            'url:"<?php echo request()->baseUrl(true);?>",' .
            'api:{' .
            'url:"<?php echo config("app.api_host");?>",' .
            'param:<?php echo json_encode(app("request")->param());?>' .
            '},' .
            'cdn:{' .
            'static:"__STATIC__",' .
            'theme:"__THEME__",' .
            'css:"__CSS__",' .
            'img:"__IMG__",' .
            'js:"__JS__"' .
            '}' .
            '}</script></head>';

        // <style type="text/css">*{moz-user-select:-moz-none;-moz-user-select:none; -o-user-select:none;-khtml-user-select:none;-webkit-user-select:none;-ms-user-select:none; user-select:none;}</style>
        // -webkit-filter: grayscale(100%);
    }
}
