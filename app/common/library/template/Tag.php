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

    public function details(string $_attr)
    {
        $parseStr  = '<?php $details = app(\'\app\cms\logic\article\Details\')->query();';
        $parseStr .= 'if (empty($details[\'data\'])): miss(404, true, true); endif;';
        $parseStr .= '$details = !empty($details[\'data\']) ? $details[\'data\'] : []; ?>';
        return $parseStr;
    }

    public function head(string $_attr)
    {
        list($root) = explode('.', request()->rootDomain(), 2);
        return '<!DOCTYPE html>' .
            '<html lang="<?php echo app(\'lang\')->getLangSet();?>">' .
            '<head>' .
            '<meta charset="UTF-8" />' .

            // 网站标题 关键词 描述
            '<title>__TITLE__</title>' .
            '<meta name="keywords" content="__KEYWORDS__" />' .
            '<meta name="description" content="__DESCRIPTION__" />' .

            '<meta property="og:title" content="__NAME__" />' .
            '<meta property="og:type" content="website" />' .
            '<meta property="og:url" content="<?php echo request()->baseUrl(true);?>" />' .
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
            '<link rel="dns-prefetch" href="' . config('app.api_host') . '" />' .
            '<link rel="dns-prefetch" href="' . config('app.img_host') . '" />' .
            '<link rel="dns-prefetch" href="' . config('app.cdn_host') . '" />' .
            '<link href="<?php echo config(\'app.img_host\');?>/favicon.ico" rel="shortcut icon" type="image/x-icon" />' .
            '<meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,minimum-scale=1,user-scalable=no" />' .
            '<meta http-equiv="Window-target" content="_top" />' .
            '<meta http-equiv="Cache-Control" content="no-siteapp" />' .            // 禁止baidu转码
            '<meta http-equiv="Cache-Control" content="no-transform" />' .
            '<meta name="csrf-root" content="' . $root . '" />' .
            '<meta name="csrf-version" content="' . $this->config['theme_config']['api_version'] . '" />' .
            csrf_appid() .
            $this->meta() .
            $this->link() .
            '<style type="text/css">body{moz-user-select:-moz-none;-moz-user-select:none;-o-user-select:none;-khtml-user-select:none;-webkit-user-select:none;-ms-user-select:none;user-select:none;}</style>' .
            '<script type="text/javascript">const NICMS = {domain:"//"+window.location.host,rootDomain:"//"+window.location.host.substr(window.location.host.indexOf(".")+1),url:"//"+window.location.host+window.location.pathname+window.location.search,api_uri:"' . config("app.api_host") . '",param:' . json_encode(app("request")->param()) . '};</script>' .

            '</head><body>';
    }

    public function endHead()
    {
        return '</body></html>';
    }

    public function link()
    {
        $link = '';
        if (!empty($this->config['theme_config']['link'])) {
            foreach ($this->config['theme_config']['link'] as $value) {
                // 过滤多余空格
                $value = preg_replace('/ {2,}/si', '', $value);
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

    public function meta()
    {
        $meta = '';
        if (!empty($this->config['theme_config']['meta'])) {
            foreach ($this->config['theme_config']['meta'] as $value) {
                $meta .= str_replace('\'', '"', $value);
            }
        }
        return $meta;
    }
}
