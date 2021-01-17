<?php

/**
 *
 * 单页详情标签
 *
 * @package   NICMS
 * @category  app\common\library\view\taglib
 * @author    失眠小枕头 [312630173@qq.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace app\common\library\view\taglib;

use app\common\library\view\Taglib;

class TagsPage extends Taglib
{

    public function alone(): string
    {
        $this->params['id'] = !empty($this->params['id']) ? (int) $this->params['id'] : '';
        $this->params['page_id'] = !empty($this->params['page_id']) ? (int) $this->params['page_id'] : '';
        $this->params['date_format'] = !empty($this->params['date_format']) ? $this->params['date_format'] : 'Y-m-d';

        $cache_key = 'article details' . $this->params['id'] . $this->params['page_id'];

        $parseStr  = '<?php
            if (!cache("?' . $cache_key . '") || !$result = cache("' . $cache_key . '")):
                $result = \app\common\model\Article::view("article", ["id", "category_id", "title", "keywords", "description", "username", "access_id", "hits", "update_time"])
                    ->view("category", ["name" => "cat_name"], "category.id=article.category_id")
                    ->view("model", ["id" => "model_id", "name" => "model_name", "table_name"], "model.id=category.model_id")
                    ->view("type", ["id" => "type_id", "name" => "type_name"], "type.id=article.type_id", "LEFT")
                    ->view("level", ["name" => "access_name"], "level.id=article.access_id", "LEFT")
                    ->view("user", ["username" => "author"], "user.id=article.user_id", "LEFT")
                    ->where(';
        if ($this->params['id']) {
            $parseStr .= '[
                ["article.id", "=", ' . $this->params['id'] . '],
                ["article.is_pass", "=", "1"],
                ["article.delete_time", "=", "0"],
                ["article.show_time", "<", time()],
                ["article.lang", "=", app("lang")->getLangSet()]
            ]';
        } elseif ($this->params['page_id']) {
            $parseStr .= '[
                ["article.category_id", "=", ' . $this->params['page_id'] . '],
                ["article.is_pass", "=", 1],
                ["article.delete_time", "=", 0],
                ["article.show_time", "<", time()],
                ["article.lang", "=", app("lang")->getLangSet()]
            ]';
        }
        $parseStr .= ')->find();
                if ($result && $result = $result->toArray()):
                    $result["cat_url"] = url("list/" . \app\common\library\Base64::url62encode($result["category_id"]));
                    $result["url"] = url("details/" . \app\common\library\Base64::url62encode($result["category_id"]) . "/" . \app\common\library\Base64::url62encode($result["id"]));
                    $result["flag"] = \app\common\library\Base64::flag($result["category_id"] . $result["id"], 7);
                    $result["update_time"] = date("' . $this->params['date_format'] . '", (int) $result["update_time"]);
                    $result["author"] = $result["author"] ?: $result["username"];
                    unset($result["username"]);

                    $fields = \app\common\model\FieldsExtend::view("fields_extend", ["data"])
                        ->view("fields", ["name" => "fields_name"], "fields.id=fields_extend.fields_id")
                        ->where([
                            ["fields_extend.article_id", "=", $result["id"]],
                            ["fields.category_id", "=", $result["category_id"]],
                        ])
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
                        $result["thumb"] = \app\common\library\File::pathToUrl($content["thumb"]);
                        $result["content"] = \app\common\library\File::pathToUrl($content["content"]);
                    endif;

                    cache("' . $cache_key . '", $result);
                endif;
            endif;
            $page = !empty($result) ? $result : []; ?>';

        return $parseStr;
    }
}
