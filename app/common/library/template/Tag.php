<?php

declare(strict_types=1);

namespace app\common\library\template;

class Tag
{
    private $config = [];

    public function __construct(array $_config = [])
    {
        $this->config = $_config;
    }

    public function links(string $_attr)
    {
        $parseStr  = '<?php $links = app(\'\app\cms\logic\link\Catalog\')->query();';
        $parseStr .= 'if (empty($links[\'data\'])): miss(404, true, true); endif;';
        $parseStr .= '$links = !empty($links[\'data\']) ? $links[\'data\'] : [];?>';
        return $parseStr;
    }

    public function details(string $_attr)
    {
        $parseStr  = '<?php $details = app(\'\app\cms\logic\article\Details\')->query();';
        $parseStr .= 'if (empty($details[\'data\'])): miss(404, true, true); endif;';
        $parseStr .= '$details = !empty($details[\'data\']) ? $details[\'data\'] : [];?>';
        return $parseStr;
    }

    public function head(string $_attr, string &$_content)
    {
        $meta = '';
        if (!empty($this->config['theme_config']['meta'])) {
            foreach ($this->config['theme_config']['meta'] as $value) {
                $meta .= str_replace('\'', '"', $value) . PHP_EOL;
            }
        }

        $link = '';
        if (!empty($this->config['theme_config']['js'])) {
            foreach ($this->config['theme_config']['js'] as $js) {
                $js = preg_replace('/ {2,}/si', '', $js);
                $link .= str_replace('\'', '"', $js) . PHP_EOL;
            }
        }
        if (!empty($this->config['theme_config']['link'])) {
            foreach ($this->config['theme_config']['link'] as $value) {
                $value = preg_replace('/ {2,}/si', '', $value);
                $link .= str_replace('\'', '"', $value) . PHP_EOL;

                // $value = false === stripos($value, 'preload') && false === stripos($value, 'prefetch')
                // ? str_replace('rel="', 'as="style" rel="preload ', $value)
                // : $value;
            }
        }

        list($root) = explode('.', request()->rootDomain(), 2);
        return '<!DOCTYPE html>' .
            '<html lang="__LANG__">' .
            '<head>' .
            '<meta charset="UTF-8" />' .

            // 网站标题 关键词 描述
            '<title>__TITLE__</title>' .
            '<meta name="keywords" content="__KEYWORDS__" />' .
            '<meta name="description" content="__DESCRIPTION__" />' .

            '<meta property="og:title" content="__NAME__" />' .
            '<meta property="og:type" content="website" />' .
            '<meta property="og:url" content="__URL__" />' .
            '<meta property="og:image" content="" />' .
            '<meta name="fragment" content="!" />' .                                // 支持蜘蛛ajax
            '<meta name="robots" content="all" />' .                                // 蜘蛛抓取
            '<meta name="revisit-after" content="1 days" />' .                      // 蜘蛛重访
            '<meta name="renderer" content="webkit" />' .                           // 强制使用webkit渲染
            '<meta name="force-rendering" content="webkit" />' .
            '<meta http-equiv="X-UA-Compatible" content="IE=Edge,chrome=1" />' .
            '<meta name="author" content="312630173@qq.com" />' .
            '<meta name="generator" content="nicms" />' .
            '<meta name="copyright" content="2013-' . date('Y') . ' nicms all rights reserved" />' .
            '<meta http-equiv="x-dns-prefetch-control" content="on" />' .
            '<link rel="dns-prefetch" href="__API_HOST__" />' .
            '<link rel="dns-prefetch" href="__IMG_HOST__" />' .
            '<link rel="dns-prefetch" href="__CDN_HOST__" />' .
            '<link href="__IMG_HOST__favicon.ico" rel="shortcut icon" type="image/x-icon" />' .
            '<meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,minimum-scale=1,user-scalable=no" />' .
            '<meta http-equiv="Window-target" content="_top" />' .
            '<meta http-equiv="Cache-Control" content="no-siteapp" />' .            // 禁止baidu转码
            '<meta http-equiv="Cache-Control" content="no-transform" />' .
            '<meta name="csrf-version" content="' . $this->config['theme_config']['api_version'] . '" />' .
            csrf_appid() .
            $meta . $link .
            '<style type="text/css">body{moz-user-select:-moz-none;-moz-user-select:none;-o-user-select:none;-khtml-user-select:none;-webkit-user-select:none;-ms-user-select:none;user-select:none;}</style>' .
            '<script type="text/javascript">const NICMS = {domain:"//"+window.location.host+"/",rootDomain:"//"+window.location.host.substr(window.location.host.indexOf(".")+1)+"/",url:"//"+window.location.host+window.location.pathname+window.location.search,api_uri:"__API_HOST__",param:' . json_encode(app("request")->param()) . '};</script>' .

            '</head>' . (stripos($_content, '<body') ? '' : '<body>');
    }

    public function endHead()
    {
        return '</body></html>';
    }
}
