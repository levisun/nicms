<?php

/**
 *
 * API接口层
 * 文章搜索
 *
 * @package   NICMS
 * @category  app\cms\logic\search
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace app\cms\logic\article;

use app\common\controller\BaseLogic;
use app\common\library\Base64;
use app\common\library\Image;
use app\common\model\Article as ModelArticle;
use app\common\model\Category as ModelCategory;
use app\common\model\ArticleTags as ModelArticleTags;
use app\common\model\FieldsExtend as ModelFieldsExtend;

class Search extends BaseLogic
{

    /**
     * 查询列表
     * @access public
     * @param
     * @return array
     */
    public function query(): array
    {
        $map = [
            ['article.is_pass', '=', '1'],
            ['article.delete_time', '=', '0'],
            ['article.show_time', '<', time()],
            ['article.lang', '=', $this->lang->getLangSet()]
        ];

        // 安栏目查询,为空查询所有
        if ($category_id = $this->request->param('cid/d', 0, 'abs')) {
            $map[] = ['article.category_id', 'in', $this->child($category_id)];
        }

        // 推荐置顶最热,三选一
        if ($com = $this->request->param('com/d', 0, 'abs')) {
            $map[] = ['article.is_com', '=', '1'];
        } elseif ($top = $this->request->param('top/d', 0, 'abs')) {
            $map[] = ['article.is_top', '=', '1'];
        } elseif ($hot = $this->request->param('hot/d', 0, 'abs')) {
            $map[] = ['article.is_hot', '=', '1'];
        }

        // 安类别查询,为空查询所有
        if ($type_id = $this->request->param('tid/d', 0, 'abs')) {
            $map[] = ['article.type_id', '=', $type_id];
        }

        // 搜索
        if ($search_key = $this->request->param('key')) {
            // 搜索5个词
            $search_key = words($search_key, 5);
            if ($search_key = implode('|', $search_key)) {
                $map[] = ['article.title', 'regexp', $search_key];
            }
        }

        // 排序,为空依次安置顶,最热,推荐,自定义顺序,最新发布时间排序
        if ($sort_order = $this->request->param('sort')) {
            $sort_order = 'article.' . $sort_order;
        } else {
            $sort_order = 'article.is_top DESC, article.is_hot DESC , article.is_com DESC, article.sort_order DESC, article.update_time DESC';
        }

        $query_limit = $this->request->param('limit/d', 20, 'abs');
        $query_page = $this->request->param('page/d', 1, 'abs');
        $date_format = $this->request->param('date_format', 'Y-m-d');

        $cache_key = 'article list' . $category_id .
            $com . $top . $hot . $type_id . $sort_order . $search_key .
            $query_limit . $query_page . $date_format;
        $cache_key = md5($cache_key);

        if (!$this->cache->has($cache_key) || !$list = $this->cache->get($cache_key)) {
            $result = ModelArticle::view('article', ['id', 'category_id', 'title', 'keywords', 'description', 'username', 'access_id', 'hits', 'update_time'])
                ->view('category', ['name' => 'cat_name'], 'category.id=article.category_id')
                ->view('model', ['id' => 'model_id', 'name' => 'model_name'], 'model.id=category.model_id and model.id<=3')
                ->view('article_content', ['thumb'], 'article_content.article_id=article.id', 'LEFT')
                ->view('type', ['id' => 'type_id', 'name' => 'type_name'], 'type.id=article.type_id', 'LEFT')
                ->view('level', ['name' => 'access_name'], 'level.id=article.access_id', 'LEFT')
                ->view('user', ['username' => 'author'], 'user.id=article.user_id', 'LEFT')
                ->where($map)
                ->order($sort_order)
                ->paginate([
                    'list_rows' => $query_limit,
                    'path' => 'javascript:paging([PAGE]);',
                ]);

            $list = $result->toArray();
            $list['total'] = number_format($list['total']);
            $list['render'] = $result->render();
            $list['search_key'] = $search_key ?: '';
            foreach ($list['data'] as $key => $value) {
                // 栏目链接
                $value['cat_url'] = url('list/' . $value['category_id']);
                // 文章链接
                $value['url'] = url('details/' . $value['category_id'] . '/' . $value['id']);
                // 标识符
                $value['flag'] = Base64::flag($value['category_id'] . $value['id'], 7);
                // 缩略图
                $value['thumb'] = Image::path($value['thumb']);
                // 时间格式
                $value['update_time'] = date($date_format, (int) $value['update_time']);
                // 作者
                $value['author'] = $value['author'] ?: $value['username'];
                unset($value['username']);

                // 附加字段数据
                $fields = ModelFieldsExtend::view('fields_extend', ['data'])
                    ->view('fields', ['name' => 'fields_name'], 'fields.id=fields_extend.fields_id')
                    ->where([
                        ['fields_extend.article_id', '=', $value['id']],
                        ['fields.category_id', '=', $value['category_id']],
                    ])
                    ->select()
                    ->toArray();
                foreach ($fields as $val) {
                    $value[$val['fields_name']] = $val['data'];
                }

                // 标签
                $value['tags'] = ModelArticleTags::view('article_tags', ['tags_id'])
                    ->view('tags tags', ['name'], 'tags.id=article_tags.tags_id')
                    ->where([
                        ['article_tags.article_id', '=', $value['id']],
                    ])
                    ->select()
                    ->toArray();
                foreach ($value['tags'] as $k => $tag) {
                    $tag['url'] = url('tags/' . $tag['tags_id']);
                    $value['tags'][$k] = $tag;
                }

                $list['data'][$key] = $value;
            }

            $this->cache->tag(['cms', 'cms article list' . $category_id])->set($cache_key, $list);
        }

        return [
            'debug' => false,
            'cache' => isset($list) ? 28800    : false,
            'msg'   => isset($list) ? 'search' : 'error',
            'data'  => isset($list) ? [
                'list'         => $list['data'],
                'total'        => $list['total'],
                'per_page'     => $list['per_page'],
                'current_page' => $list['current_page'],
                'last_page'    => $list['last_page'],
                'page'         => $list['render'],
                'search_key'   => $list['search_key'],
            ] : []
        ];
    }

    /**
     * 查询当前栏目下的所有子栏目
     * @access protected
     * @return array
     */
    private function child(int $_id): array
    {
        $category = [];

        $result = ModelCategory::field('id')
            ->where([
                ['pid', '=', $_id]
            ])
            ->select();

        $result = $result ? $result->toArray() : [];
        foreach ($result as $value) {
            // 递归查询子类
            $child = $this->child((int) $value['id']);
            $category = array_merge($category, $child);
        }

        // 追加当前栏目ID
        $category[] = $_id;
        // 去重
        $category = array_unique($category);

        return $category;
    }
}
