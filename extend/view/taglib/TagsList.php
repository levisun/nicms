<?php

declare(strict_types=1);

namespace view\taglib;

use view\Taglib;

class TagsList extends Taglib
{

    public function closed(): string
    {
        $this->params['cid'] = !empty($this->params['cid']) ? (int) $this->params['cid'] : request()->param('cid/d', 0);
        $this->params['com'] = !empty($this->params['com']) ? (int) $this->params['com'] : 0;
        $this->params['top'] = !empty($this->params['top']) ? (int) $this->params['top'] : 0;
        $this->params['hot'] = !empty($this->params['hot']) ? (int) $this->params['hot'] : 0;
        $this->params['tid'] = !empty($this->params['tid']) ? (int) $this->params['tid'] : 0;
        $this->params['sort'] = !empty($this->params['sort']) ? $this->params['sort'] : null;
        $this->params['limit'] = !empty($this->params['limit']) ? (int) $this->params['limit'] : 10;
        $this->params['page'] = !empty($this->params['page']) ? (int) $this->params['page'] : 1;
        $this->params['date_format'] = !empty($this->params['date_format']) ? $this->params['date_format'] : 'Y-m-d';


        $map = [
            ['article.is_pass', '=', '1'],
            ['article.delete_time', '=', '0'],
            ['article.show_time', '<', time()],
            ['article.lang', '=', app('lang')->getLangSet()]
        ];
        if ($this->params['cid']) {
            $map[] = ['article.category_id', 'in', app('\app\cms\logic\article\Category')->child($this->params['cid'])];
        }

        if ($this->params['com']) {
            $map[] = ['article.is_com', '=', 1];
        } elseif ($this->params['top']) {
            $map[] = ['article.is_top', '=', 1];
        } elseif ($this->params['hot']) {
            $map[] = ['article.is_hot', '=', 1];
        }

        if ($this->params['tid']) {
            $map[] = ['article.type_id', '=', $this->params['tid']];
        }

        if ($this->params['sort']) {
            $sort_order = "article.' . $this->params['sort'] . '";
        } else {
            $sort_order = "article.is_top DESC, article.is_hot DESC , article.is_com DESC, article.sort_order DESC, article.update_time DESC";
        }

        $cache_key = md5('tagslib tagslist::article list' . $this->params['cid'] . $this->params['com'] . $this->params['top'] . $this->params['hot'] . $this->params['tid'] . $this->params['sort'] . $this->params['limit'] . $this->params['page'] . $this->params['date_format']);

        $parseStr  = '';
        $parseStr .= 'if (!cache("?' . $cache_key . '") || !$list = cache("' . $cache_key . '")):';
        $parseStr .= '$result = \app\common\model\Article::view("article", ["id", "category_id", "title", "keywords", "description", "username", "access_id", "hits", "update_time"])
        ->view("category", ["name" => "cat_name"], "category.id=article.category_id")
        ->view("model", ["id" => "model_id", "name" => "model_name"], "model.id=category.model_id and model.id<=3")
        ->view("article_content", ["thumb"], "article_content.article_id=article.id", "LEFT")
        ->view("type", ["id" => "type_id", "name" => "type_name"], "type.id=article.type_id", "LEFT")
        ->view("level", ["name" => "access_name"], "level.id=article.access_id", "LEFT")
        ->view("user", ["username" => "author"], "user.id=article.user_id", "LEFT")
        ->where(' . var_export($map, true) . ')
        ->order(' . $sort_order . ')
        ->paginate([
            "list_rows" => ' . $this->params['limit'] . ',
            "path" => "javascript:paging([PAGE]);",
        ]);';

        echo $parseStr;
        print_r($this->params);
        die();

        return $parseStr;
    }

    public function end()
    {
        return '<?php endif; ?>';
    }
}
