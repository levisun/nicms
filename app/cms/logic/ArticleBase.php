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

namespace app\cms\logic;

use app\common\controller\BaseLogic;
use app\common\library\Base64;
use app\common\library\DataFilter;
use app\common\model\Article as ModelArticle;
use app\common\model\ArticleExtend as ArticleExtend;
use app\common\model\ArticleTags as ModelArticleTags;

class BaseArticle extends BaseLogic
{

    /**
     * 查询列表
     * @access protected
     * @param
     * @return array
     */
    protected function ArticleList()
    {
        if ($category_id = $this->request->param('cid/d')) {
            $map = [
                ['article.category_id', '=', $category_id],
                ['article.is_pass', '=', '1'],
                ['article.show_time', '<=', time()],
                ['article.lang', '=', $this->lang->getLangSet()]
            ];

            if ($com = $this->request->param('com/d', 0)) {
                $map[] = ['article.is_com', '=', '1'];
            } elseif ($top = $this->request->param('top/d', 0)) {
                $map[] = ['article.is_top', '=', '1'];
            } elseif ($hot = $this->request->param('hot/d', 0)) {
                $map[] = ['article.is_hot', '=', '1'];
            }

            if ($type_id = $this->request->param('tid/d', 0)) {
                $map[] = ['article.type_id', '=', $type_id];
            }

            $query_limit = $this->request->param('limit/d', 10);
            $query_page = $this->request->param('page/d', 1);
            $date_format = $this->request->param('date_format', 'Y-m-d');

            $cache_key = md5(__METHOD__ . date('Ymd') . $category_id . $com . $top . $hot . $type_id . $query_limit . $query_page . $date_format);
            if (!$this->cache->has($cache_key) || !$list = $this->cache->get($cache_key)) {
                $result = (new ModelArticle)
                    ->view('article', ['id', 'category_id', 'title', 'keywords', 'description', 'access_id', 'update_time'])
                    ->view('category', ['name' => 'cat_name'], 'category.id=article.category_id')
                    ->view('model', ['name' => 'model_name'], 'model.id=category.model_id')
                    ->view('article_content', ['thumb'], 'article_content.article_id=article.id', 'LEFT')
                    ->view('type', ['id' => 'type_id', 'name' => 'type_name'], 'type.id=article.type_id', 'LEFT')
                    ->view('level', ['name' => 'level_name'], 'level.id=article.access_id', 'LEFT')
                    ->where($map)
                    ->order('article.is_top DESC, article.is_hot DESC , article.is_com DESC, article.sort_order DESC, article.id DESC')
                    ->paginate([
                        'list_rows'=> $query_limit,
                        'path' => 'javascript:paging([PAGE]);',
                    ]);

                if ($result) {
                    $list = $result->toArray();
                    $list['render'] = $result->render();

                    foreach ($list['data'] as $key => $value) {
                        $value['flag'] = Base64::flag($value['category_id'] . $value['id'], 7);
                        $value['update_time'] = date($date_format, strtotime($value['update_time']));

                        $value['thumb_original'] = get_img_url($value['thumb'], 0);
                        $value['thumb'] = get_img_url($value['thumb'], 300);

                        $value['cat_url'] = url('list/' . $value['model_name'] . '/' . $value['category_id']);
                        $value['url'] = url('details/' . $value['model_name'] . '/' . $value['category_id'] . '/' . $value['id']);

                        // 附加字段数据
                        $fields = (new ArticleExtend)
                            ->view('article_extend extend', ['data'])
                            ->view('fields fields', ['name' => 'fields_name'], 'fields.id=extend.fields_id')
                            ->where([
                                ['extend.article_id', '=', $value['id']],
                            ])
                            ->select()
                            ->toArray();
                        foreach ($fields as $val) {
                            $value[$val['fields_name']] = $val['data'];
                        }

                        // 标签
                        $value['tags'] = (new ModelArticleTags)
                            ->view('article_tags article', ['tags_id'])
                            ->view('tags tags', ['name'], 'tags.id=article.tags_id')
                            ->where([
                                ['article.article_id', '=', $value['id']],
                            ])
                            ->select()
                            ->toArray();

                        $list['data'][$key] = $value;
                    }

                    $this->cache->tag('CMS LIST ' . $category_id)->set($cache_key, $list);
                }
            }
        }

        return isset($list) ? $list : false;
    }

