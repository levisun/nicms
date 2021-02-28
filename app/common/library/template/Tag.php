<?php

/**
 *
 * 模板默认标签库
 *
 * @package   NICMS
 * @category  app\common\library\template
 * @author    失眠小枕头 [312630173@qq.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2020
 */

declare(strict_types=1);

namespace app\common\library\template;

class Tag
{
    private $config = [];

    public function __construct(array $_config = [])
    {
        $this->config = $_config;
    }

    public function links(array $_attr)
    {
        $parseStr  = '<?php $links = app(\'\app\cms\logic\link\Catalog\')->query();';
        $parseStr .= 'if (empty($links[\'data\'])): miss(404, true, true); endif;';
        $parseStr .= '$links = !empty($links[\'data\']) ? $links[\'data\'] : [];?>';
        return $parseStr;
    }

    public function list(array $_attr)
    {
        if (!empty($_attr)) {
            $_attr['date_format'] = !empty($_attr['date_format']) ? $_attr['date_format'] : 'Y-m-d';
            $_attr['limit'] = !empty($_attr['limit']) ? (int) $_attr['limit'] : 10;
            $_attr['limit'] = 100 > $_attr['limit'] ? $_attr['limit'] : 10;
            $_attr['page'] = !empty($_attr['page']) ? (int) $_attr['page'] : 1;

            $sort_order = isset($_attr['sort'])
                ? 'article.' . $_attr['sort']
                : 'article.attribute DESC, article.sort_order DESC, article.update_time DESC';

            $cache_key = 'taglib::article list' . implode('', $_attr);

            $parseStr  = '<?php
            if (!cache("?' . $cache_key . '") || !$list = cache("' . $cache_key . '")):
                $result = \app\common\model\Article::view("article", ["id", "category_id", "title", "keywords", "description", "thumb", "username", "access_id", "hits", "update_time"])
                ->view("category", ["name" => "cat_name"], "category.id=article.category_id")
                ->view("model", ["id" => "model_id", "name" => "model_name"], "model.id=category.model_id and model.id<=3")
                ->view("type", ["id" => "type_id", "name" => "type_name"], "type.id=article.type_id", "LEFT")
                ->view("level", ["name" => "access_name"], "level.id=article.access_id", "LEFT")
                ->view("user", ["username" => "author"], "user.id=article.user_id", "LEFT")
                ->where("article.is_pass", "=", 1)
                ->where("article.delete_time", "=", 0)
                ->where("article.show_time", "<", time())
                ->where("article.lang", "=", app("lang")->getLangSet())';
            if (isset($_attr['cid'])) {
                $child = app('\app\cms\logic\article\Category')->child($_attr["cid"]);
                $parseStr .= '->where("article.category_id", "in", "' . implode(',', $child) . '")';
            }
            if ($_attr['attribute']) {
                $parseStr .= '->where("article.attribute", "=", "' . $_attr['attribute'] . '")';
            }

            if (isset($_attr['tid'])) {
                $parseStr .= '->where("article.type_id", "=", "' . $_attr['tid'] . '")';
            }
            $parseStr .= '
                ])
                ->order("' . $sort_order . '")
                ->paginate([
                    "list_rows" => ' . $_attr['limit'] . ',
                    "path" => "javascript:paging([PAGE]);",
                ]);
                if ($result):
                    $list = $result->toArray();
                    $list["render"] = $result->render();
                    foreach ($list["data"] as $key => $value):
                        $value["cat_url"] = url("list/" . \app\common\library\Base64::url62encode($value["category_id"]));
                        $value["url"] = url("details/" . \app\common\library\Base64::url62encode($value["category_id"]) . "/" . \app\common\library\Base64::url62encode($value["id"]));
                        $value["flag"] = \app\common\library\Base64::flag($value["category_id"] . $value["id"], 7);
                        $value["thumb"] = \app\common\library\tools\File::imgUrl($value["thumb"], 300);
                        $value["update_time"] = date("' . $_attr['date_format'] . '", (int) $value["update_time"]);
                        $value["author"] = $value["author"] ?: $value["username"];
                        unset($value["username"]);

