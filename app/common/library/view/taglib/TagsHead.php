<?php

/**
 *
 * HTML头信息标签
 *
 * @package   NICMS
 * @category  view\taglib
 * @author    失眠小枕头 [312630173@qq.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace app\common\library\view\taglib;

use app\common\library\view\Taglib;

class TagsHead extends Taglib
{

    public function alone(): string
    {
        list($root) = explode('.', request()->rootDomain(), 2);

        return
            '<!DOCTYPE html>' .
            '<html lang="<?php echo app(\'lang\')->getLangSet();?>">' .
            '<head>' .
            '<meta charset="UTF-8" />' .

            // 网站标题 关键词 描述
            '<title>{$web_title}</title>' .
            '<meta name="keywords" content="{$web_keywords}" />' .
            '<meta name="description" content="{$web_description}" />' .

            '<meta property="og:title" content="__NAME__" />' .
            '<meta property="og:type" content="website" />' .
            '<meta property="og:url" content="<?php echo request()->baseUrl(true);?>" />' .
            '<meta property="og:image" content="" />' .

            '<meta name="fragment" content="!" />' .                                // 支持蜘蛛ajax
            '<meta name="robots" content="all" />' .                                // 蜘蛛抓取
            '<meta name="googlebot" content="all" />' .
            '<meta name="baiduspider" content="all" />' .
            '<meta name="revisit-after" content="1 days" />' .                      // 蜘蛛重访

            '<meta name="renderer" content="webkit" />' .                           // 强制使用webkit渲染
            '<meta name="force-rendering" content="webkit" />' .
            '<meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,minimum-scale=1,user-scalable=no" />' .
            '<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />' .

            '<meta name="author" content="312630173@qq.com" />' .
            '<meta name="generator" content="nicms" />' .
            '<meta name="copyright" content="2013-<?php echo date(\'Y\');?> nicms all rights reserved" />' .

            '<meta http-equiv="Window-target" content="_top" />' .

            '<meta http-equiv="Cache-Control" content="no-siteapp" />' .            // 禁止baidu转码
            '<meta http-equiv="Cache-Control" content="no-transform" />' .

            '<meta name="csrf-root" content="' . $root . '" />' .
            '<meta name="csrf-version" content="' . $this->config['tpl_config']['api_version'] . '" />' .
            '<?php echo csrf_appid();?>' .

            '<meta http-equiv="x-dns-prefetch-control" content="on" />' .           // DNS缓存
            '<link rel="dns-prefetch" href="<?php echo config(\'app.api_host\');?>" />' .
            '<link rel="dns-prefetch" href="<?php echo config(\'app.img_host\');?>" />' .
            '<link rel="dns-prefetch" href="<?php echo config(\'app.cdn_host\');?>" />' .

            '<link href="<?php echo config(\'app.img_host\');?>/favicon.ico" rel="shortcut icon" type="image/x-icon" />' .

            /* '<?php echo request()->isMobile() ? \'<link rel="canonical" href="\' . request()->scheme() . \'://www.\' . request()->rootDomain() . \'" />\' : \'<link rel="alternate" href="\' . request()->scheme() . \'://m.\' . request()->rootDomain() . \'" />\' ?>' . */

            $this->meta() .
            $this->link() .

            '<script type="text/javascript">const NICMS = {' .
            'domain:"//"+window.location.host,' .
            'rootDomain:"//"+window.location.host.substr(window.location.host.indexOf(".")+1),' .
            'url:"//"+window.location.host+window.location.pathname+window.location.search,' .
            'api_uri:"' . config("app.api_host") . '",' .
            'param:<?php echo json_encode(app("request")->param());?>,' .
            'static:"__STATIC__",' .
            'theme:"__THEME__",' .
            'css:"__CSS__",' .
            'img:"__IMG__",' .
            'js:"__JS__"' .
            '}</script></head><body>';

        // <style type="text/css">*{moz-user-select:-moz-none;-moz-user-select:none; -o-user-select:none;-khtml-user-select:none;-webkit-user-select:none;-ms-user-select:none; user-select:none;}</style>
        // -webkit-filter: grayscale(100%);
    }

    private function link()
    {
        $link = '';
        if (!empty($this->config['tpl_config']['link'])) {
            foreach ($this->config['tpl_config']['link'] as $value) {
                // 过滤多余空格
                $value = preg_replace('/( ){2,}/si', '', $value);
                // 替换引号
                $value = str_replace('\'', '"', $value);

                // $value = false === stripos($value, 'preload') && false === stripos($value, 'prefetch')
                //     ? str_replace('rel="', 'rel="preload ', $value)
                //     : $value;

                $link .= $value;
            }
        }
        return $link;
    }

    private function meta()
    {
        $meta = '';
        if (!empty($this->config['tpl_config']['meta'])) {
            foreach ($this->config['tpl_config']['meta'] as $value) {
                $meta .= str_replace('\'', '"', $value);
            }
        }
        return $meta;
    }
}