    /**
     * 查询内容
     * @access protected
     * @param
     * @return array
     */
    protected function ArticleDetails(): array
    {
        if ($id = $this->request->param('id/d')) {
            $map = [
                ['article.id', '=', $id],
                ['article.is_pass', '=', '1'],
                ['article.show_time', '<=', time()],
                ['article.lang', '=', $this->lang->getLangSet()]
            ];
            $cache_key = md5('CMS DETAILS ' . $id);
            if (!$this->cache->has($cache_key) || !$result = $this->cache->get($cache_key)) {
                $result = (new ModelArticle)
                    ->view('article', ['id', 'category_id', 'title', 'keywords', 'description', 'access_id', 'update_time'])
                    ->view('category', ['name' => 'cat_name'], 'category.id=article.category_id')
                    ->view('model', ['name' => 'model_name'], 'model.id=category.model_id')
                    ->view('article_content', ['thumb', 'content'], 'article_content.article_id=article.id', 'LEFT')
                    ->view('type', ['id' => 'type_id', 'name' => 'type_name'], 'type.id=article.type_id', 'LEFT')
                    ->view('level', ['name' => 'level_name'], 'level.id=article.access_id', 'LEFT')
                    ->where($map)
                    ->find()
                    ->toArray();

                if ($result) {
                    $result['flag'] = Base64::flag($result['category_id'] . $result['id'], 7);
                    $date_format = $this->request->param('date_format', 'Y-m-d');
                    $result['update_time'] = date($date_format, strtotime($result['update_time']));

                    $result['thumb'] = get_img_url($result['thumb'], 300);

                    $result['content'] = htmlspecialchars_decode($result['content']);
                    $result['content'] = DataFilter::string($result['content']);
                    $result['content'] = preg_replace_callback([
                        '/(style=["|\'])(.*?)(["|\'])/si',
                        '/<\/?h[1-4]{1}(.*?)>/si'
                    ], function () {
                        return '';
                    }, $result['content']);

                    $result['content'] =
                        preg_replace_callback('/(src=["|\'])(.*?)(["|\'])/si', function ($matches) {
                            $thumb = get_img_url($matches[2], 300);
                            return 'src="' . $thumb . '" original="' . get_img_url($matches[2], 0) . '"';
                        }, $result['content']);

                    $result['url'] = url('details/' . $result['model_name'] . '/' . $result['category_id'] . '/' . $result['id']);
                    $result['cat_url'] = url('list/' . $result['model_name'] . '/' . $result['category_id']);

                    // 上一篇 下一篇
                    $result['next'] = $this->next($result['id']);
                    $result['prev'] = $this->prev($result['id']);

                    // 附加字段数据
                    $fields = (new ArticleExtend)
                        ->view('article_extend extend', ['data'])
                        ->view('fields fields', ['name' => 'fields_name'], 'fields.id=extend.fields_id')
                        ->where([
                            ['extend.article_id', '=', $result['id']],
                        ])
                        ->select()
                        ->toArray();
                    foreach ($fields as $val) {
                        $result[$val['fields_name']] = $val['data'];
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

                    $this->cache->set($cache_key, $result);
                }
            }
        }

        return !empty($result) ? $result : false;
    }

    /**
     * 更新浏览量
     * @access public
     * @param
     * @return array
     */
    public function hits(): array
    {
        if ($id = $this->request->param('id/d')) {
            $map = [
                ['id', '=', $id],
                ['is_pass', '=', '1'],
                ['show_time', '<=', time()],
                ['lang', '=', $this->lang->getLangSet()]
            ];

            // 更新浏览数
            (new ModelArticle)->where($map)
                ->inc('hits', 1, 60)
                ->update();

            $result = (new ModelArticle)
                ->where($map)
                ->value('hits');
        }

        return [
            'debug'  => false,
            'cache'  => isset($result) ? true : false,
            'expire' => 60,
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
                ['show_time', '<=', time()],
                ['id', '>', $_id]
            ])
            ->order('is_top, is_hot, is_com, sort_order DESC, id DESC')
            ->min('id');

        $result = (new ModelArticle)
            ->view('article', ['id', 'category_id', 'title', 'keywords', 'description', 'access_id', 'update_time'])
            ->view('category', ['name' => 'cat_name'], 'category.id=article.category_id')
            ->view('model', ['name' => 'model_name'], 'model.id=category.model_id')
            ->where([
                ['article.is_pass', '=', 1],
                ['article.show_time', '<=', time()],
                ['article.id', '=', $next_id]
            ])
            ->find();

        if ($result) {
            $result['flag'] = Base64::flag($result['category_id'] . $result['id'], 7);
            $result['url'] = url('details/' . $result['model_name'] . '/' . $result['category_id'] . '/' . $result['id']);
            $result['cat_url'] = url('list/' . $result['model_name'] . '/' . $result['category_id']);
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
                ['show_time', '<=', time()],
                ['id', '<', $_id]
            ])
            ->order('is_top, is_hot, is_com, sort_order DESC, id DESC')
            ->max('id');

        $result = (new ModelArticle)
            ->view('article', ['id', 'category_id', 'title', 'keywords', 'description', 'access_id', 'update_time'])
            ->view('category', ['name' => 'cat_name'], 'category.id=article.category_id')
            ->view('model', ['name' => 'model_name'], 'model.id=category.model_id')
            ->where([
                ['article.is_pass', '=', 1],
                ['article.show_time', '<=', time()],
                ['article.id', '=', $prev_id]
            ])
            ->find();

        if ($result) {
            $result['flag'] = Base64::flag($result['category_id'] . $result['id'], 7);
            $result['url'] = url('details/' . $result['model_name'] . '/' . $result['category_id'] . '/' . $result['id']);
            $result['cat_url'] = url('list/' . $result['model_name'] . '/' . $result['category_id']);
        }

        return $result;
    }
}
