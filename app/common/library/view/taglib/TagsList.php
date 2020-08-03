<?php

/**
 *
 * 指定列表标签
 *
 * @package   NICMS
 * @category  app\common\library\view\taglib
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace app\common\library\view\taglib;

use app\common\library\view\Taglib;

class TagsList extends Taglib
{

    public function closed(): string
    {
        $this->params['cid'] = !empty($this->params['cid']) ? (int) $this->params['cid'] : request()->param('cid/d', 0);
        $this->params['com'] = !empty($this->params['com']) ? (int) $this->params['com'] : 0;
        $this->params['top'] = !empty($this->params['top']) ? (int) $this->params['top'] : 0;
        $this->params['hot'] = !empty($this->params['hot']) ? (int) $this->params['hot'] : 0;
        $this->params['tid'] = !empty($this->params['tid']) ? (int) $this->params['tid'] : 0;
        $this->params['sort'] = !empty($this->params['sort']) ? $this->params['sort'] : '';
        $this->params['limit'] = !empty($this->params['limit']) ? (int) $this->params['limit'] : 10;
        $this->params['page'] = !empty($this->params['page']) ? (int) $this->params['page'] : 1;
        $this->params['date_format'] = !empty($this->params['date_format']) ? $this->params['date_format'] : 'Y-m-d';

        if ($this->params['sort']) {
            $sort_order = 'article.' . $this->params['sort'];
        } else {
            $sort_order = 'article.is_top DESC, article.is_hot DESC , article.is_com DESC, article.sort_order DESC, article.update_time DESC';
        }

        $cache_key = md5('taglib tablist::article list' . $this->params['cid'] . $this->params['com'] . $this->params['top'] . $this->params['hot'] . $this->params['tid'] . $this->params['sort'] . $this->params['limit'] . $this->params['page'] . $this->params['date_format']);

        $parseStr  = '<?php
        if (!cache("?' . $cache_key . '") || !$list = cache("' . $cache_key . '")):
            $result = \app\common\model\Article::view("article", ["id", "category_id", "title", "keywords", "description", "thumb", "username", "access_id", "hits", "update_time"])
            ->view("category", ["name" => "cat_name"], "category.id=article.category_id")
            ->view("model", ["id" => "model_id", "name" => "model_name"], "model.id=category.model_id and model.id<=3")
            ->view("type", ["id" => "type_id", "name" => "type_name"], "type.id=article.type_id", "LEFT")
            ->view("level", ["name" => "access_name"], "level.id=article.access_id", "LEFT")
            ->view("user", ["username" => "author"], "user.id=article.user_id", "LEFT")
            ->where([
                ["article.is_pass", "=", "1"],
                ["article.delete_time", "=", "0"],
                ["article.show_time", "<", time()],
                ["article.lang", "=", app("lang")->getLangSet()],';
        if ($this->params['cid']) {
            $child = app('\app\cms\logic\article\Category')->child($this->params["cid"]);
            $parseStr .= '["article.category_id", "in", "' . implode(',', $child) . '"],';
        }
        if ($this->params['com']) {
            $parseStr .= '["article.is_com", "=", 1],';
        } elseif ($this->params['top']) {
            $parseStr .= '["article.is_top", "=", 1],';
        } elseif ($this->params['hot']) {
            $parseStr .= '["article.is_hot", "=", 1],';
        }

        if ($this->params['tid']) {
            $parseStr .= '["article.type_id", "=", ' . $this->params['tid'] . '],';
        }
        $parseStr .= '
            ])
            ->order("' . $sort_order . '")
            ->paginate([
                "list_rows" => ' . $this->params['limit'] . ',
                "path" => "javascript:paging([PAGE]);",
            ]);
            if ($result):
                $list = $result->toArray();
                $list["render"] = $result->render();
                foreach ($list["data"] as $key => $value):
                    $value["cat_url"] = url("list/" . $value["category_id"]);
                    $value["url"] = url("details/" . $value["category_id"] . "/" . $value["id"]);
                    $value["flag"] = \app\common\library\Base64::flag($value["category_id"] . $value["id"], 7);
                    $value["thumb"] = \app\common\library\Image::path($value["thumb"], 300);
                    $value["update_time"] = date("' . $this->params['date_format'] . '", (int) $value["update_time"]);
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
                    foreach ($fields as $val):
                        $value[$val["fields_name"]] = $val["data"];
                    endforeach;

                    $value["tags"] = \app\common\model\ArticleTags::view("article_tags", ["tags_id"])
                        ->view("tags tags", ["name"], "tags.id=article_tags.tags_id")
                        ->where([
                            ["article_tags.article_id", "=", $value["id"]],
                        ])
                        ->select()
                        ->toArray();
                    foreach ($value["tags"] as $k => $tag):
                        $tag["url"] = url("tags/" . $tag["tags_id"]);
                        $value["tags"][$k] = $tag;
                    endforeach;

                    $list["data"][$key] = $value;
                endforeach;
                cache("' . $cache_key . '", $list);
            endif;
        endif;
        if (!empty($list)):
            $total = $list["total"];
            $per_page = $list["per_page"];
            $current_page = $list["current_page"];
            $last_page = $list["last_page"];
            $page = $list["render"];
            $items = $list["data"];
            foreach ($items as $key => $item): ?>';

        return $parseStr;
    }

    public function end(): string
    {
        return '<?php endforeach; endif; ?>';
    }
}
