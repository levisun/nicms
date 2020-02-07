<?php

/**
 *
 * API接口层
 * 文章基础类
 *
 * @package   NICMS
 * @category  app\cms\logic
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace app\cms\logic\article;

use app\common\controller\BaseLogic;
use app\common\library\Base64;
use app\common\library\Canvas;
use app\common\library\DataFilter;
use app\common\library\Download;
use app\common\model\Article as ModelArticle;
use app\common\model\ArticleTags as ModelArticleTags;
use app\common\model\FieldsExtend as ModelFieldsExtend;

class ArticleBase extends BaseLogic
{

    /**
     * 查询列表
     * @access protected
     * @return array|false
     */
    protected function ArticleList()
    {
        $map = [
            ['article.is_pass', '=', '1'],
            ['article.delete_time', '=', '0'],
            ['article.show_time', '<', time()],
            ['article.lang', '=', $this->lang->getLangSet()]
        ];

        // 安栏目查询,为空查询所有
        if ($category_id = $this->request->param('cid/d', 0)) {
            $map[] = ['article.category_id', '=', $category_id];
        }

        // 推荐置顶最热,三选一
        if ($com = $this->request->param('com/d', 0)) {
            $map[] = ['article.is_com', '=', '1'];
        } elseif ($top = $this->request->param('top/d', 0)) {
            $map[] = ['article.is_top', '=', '1'];
        } elseif ($hot = $this->request->param('hot/d', 0)) {
            $map[] = ['article.is_hot', '=', '1'];
        }

        // 安类别查询,为空查询所有
        if ($type_id = $this->request->param('tid/d', 0)) {
            $map[] = ['article.type_id', '=', $type_id];
        }

        // 搜索
        if ($search_key = $this->request->param('key')) {
            $search_key = DataFilter::word($search_key, 3);
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

        $query_limit = $this->request->param('limit/d', 10);
        $query_page = $this->request->param('page/d', 1);
        $date_format = $this->request->param('date_format', 'Y-m-d');

        $cache_key = 'article list' . date('Ymd') . $category_id .
            $com . $top . $hot . $type_id . $sort_order . $search_key .
            $query_limit . $query_page . $date_format;
        $cache_key = md5($cache_key);

        if (!$this->cache->has($cache_key) || !$list = $this->cache->get($cache_key)) {
            $result = (new ModelArticle)
                ->view('article', ['id', 'category_id', 'title', 'keywords', 'description', 'username', 'access_id', 'hits', 'update_time'])
                ->view('category', ['name' => 'cat_name'], 'category.id=article.category_id')
                ->view('model', ['id' => 'model_id', 'name' => 'model_name'], 'model.id=category.model_id')
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

            if ($result) {
                $list = $result->toArray();
                $list['render'] = $result->render();
                $list['search_key'] = $search_key ? : '';

                foreach ($list['data'] as $key => $value) {
                    // 栏目链接
                    $value['cat_url'] = url('list/' . $value['category_id']);
                    // 文章链接
                    $value['url'] = url('details/' . $value['category_id'] . '/' . $value['id']);
                    // 标识符
                    $value['flag'] = Base64::flag($value['category_id'] . $value['id'], 7);
                    // 缩略图
                    $value['thumb'] = (new Canvas)->image($value['thumb'], 300);
                    // 时间格式
                    $value['update_time'] = date($date_format, (int) $value['update_time']);
                    // 作者
                    $value['author'] = $value['author'] ?: $value['username'];
                    unset($value['username']);

                    // 附加字段数据
                    $fields = (new ModelFieldsExtend)
                        ->view('fields_extend', ['data'])
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
                    $value['tags'] = (new ModelArticleTags)
                        ->view('article_tags', ['tags_id'])
                        ->view('tags tags', ['name'], 'tags.id=article_tags.tags_id')
                        ->where([
                            ['article_tags.article_id', '=', $value['id']],
                        ])
                        ->select()
                        ->toArray();
                    foreach ($value['tags'] as $k => $tag) {
                        $tag['url'] = url('tags/' . $tag['tags_id']);
                        $result['tags'][$k] = $tag;
                    }

                    $list['data'][$key] = $value;
                }

                $this->cache->tag([
                    'cms',
                    'cms article list' . $category_id
                ])->set($cache_key, $list);
            }
        }

        return isset($list) ? $list : false;
    }

    /**
     * 查询内容
     * @access protected
     * @return array|false
     */
    protected function ArticleDetails()
    {
        if ($id = $this->request->param('id/d')) {
            $map = [
                ['article.id', '=', $id],
                ['article.is_pass', '=', '1'],
                ['article.delete_time', '=', '0'],
                ['article.show_time', '<', time()],
            ];
            $cache_key = md5('article details' . $id);
            if (!$this->cache->has($cache_key) || !$result = $this->cache->get($cache_key)) {
                $result = (new ModelArticle)
                    ->view('article', ['id', 'category_id', 'title', 'keywords', 'description', 'username', 'access_id', 'hits', 'update_time'])
                    ->view('category', ['name' => 'cat_name'], 'category.id=article.category_id')
                    ->view('model', ['id' => 'model_id', 'name' => 'model_name', 'table_name'], 'model.id=category.model_id')
                    ->view('type', ['id' => 'type_id', 'name' => 'type_name'], 'type.id=article.type_id', 'LEFT')
                    ->view('level', ['name' => 'access_name'], 'level.id=article.access_id', 'LEFT')
                    ->view('user', ['username' => 'author'], 'user.id=article.user_id', 'LEFT')
                    ->where($map)
                    ->find();

                if ($result && $result = $result->toArray()) {
                    // 栏目链接
                    $result['cat_url'] = url('list/' . $result['category_id']);
                    // 文章链接
                    $result['url'] = url('details/' . $result['category_id'] . '/' . $result['id']);
                    // 标识符
                    $result['flag'] = Base64::flag($result['category_id'] . $result['id'], 7);
                    // 时间格式
                    $date_format = $this->request->param('date_format', 'Y-m-d');
                    $result['update_time'] = date($date_format, (int) $result['update_time']);
                    // 作者
                    $result['author'] = $result['author'] ?: $result['username'];
                    unset($result['username']);

                    // 上一篇 下一篇
                    if ($result['model_id'] <= 3) {
                        $result['next'] = $this->next((int) $result['id']);
                        $result['prev'] = $this->prev((int) $result['id']);
                    }

                    // 附加字段数据
                    $fields = (new ModelFieldsExtend)
                        ->view('fields_extend', ['data'])
                        ->view('fields', ['name' => 'fields_name'], 'fields.id=fields_extend.fields_id')
                        ->where([
                            ['fields_extend.article_id', '=', $result['id']],
                            ['fields.category_id', '=', $result['category_id']],
                        ])
                        ->select()
                        ->toArray();
                    foreach ($fields as $value) {
                        $fields[$value['fields_name']] = $value['data'];
                    }

                    // 标签
                    $result['tags'] = (new ModelArticleTags)
                        ->view('article_tags', ['tags_id'])
                        ->view('tags', ['name'], 'tags.id=article_tags.tags_id')
                        ->where([
                            ['article_tags.article_id', '=', $result['id']],
                        ])
                        ->select()
                        ->toArray();
                    foreach ($result['tags'] as $key => $tag) {
                        $tag['url'] = url('tags/' . $tag['tags_id']);
                        $result['tags'][$key] = $tag;
                    }

                    // table_name
                    $model = \think\helper\Str::studly($result['table_name']);
                    unset($result['table_name']);
                    $content = $this->app->make('\app\common\model\\' . $model);
                    $content = $content->where([
                        ['article_id', '=', $id]
                    ])->find();
                    if ($content && $content = $content->toArray()) {
                        unset($content['id'], $content['article_id']);
                        foreach ($content as $key => $value) {
                            switch ($key) {
                                    // 缩略图
                                case 'thumb':
                                    $result[$key] = (new Canvas)->image($value);
                                    break;

                                    // 图片
                                case 'image_url':
                                    $value = unserialize($value);
                                    foreach ($value as $v) {
                                        $result[$key][] = $v ? (new Canvas)->image($v) : '';
                                    }
                                    $result[$key] = array_unique($result[$key]);
                                    $result[$key] = array_filter($result[$key]);
                                    break;

                                    // 文章内容
                                case 'content':
                                    $value = DataFilter::decode($value);
                                    $value = preg_replace_callback('/(src=")([a-zA-Z0-9&=#,_:?.\/]+)(")/si', function ($matches) {
                                        return $matches[2]
                                            ? 'src="' . (new Canvas)->image($matches[2]) . '"'
                                            : '';
                                    }, $value);
                                    $result[$key] = $value;
                                    break;

                                    // 下载文件
                                case 'file_url':
                                    $result[$key] = $value ? Download::getUrl($value) : '';
                                    break;

                                default:
                                    $result[$key] = $value;
                                    break;
                            }
                        }
                    }

                    $this->cache->tag([
                        'cms'
                    ])->set($cache_key, $result);
                }
            }
        }

        return $result ? $result : false;
    }

    /**
     * 更新浏览量
     * @access public
     * @return array
     */
    public function hits(): array
    {
        if ($id = $this->request->param('id/d')) {
            $map = [
                ['id', '=', $id],
            ];

            // 更新浏览数
            (new ModelArticle)->where($map)
                ->inc('hits', 1, 60)
                ->update();

            $result = (new ModelArticle)
                ->where($map)
                ->value('hits', 0);
        }

        return [
            'debug'  => false,
            'cache'  => false,
            'msg'    => isset($result) ? 'article hits' : 'article hits error',
            'data'   => isset($result) ? ['hits' => $result] : []
        ];
    }

    /**
     * 下一篇
     * @access protected
     * @param  int      $_id
     * @return array
     */
    protected function next(int $_id)
    {
        $next_id = (new ModelArticle)
            ->where([
                ['is_pass', '=', 1],
                ['show_time', '<', time()],
                ['id', '>', $_id]
            ])
            ->order('is_top, is_hot, is_com, sort_order DESC, update_time DESC')
            ->min('id');

        $result = (new ModelArticle)
            ->view('article', ['id', 'category_id', 'title', 'keywords', 'description', 'access_id', 'update_time'])
            ->view('category', ['name' => 'cat_name'], 'category.id=article.category_id')
            ->view('model', ['name' => 'model_name'], 'model.id=category.model_id')
            ->where([
                ['article.is_pass', '=', 1],
                ['article.show_time', '<', time()],
                ['article.id', '=', $next_id]
            ])
            ->find();

        if (null !== $result && $result = $result->toArray()) {
            $result['flag'] = Base64::flag($result['category_id'] . $result['id'], 7);
            $result['url'] = url('details/' . $result['category_id'] . '/' . $result['id']);
            $result['cat_url'] = url('list/' . $result['category_id']);
        }

        return $result;
    }

    /**
     * 上一篇
     * @access protected
     * @param  int      $_id
     * @return array
     */
    protected function prev(int $_id)
    {
        $prev_id = (new ModelArticle)
            ->where([
                ['is_pass', '=', 1],
                ['show_time', '<', time()],
                ['id', '<', $_id]
            ])
            ->order('is_top, is_hot, is_com, sort_order DESC, update_time DESC')
            ->max('id');

        $result = (new ModelArticle)
            ->view('article', ['id', 'category_id', 'title', 'keywords', 'description', 'access_id', 'update_time'])
            ->view('category', ['name' => 'cat_name'], 'category.id=article.category_id')
            ->view('model', ['name' => 'model_name'], 'model.id=category.model_id')
            ->where([
                ['article.is_pass', '=', 1],
                ['article.show_time', '<', time()],
                ['article.id', '=', $prev_id]
            ])
            ->find();

        if (null !== $result && $result = $result->toArray()) {
            $result['flag'] = Base64::flag($result['category_id'] . $result['id'], 7);
            $result['url'] = url('details/' . $result['category_id'] . '/' . $result['id']);
            $result['cat_url'] = url('list/' . $result['category_id']);
        }

        return $result;
    }
}
