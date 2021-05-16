<?php

namespace app\common\taglib;

use think\template\TagLib;

class Tags extends TagLib
{

    /**
     * 定义标签列表
     */
    protected $tags   =  [
        'list' => ['attr' => '', 'close' => 1],
        'page' => ['attr' => 'date_format,id,page_id', 'close' => 0],
        'category' => ['attr' => '', 'close' => 1],
        'details' => ['attr' => '', 'close' => 0],
        'head'    => ['attr' => '', 'close' => 0],
        'foot'    => ['attr' => '', 'close' => 0],
    ];

    public function tagList($_tag, $_content)
    {
        $_tag['date_format'] = !empty($_tag['date_format']) ? $_tag['date_format'] : 'Y-m-d';
        $_tag['limit'] = !empty($_tag['limit']) ? (int) $_tag['limit'] : 10;
        $_tag['limit'] = 100 > $_tag['limit'] ? $_tag['limit'] : 10;
        $_tag['page'] = !empty($_tag['page']) ? (int) $_tag['page'] : 1;

        $sort_order = isset($_tag['sort'])
            ? 'article.' . $_tag['sort']
            : 'article.attribute DESC, article.sort_order DESC, article.update_time DESC';

        $cache_key = 'taglib::article list' . implode('', $_tag);

        $parse  = '<?php
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
        if (isset($_tag['cid'])) {
            $child = app('\app\cms\logic\article\Category')->child($_tag["cid"]);
            $parse .= '->where("article.category_id", "in", "' . implode(',', $child) . '")';
        }
        if ($_tag['attribute']) {
            $parse .= '->where("article.attribute", "=", "' . $_tag['attribute'] . '")';
        }

        if (isset($_tag['tid'])) {
            $parse .= '->where("article.type_id", "=", "' . $_tag['tid'] . '")';
        }
        $parse .= '
                ])
                ->order("' . $sort_order . '")
                ->paginate([
                    "list_rows" => ' . $_tag['limit'] . ',
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
                        $value["update_time"] = date("' . $_tag['date_format'] . '", (int) $value["update_time"]);
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

        return $parse;
    }



    public function tagPage($_tag, $_content)
    {
        $_tag['date_format'] = !empty($_tag['date_format']) ? $_tag['date_format'] : 'Y-m-d';
        $cache_key = 'taglib::article details' . implode('', $_tag);

        $parse = '<?php
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
        if (isset($_tag['id'])) {
            $parse .= '->where("article.id", "=", ' . $_tag['id'] . ')';
        } elseif (isset($_tag['page_id'])) {
            $parse .= '->where("article.category_id", "=", ' . $_tag['page_id'] . ')';
        }
        $parse .= '->find();
                if ($result && $result = $result->toArray()):
                    $result["thumb"] = \app\common\library\tools\File::imgUrl($result["thumb"]);
                    $result["cat_url"] = url("list/" . \app\common\library\Base64::url62encode($result["category_id"]));
                    $result["url"] = url("details/" . \app\common\library\Base64::url62encode($result["category_id"]) . "/" . \app\common\library\Base64::url62encode($result["id"]));
                    $result["flag"] = \app\common\library\Base64::flag($result["category_id"] . $result["id"], 7);
                    $result["update_time"] = date("' . $_tag['date_format'] . '", (int) $result["update_time"]);
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

        return $parse;
    }

    public function tagCategory($_tag, $_content)
    {
        $parse  = '<?php $result = app(\'\app\cms\logic\article\Category\')->query();';
        $parse .= '$result = !empty($result[\'data\']) ? $result[\'data\'] : [];';
        $parse .= 'foreach ($result["list"] $key => $item):';
        $parse .= $_content;
        $parse .= 'endforeach;';
        $parse .= '?>';
    }

    public function tagDetails($_tag, $_content): string
    {
        $parse  = '<?php $details = app(\'\app\cms\logic\article\Details\')->query();';
        $parse .= 'if (empty($details[\'data\'])): miss(404, true, true); endif;';
        $parse .= '$details = !empty($details[\'data\']) ? $details[\'data\'] : [];?>';

        return $parse;
    }

    public function tagHead($_tag, $_content): string
    {
        $view_path = $this->tpl->getConfig('view_path');
        $theme_config = is_file($view_path . 'config.json')
            ? json_decode(file_get_contents($view_path . 'config.json'), true)
            : [];

        $meta = '';
        if (!empty($theme_config['meta'])) {
            foreach ($theme_config['meta'] as $value) {
                $meta .= str_replace('\'', '"', $value);
            }
        }

        $link = '';
        if (!empty($theme_config['link'])) {
            foreach ($theme_config['link'] as $value) {
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
            '<link rel="canonical" href="<?php echo request()->baseUrl(true); ?>" />' .
            '<link rel="dns-prefetch" href="__API_HOST__" />' .
            '<link rel="dns-prefetch" href="__IMG_HOST__" />' .
            '<link rel="dns-prefetch" href="__STATIC_HOST__" />' .
            '<link href="__IMG_HOST__favicon.ico" rel="shortcut icon" type="image/x-icon" />' .
            '<meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,minimum-scale=1,user-scalable=no" />' .
            '<meta http-equiv="Window-target" content="_top" />' .
            '<meta http-equiv="Cache-Control" content="no-siteapp" />' .            // 禁止baidu转码
            '<meta http-equiv="Cache-Control" content="no-transform" />' .
            $meta . $link .
            '<style type="text/css">body{moz-user-select:-moz-none;-moz-user-select:none;-o-user-select:none;-khtml-user-select:none;-webkit-user-select:none;-ms-user-select:none;user-select:none;}</style>' .
            '<script src="__STATIC_HOST__static/<?php echo trim(base64_encode(app("http")->getName()), "=");?>.do?token=<?php echo trim(base64_encode(json_encode(app("request")->only(["id","pass","attribute","status","model_id","limit","page","date_format","sort","key","category_id","type_id","book_id","book_type_id","lang"]))), "=");?>&version=' . $theme_config['api_version'] . '"></script>' .
            '</head>';
    }

    public function tagFoot($_tag, $_content): string
    {
        $view_path = $this->tpl->getConfig('view_path');
        $theme_config = is_file($view_path . 'config.json')
            ? json_decode(file_get_contents($view_path . 'config.json'), true)
            : [];

        $link = '';
        if (!empty($theme_config['js'])) {
            foreach ($theme_config['js'] as $js) {
                $js = preg_replace('/ {2,}/si', '', $js);
                $link .= str_replace('\'', '"', $js);
            }
        }

        return $link . '</body></html>';
    }
}