                        $fields = \app\common\model\FieldsExtend::view("fields_extend", ["data"])
                            ->view("fields", ["name" => "fields_name"], "fields.id=fields_extend.fields_id")
                            ->where("fields_extend.article_id", "=", $value["id"])
                            ->where("fields.category_id", "=", $value["category_id"])
                            ->select()
                            ->toArray();
                        foreach ($fields as $val):
                            $value[$val["fields_name"]] = $val["data"];
                        endforeach;

                        $value["tags"] = \app\common\model\ArticleTags::view("article_tags", ["tags_id"])
                            ->view("tags tags", ["name"], "tags.id=article_tags.tags_id")
                            ->where("article_tags.article_id", "=", $value["id"])
                            ->select()
                            ->toArray();
                        foreach ($value["tags"] as $k => $tag):
                            $tag["url"] = url("tags/" . \app\common\library\Base64::url62encode($tag["tags_id"]));
                            $value["tags"][$k] = $tag;
                        endforeach;

                        $list["data"][$key] = $value;
                    endforeach;
                    cache("' . $cache_key . '", $list);
                endif;
            endif;
            $total = $list["total"];
            $per_page = $list["per_page"];
            $current_page = $list["current_page"];
            $last_page = $list["last_page"];
            $page = $list["render"];
            $items = $list["data"]; ?>';
        } else {
            $parseStr  = '<?php $result = app(\'\app\cms\logic\article\Category\')->query();';
            $parseStr .= '$result = !empty($result[\'data\']) ? $result[\'data\'] : [];
                $total = $result["total"];
                $per_page = $result["per_page"];
                $current_page = $result["current_page"];
                $last_page = $result["last_page"];
                $page = $result["page"];
                $items = $result["list"];';
        }
        return $parseStr;
    }

    public function details(array $_attr)
    {
        if (!empty($_attr)) {
            $_attr['date_format'] = !empty($_attr['date_format']) ? $_attr['date_format'] : 'Y-m-d';

            $cache_key = 'taglib::article details' . implode('', $_attr);

            $parseStr  = '<?php
            if (!cache("?' . $cache_key . '") || !$result = cache("' . $cache_key . '")):
                $result = \app\common\model\Article::view("article", ["id", "category_id", "title", "keywords", "description", "thumb", "username", "access_id", "hits", "update_time"])
                    ->view("category", ["name" => "cat_name"], "category.id=article.category_id")
                    ->view("model", ["id" => "model_id", "name" => "model_name", "table_name"], "model.id=category.model_id")
                    ->view("type", ["id" => "type_id", "name" => "type_name"], "type.id=article.type_id", "LEFT")
                    ->view("level", ["name" => "access_name"], "level.id=article.access_id", "LEFT")
                    ->view("user", ["username" => "author"], "user.id=article.user_id", "LEFT")
                    ->where("article.is_pass", "=", 1)
                    ->where("article.delete_time", "=", 0)
                    ->where("article.show_time", "<", time())
                    ->where("article.lang", "=", app("lang")->getLangSet())';
            if (isset($_attr['id'])) {
                $parseStr .= '->where("article.id", "=", ' . $_attr['id'] . ')';
            } elseif (isset($_attr['page_id'])) {
                $parseStr .= '->where("article.category_id", "=", ' . $_attr['page_id'] . ')';
            }
            $parseStr .= '->find();
                if ($result && $result = $result->toArray()):
                    $result["thumb"] = \app\common\library\tools\File::imgUrl($result["thumb"]);
                    $result["cat_url"] = url("list/" . \app\common\library\Base64::url62encode($result["category_id"]));
                    $result["url"] = url("details/" . \app\common\library\Base64::url62encode($result["category_id"]) . "/" . \app\common\library\Base64::url62encode($result["id"]));
                    $result["flag"] = \app\common\library\Base64::flag($result["category_id"] . $result["id"], 7);
                    $result["update_time"] = date("' . $_attr['date_format'] . '", (int) $result["update_time"]);
                    $result["author"] = $result["author"] ?: $result["username"];
                    unset($result["username"]);

                    if ($result["model_id"] <= 3) {
                        $result["next"] = $result["prev"] = [];
                    }

                    $fields = \app\common\model\FieldsExtend::view("fields_extend", ["data"])
                        ->view("fields", ["name" => "fields_name"], "fields.id=fields_extend.fields_id")
                        ->where("fields_extend.article_id", "=", $result["id"])
                        ->where("fields.category_id", "=", $result["category_id"])
                        ->select()
                        ->toArray();
                    foreach ($fields as $value):
                        $result[$value["fields_name"]] = $value["data"];
                    endforeach;

                    $result["tags"] = \app\common\model\ArticleTags::view("article_tags", ["tags_id"])
                        ->view("tags", ["name"], "tags.id=article_tags.tags_id")
                        ->where("article_tags.article_id", "=", $result["id"])
                        ->select()
                        ->toArray();
                    foreach ($result["tags"] as $key => $tag):
                        $tag["url"] = url("tags/" . \app\common\library\Base64::url62encode($tag["tags_id"]));
                        $result["tags"][$key] = $tag;
                    endforeach;

                    $content = \app\common\model\ArticleContent::where("article_id", "=", $result["id"])->find();
                    if ($content && $content = $content->toArray()):
                        unset($content["id"], $content["article_id"]);
                        $result["content"] = \app\common\library\tools\File::imgUrl($content["content"]);
                    endif;

                    cache("' . $cache_key . '", $result);
                endif;
            endif;
            $details = !empty($result) ? $result : []; ?>';
        } else {
            $parseStr  = '<?php $details = app(\'\app\cms\logic\article\Details\')->query();';
            $parseStr .= 'if (empty($details[\'data\'])): miss(404, true, true); endif;';
            $parseStr .= '$details = !empty($details[\'data\']) ? $details[\'data\'] : [];?>';
        }
        return $parseStr;
    }

    public function head(array $_attr, string &$_content)
    {
        $meta = '';
        if (!empty($this->config['theme_config']['meta'])) {
            foreach ($this->config['theme_config']['meta'] as $value) {
                $meta .= str_replace('\'', '"', $value);
            }
        }

        $link = '';
        if (!empty($this->config['theme_config']['js'])) {
            foreach ($this->config['theme_config']['js'] as $js) {
                $js = preg_replace('/ {2,}/si', '', $js);
                $link .= str_replace('\'', '"', $js);
            }
        }
        if (!empty($this->config['theme_config']['link'])) {
            foreach ($this->config['theme_config']['link'] as $value) {
                $value = preg_replace('/ {2,}/si', '', $value);
                $link .= str_replace('\'', '"', $value);

                // $value = false === stripos($value, 'preload') && false === stripos($value, 'prefetch')
                // ? str_replace('rel="', 'as="style" rel="preload ', $value)
                // : $value;
            }
        }

        return '<!DOCTYPE html>' .
            '<html lang="__LANG__">' .
            '<head>' .
            '<meta charset="UTF-8" />' .

            // 网站标题 关键词 描述
            '<title>{$TITLE}</title>' .
            '<meta name="keywords" content="{$KEYWORDS}" />' .
            '<meta name="description" content="{$DESCRIPTION}" />' .

            '<meta property="og:title" content="__NAME__" />' .
            '<meta property="og:type" content="website" />' .
            '<meta property="og:url" content="{$URL}" />' .
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
            '<script type="text/javascript">const NICMS = {domain:"//"+window.location.host+"/",rootDomain:"//"+window.location.host.substr(window.location.host.indexOf(".")+1)+"/",url:"//"+window.location.host+window.location.pathname,api_uri:"__API_HOST__",app_name:"__APP_NAME__",param:<?php echo json_encode(app("request")->param());?>};</script>' .

            '<script src="__API_HOST__tools/ip.do?token=<?php echo md5(request()->url(true));?>" async></script>' .
            ('admin' !== app('http')->getName() ? '<script src="__API_HOST__tools/record.do?url=<?php echo urlencode(request()->url(true));?>" async></script>' : '') .

            '</head>' . (stripos($_content, '<body') ? '' : '<body>');
    }

    public function endHead()
    {
        return '</body></html>';
    }
}
