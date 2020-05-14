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





    public function details(array $_attr, string $_tags_content, array $_config)
    {
        $_attr['id'] = !empty($_attr['id']) ? $_attr['id'] : request()->param('id/d', 0);
        $_attr['cid'] = !empty($_attr['cid']) ? $_attr['cid'] : request()->param('cid/d', 0);
        $_attr['date_format'] = !empty($_attr['date_format']) ? $_attr['date_format'] : 'Y-m-d';
        $parseStr = '<?php
        $map = [
            ["article.is_pass", "=", "1"],
            ["article.delete_time", "=", "0"],
            ["article.show_time", "<", time()],
            ["article.lang", "=", app("lang")->getLangSet()]
        ];
        if ("' . $_attr['id'] . '") {
            $map[] = ["article.id", "=", $id];
        } elseif ("' . $_attr['cid'] . '") {
            $map[] = ["article.category_id", "=", $cid];
        }
        if ("' . $_attr['id'] . '" || "' . $_attr['cid'] . '") {
            $cache_key = md5("tags article details' . $_attr['id'] .  $_attr['cid'] . '";
            if (!cache("?".$cache_key) || !$result = cache($cache_key)) {
                $result = \app\common\model\Article::view("article", ["id", "category_id", "title", "keywords", "description", "username", "access_id", "hits", "update_time"])
                    ->view("category", ["name" => "cat_name"], "category.id=article.category_id")
                    ->view("model", ["id" => "model_id", "name" => "model_name", "table_name"], "model.id=category.model_id")
                    ->view("type", ["id" => "type_id", "name" => "type_name"], "type.id=article.type_id", "LEFT")
                    ->view("level", ["name" => "access_name"], "level.id=article.access_id", "LEFT")
                    ->view("user", ["username" => "author"], "user.id=article.user_id", "LEFT")
                    ->where($map)
                    ->find();
                if ($result && $result = $result->toArray()) {
                    $result["cat_url"] = url("list/" . $result["category_id"]);
                    $result["url"] = url("details/" . $result["category_id"] . "/" . $result["id"]);
                    $result["flag"] = \app\common\library\Base64::flag($result["category_id"] . $result["id"], 7);
                    $result["update_time"] = date("' . $_attr['date_format'] . '", (int) $result["update_time"]);
                    $result["author"] = $result["author"] ?: $result["username"];
                    unset($result["username"]);
                }
            }
        }
        ';

    }

    public static function catalog(array $_attr, string $_tags_content, array $_config)
    {
        $_attr['cid'] = !empty($_attr['cid']) ? $_attr['cid'] : request()->param('cid/d', 0);
        $_attr['com'] = !empty($_attr['com']) ? $_attr['com'] : 0;
        $_attr['top'] = !empty($_attr['top']) ? $_attr['top'] : 0;
        $_attr['hot'] = !empty($_attr['hot']) ? $_attr['hot'] : 0;
        $_attr['tid'] = !empty($_attr['tid']) ? $_attr['tid'] : 0;
        $_attr['sort'] = !empty($_attr['sort']) ? $_attr['sort'] : 0;
        $_attr['limit'] = !empty($_attr['limit']) ? $_attr['limit'] : 10;
        $_attr['page'] = !empty($_attr['page']) ? $_attr['page'] : 1;
        $_attr['date_format'] = !empty($_attr['date_format']) ? $_attr['date_format'] : 'Y-m-d';

        $parseStr = '<?php
        $map = [
            ["article.is_pass", "=", "1"],
            ["article.delete_time", "=", "0"],
            ["article.show_time", "<", time()],
            ["article.lang", "=", app("lang")->getLangSet()]
        ];
        if ("' . $_attr['cid'] . '") {
            $map[] = ["article.category_id", "in", app("\app\cms\logic\article\Category")->child(intval(' . $_attr['cid'] . '))];
        }
        if ("' . $_attr['com'] . '") {
            $map[] = ["article.is_com", "=", "1"];
        } elseif ("' . $_attr['top'] . '") {
            $map[] = ["article.is_top", "=", "1"];
        } elseif ("' . $_attr['hot'] . '") {
            $map[] = ["article.is_hot", "=", "1"];
        }
        if ("' . $_attr['tid'] . '") {
            $map[] = ["article.type_id", "=", ' . $_attr['tid'] . '];
        }
        if ("' . $_attr['sort'] . '") {
            $sort_order = "article.' . $_attr['sort'] . '";
        } else {
            $sort_order = "article.is_top DESC, article.is_hot DESC , article.is_com DESC, article.sort_order DESC, article.update_time DESC";
        }
        $cache_key = md5("tags article list' . $_attr['cid'] . $_attr['com'] . $_attr['top'] . $_attr['hot'] . $_attr['tid'] . $_attr['sort'] . $_attr['limit'] . $_attr['page'] . $_attr['date_format'] . '");
        if (!cache("?".$cache_key) || !$list = cache($cache_key)) {
            $result = \app\common\model\Article::view("article", ["id", "category_id", "title", "keywords", "description", "username", "access_id", "hits", "update_time"])
                ->view("category", ["name" => "cat_name"], "category.id=article.category_id")
                ->view("model", ["id" => "model_id", "name" => "model_name"], "model.id=category.model_id and model.id<=3")
                ->view("article_content", ["thumb"], "article_content.article_id=article.id", "LEFT")
                ->view("type", ["id" => "type_id", "name" => "type_name"], "type.id=article.type_id", "LEFT")
                ->view("level", ["name" => "access_name"], "level.id=article.access_id", "LEFT")
                ->view("user", ["username" => "author"], "user.id=article.user_id", "LEFT")
                ->where($map)
                ->order($sort_order)
                ->paginate([
                    "list_rows" => ' . $_attr['limit'] . ',
                    "path" => "javascript:paging([PAGE]);",
                ]);

            if ($result) {
                $list = $result->toArray();
                $list["render"] = $result->render();
                foreach ($list["data"] as $key => $value) {
                    $value["cat_url"] = url("list/" . $value["category_id"]);
                    $value["url"] = url("details/" . $value["category_id"] . "/" . $value["id"]);
                    $value["flag"] = \app\common\library\Base64::flag($value["category_id"] . $value["id"], 7);
                    $value["thumb"] = \app\common\library\Canvas::image($value["thumb"], 300);
                    $value["update_time"] = date("' . $_attr['date_format'] . '", (int) $value["update_time"]);
                    $value["author"] = $value["author"] ?: $value["username"];
                    unset($value["username"]);

                    $fields = \app\common\model\FieldsExtend::view("fields_extend", ["data"])
                        ->view("fields", ["name" => "fields_name"], "fields.id=fields_extend.fields_id")
                        ->where([
                            ["fields_extend.article_id", "=", $value["id"]],
                            ["fields.category_id", "=", $value["category_id"]],
                        ])
                        ->select()
                        ->toArray();
                    foreach ($fields as $val) {
                        $value[$val["fields_name"]] = $val["data"];
                    }

                    $value["tags"] = \app\common\model\ArticleTags::view("article_tags", ["tags_id"])
                        ->view("tags tags", ["name"], "tags.id=article_tags.tags_id")
                        ->where([
                            ["article_tags.article_id", "=", $value["id"]],
                        ])
                        ->select()
                        ->toArray();
                    foreach ($value["tags"] as $k => $tag) {
                        $tag["url"] = url("tags/" . $tag["tags_id"]);
                        $value["tags"][$k] = $tag;
                    }

                    $list["data"][$key] = $value;
                }

                cache($cache_key, $list);
            }
        }
        if (!empty($list)):
        $total = &$list["total"];
        $per_page = $list["per_page"];
        $current_page = $list["current_page"];
        $last_page = $list["last_page"];
        $page = $list["render"];
        $items = $list["data"];
        foreach ($items as $key => $item): ?>';
        $parseStr .= $_tags_content;
        $parseStr .= '<?php endforeach; endif; ?>';

        return $parseStr;
    }

    public static function not_empty(array $_attr, string $_tags_content, array $_config)
    {
        $parseStr = '<?php if(empty($' . $_attr['expression'] . ')): ?>';
        $parseStr .= $_tags_content;
        $parseStr .= '<?php endif; ?>';
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
        $_attr['type'] = empty($_attr['type']) ? strtolower($_attr['type']) : 'main';

        switch ($_attr['type']) {
            case 'breadcrumb':
                $_attr['type'] = '\app\cms\logic\nav\Breadcrumb';
                break;

            case 'foot':
                $_attr['type'] = '\app\cms\logic\nav\Foot';
                break;

            case 'other':
                $_attr['type'] = '\app\cms\logic\nav\Other';
                break;

            case 'sidebar':
                $_attr['type'] = '\app\cms\logic\nav\Sidebar';
                break;

            case 'top':
                $_attr['type'] = '\app\cms\logic\nav\Top';
                break;

            default:
                $_attr['type'] = '\app\cms\logic\nav\Main';
                break;
        }

        $parseStr  = '<?php $__TAGS__NAV_RESULT = app(\'' . $_attr['type'] . '\')->query();';
        $parseStr .= 'if (!is_null($__TAGS__NAV_RESULT[\'data\'])):';
        $parseStr .= '$__TAGS__NAV_RESULT = $__TAGS__NAV_RESULT[\'data\'];';
        $parseStr .= 'foreach ($__TAGS__NAV_RESULT as $key => $nav): ?>';
        $parseStr .= $_tags_content;
        $parseStr .= '<?php endforeach; endif; ?>';

        return $parseStr;
    }



    public static function else(array $_attr, array $_config): string
    {
        return '<?php else ?>';
    }

    public static function elseif(array $_attr, string $_tags_content, array $_config): string
    {
        $parseStr  = '<?php ';
        $parseStr .= 'elseif (' . $_attr['expression'] . '): ?>';
        $parseStr .= $_tags_content;
        $parseStr .= '<?php endelseif; ?>';
        return $parseStr;
    }

    public static function if(array $_attr, string $_tags_content, array $_config): string
    {
        $parseStr  = '<?php ';
        $parseStr .= 'if (' . $_attr['expression'] . '): ?>';
        $parseStr .= $_tags_content;
        $parseStr .= '<?php endif; ?>';
        return $parseStr;
    }

    public static function foreach(array $_attr, string $_tags_content, array $_config): string
    {
        $parseStr  = '<?php ';
        $parseStr .= 'foreach (' . $_attr['expression'] . '): ?>';
        $parseStr .= $_tags_content;
        $parseStr .= '<?php endforeach; ?>';
        return $parseStr;
    }

    /**
     * meta标签解析
     * 输出HTML头部内容
     * 格式： {tags:head /}
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
            '<script type="text/javascript">const NICMS = {' .
            'domain:"//<?php echo request()->subDomain() . "." . request()->rootDomain();?>",' .
            'rootDomain:"//<?php echo request()->rootDomain();?>",' .
            'url:"<?php echo request()->baseUrl(true);?>",' .
            'ip:"<?php echo request()->ip();?>",' .
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
