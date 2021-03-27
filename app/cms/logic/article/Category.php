<?php

/**
 *
 * API接口层
 * 文章栏目列表
 *
 * @package   NICMS
 * @category  app\cms\logic\article
 * @author    失眠小枕头 [312630173@qq.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace app\cms\logic\article;

use app\common\controller\BaseLogic;
use app\common\library\tools\File;
use app\common\library\Base64;
use app\common\model\Article as ModelArticle;
use app\common\model\Category as ModelCategory;
use app\common\model\ArticleTags as ModelArticleTags;
use app\common\model\FieldsExtend as ModelFieldsExtend;

class Category extends BaseLogic
{

    /**
     * 查询列表
     * @access public
     * @return array
     */
    public function query(): array
    {
        $map = [];

        // 安栏目查询,为空查询所有
        if ($category_id = $this->request->param('category_id', 0, '\app\common\library\Base64::url62decode')) {
            $map[] = ['article.category_id', 'in', $this->child($category_id)];
        }

        // 推荐置顶最热,三选一
        if ($attribute = $this->request->param('attribute/d', 0, 'abs')) {
            $map[] = ['article.attribute', '=', $attribute];
        }

        // 安类别查询,为空查询所有
        if ($type_id = $this->request->param('type_id/d', 0, 'abs')) {
            $map[] = ['article.type_id', '=', $type_id];
        }

        // 排序,为空依次安置顶,最热,推荐,自定义顺序,最新发布时间排序
        if ($sort_order = $this->request->param('sort')) {
            $sort_order = 'article.' . $sort_order;
        } else {
            $sort_order = 'article.attribute DESC, article.sort_order DESC, article.update_time DESC';
        }

        $date_format = $this->request->param('date_format', 'Y-m-d');

        $query_limit = $this->request->param('limit/d', 20, 'abs');
        $query_limit = 100 > $query_limit && 10 < $query_limit ? intval($query_limit / 10) * 10 : 20;

        $query_page = $this->request->param('page/d', 1, 'abs');
        if ($query_page > $this->ERPCache()) {
            return [
                'debug' => false,
                'cache' => true,
                'msg'   => 'error',
            ];
        }

        if (!$this->cache->has($this->getCacheKey()) || !$list = $this->cache->get($this->getCacheKey())) {
            $result = ModelArticle::view('article', ['id', 'category_id', 'title', 'keywords', 'description', 'thumb', 'username', 'access_id', 'hits', 'update_time'])
                ->view('category', ['name' => 'cat_name'], 'category.id=article.category_id')
                ->view('model', ['id' => 'model_id', 'name' => 'model_name'], 'model.id=category.model_id and model.id<=3')
                ->view('type', ['id' => 'type_id', 'name' => 'type_name'], 'type.id=article.type_id', 'LEFT')
                ->view('level', ['name' => 'access_name'], 'level.id=article.access_id', 'LEFT')
                ->view('user', ['username' => 'author'], 'user.id=article.user_id', 'LEFT')
                ->where('article.is_pass', '=', '1')
                ->where('article.delete_time', '=', '0')
                ->where('article.access_id', '=', $this->userRoleId)
                ->where('article.show_time', '<', time())
                ->where('article.lang', '=', $this->lang->getLangSet())
                ->where($map)
                ->order($sort_order)
                ->paginate([
                    'list_rows' => $query_limit,
                    'path' => 'javascript:paging([PAGE]);',
                ], true);

            if ($result && $list = $result->toArray()) {
                $this->ERPCache($query_page);

                $list['render'] = $result->render();

                foreach ($list['data'] as $key => $value) {
                    // 栏目链接
                    $value['cat_url'] = url('list/' . Base64::url62encode($value['category_id']));
                    // 文章链接
                    $value['url'] = url('details/' . Base64::url62encode($value['category_id']) . '/' . Base64::url62encode($value['id']));
                    // 标识符
                    $value['flag'] = Base64::flag($value['category_id'] . $value['id'], 7);
                    // 缩略图
                    $value['thumb'] = File::imgUrl($value['thumb']);
                    // 时间格式
                    $value['update_time'] = date($date_format, (int) $value['update_time']);
                    // 作者
                    $value['author'] = $value['author'] ?: $value['username'];
                    unset($value['username']);

                    // 附加字段数据
                    $fields = ModelFieldsExtend::view('fields_extend', ['data'])
                        ->view('fields', ['name' => 'fields_name'], 'fields.id=fields_extend.fields_id')
                        ->where('fields_extend.article_id', '=', $value['id'])
                        ->where('fields.category_id', '=', $value['category_id'])
                        ->select()
                        ->toArray();
                    foreach ($fields as $val) {
                        $value[$val['fields_name']] = $val['data'];
                    }

                    // 标签
                    $value['tags'] = ModelArticleTags::view('article_tags', ['tags_id'])
                        ->view('tags tags', ['name'], 'tags.id=article_tags.tags_id')
                        ->where('article_tags.article_id', '=', $value['id'])
                        ->select()
                        ->toArray();
                    foreach ($value['tags'] as $k => $tag) {
                        $tag['url'] = url('tags/' . Base64::url62encode($tag['tags_id']));
                        $value['tags'][$k] = $tag;
                    }

                    $list['data'][$key] = $value;
                }

                $this->cache->tag('cms article list' . $category_id)->set($this->getCacheKey(), $list);
            }
        }

        return [
            'debug' => false,
            'cache' => isset($list) ? true : false,
            'msg'   => isset($list) ? 'category' : 'error',
            'data'  => isset($list) ? [
                'list'         => $list['data'],
                'per_page'     => $list['per_page'],
                'current_page' => $list['current_page'],
                'page'         => $list['render'],
            ] : []
        ];
    }

    /**
     * 查询当前栏目下的所有子栏目
     * @access public
     * @param  int $_id
     * @return array
     */
    public function child(int $_id): array
    {
        $category = [];

        $result = ModelCategory::field('id')
            ->where('pid', '=', $_id)
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
