<?php
/**
 *
 * API接口层
 * 文章基础类
 *
 * @package   NICMS
 * @category  app\service\cms
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */
declare (strict_types = 1);

namespace app\service\cms;

use app\library\Base64;
use app\service\BaseService;
use app\model\Article as ModelArticle;
use app\model\ArticleContent as ModelArticleContent;
use app\model\ArticleData as ModelArticleData;
use app\model\TagsArticle as ModelTagsArticle;

class ArticleBase extends BaseService
{

    /**
     * 查询列表
     * @access protected
     * @param
     * @return array
     */
    protected function lists()
    {
        $map = [
            ['article.is_pass', '=', '1'],
            ['article.show_time', '<=', time()],
            ['article.lang', '=', $this->lang->getLangSet()]
        ];

        if ($category_id = (int)$this->request->param('cid/f')) {
            $map[] = ['article.category_id', '=', $category_id];

            if ($com = (int)$this->request->param('com/f', 0)) {
                $map[] = ['article.is_com', '=', '1'];
            } elseif ($top = (int)$this->request->param('top/f', 0)) {
                $map[] = ['article.is_top', '=', '1'];
            } elseif ($hot = (int)$this->request->param('hot/f', 0)) {
                $map[] = ['article.is_hot', '=', '1'];
            }

            if ($type_id = (int)$this->request->param('tid/f', 0)) {
                $map[] = ['article.type_id', '=', $type_id];
            }

            $query_limit = (int)$this->request->param('limit/f', 10);
            $query_page = (int)$this->request->param('page/f', 1);
            $date_format = $this->request->param('date_format', 'Y-m-d');

            $cache_key = md5($category_id . $com . $top . $hot . $type_id . $query_limit . $query_page, $date_format);
            if (!$this->cache->tag('LISTS')->has($cache_key)) {
                $result = (new ModelArticle)->view('article', ['id', 'category_id', 'title', 'keywords', 'description', 'access_id', 'update_time'])
                    ->view('article_content', ['thumb'], 'article_content.article_id=article.id', 'LEFT')
                    ->view('category', ['name' => 'cat_name'], 'category.id=article.category_id')
                    ->view('model', ['name' => 'model_name'], 'model.id=category.model_id')
                    ->view('level', ['name' => 'level_name'], 'level.id=article.access_id', 'LEFT')
                    ->view('type', ['id' => 'type_id', 'name' => 'type_name'], 'type.id=article.type_id', 'LEFT')
                    ->where($map)
                    ->order('article.is_top DESC, article.is_hot DESC , article.is_com DESC, article.sort_order DESC, article.id DESC')
                    ->paginate($query_limit, false, ['path' => 'javascript:paging([PAGE]);']);

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
                        // $fields =
                        // (new ModelArticleData)->view('article_data data', ['data'])
                        // ->view('fields fields', ['name' => 'fields_name'], 'fields.id=data.fields_id')
                        // ->where([
                        //     ['data.main_id', '=', $value['id']],
                        // ])
                        // ->cache('modelarticledata' . $value['id'], null, 'LISTS')
                        // ->select()
                        // ->toArray();
                        // foreach ($fields as $val) {
                        //    $value[$val['fields_name']] = $val['data'];
                        // }


                        // 标签
                        $value['tags'] = (new ModelTagsArticle)->view('tags_article article', ['tags_id'])
                            ->view('tags tags', ['name'], 'tags.id=article.tags_id')
                            ->where([
                                ['article.article_id', '=', $value['id']],
                            ])
                            ->select()
                            ->toArray();

                        $list['data'][$key] = $value;
                    }

                    $this->cache->tag('LISTS')->set($cache_key, $list);
                    return $list;
                }
            } else {
                return $this->cache->get($cache_key);
            }
        } else {
            return [
                'debug' => false,
                'cache' => false,
                'msg'   => $this->lang->get('param error'),
                'data'  => $this->request->param('', [], 'trim')
            ];
        }
    }

    /**
     * 查询内容
     * @access protected
     * @param
     * @return array
     */
    protected function details(): array
    {
        $map = [
            ['article.is_pass', '=', '1'],
            ['article.show_time', '<=', time()],
            ['article.lang', '=', $this->lang->getLangSet()]
        ];

        if ($id = (int)$this->request->param('id/f')) {
            $map[] = ['article.id', '=', $id];
            $cache_key = md5($id);
            if (!$this->cache->tag('DETAILS')->has($cache_key)) {
                $result = (new ModelArticle)->view('article', ['id', 'category_id', 'title', 'keywords', 'description', 'access_id', 'update_time'])
                    ->view('article_content', ['thumb', 'content'], 'article_content.article_id=article.id', 'LEFT')
                    ->view('category', ['name' => 'cat_name'], 'category.id=article.category_id')
                    ->view('model', ['name' => 'model_name'], 'model.id=category.model_id')
                    ->view('level', ['name' => 'level_name'], 'level.id=article.access_id', 'LEFT')
                    ->view('type', ['id' => 'type_id', 'name' => 'type_name'], 'type.id=article.type_id', 'LEFT')
                    ->where($map)
                    ->find()
                    ->toArray();

                if ($result) {
                    $result['flag'] = Base64::flag($result['category_id'] . $result['id'], 7);
                    $date_format = $this->request->param('date_format', 'Y-m-d');
                    $result['update_time'] = date($date_format, strtotime($result['update_time']));

                    $result['thumb'] = get_img_url($result['thumb'], 300);

                    $result['content'] = htmlspecialchars_decode($result['content']);

                    if (false !== preg_match_all('/(src=["|\'])(.*?)(["|\'])/si', $result['content'], $matches)) {
                        foreach ($matches[2] as $key => $value) {
                            $thumb = get_img_url($value, 300);
                            $replace = 'src="' . $thumb . '" original="' . get_img_url($value, 0) . '"';
                            $result['content'] = str_replace($matches[0][$key], $replace, $result['content']);
                        }
                    }

                    $result['url'] = url('details/' . $result['model_name'] . '/' . $result['category_id'] . '/' . $result['id']);
                    $result['cat_url'] = url('list/' . $result['model_name'] . '/' . $result['category_id']);

                    // 上一篇 下一篇
                    $result['next'] = $this->next($result['id']);
                    $result['prev'] = $this->prev($result['id']);

                    // 附加字段数据
                    // $fields =
                    // (new ModelArticleData)->view('article_data data', ['data'])
                    // ->view('fields fields', ['name' => 'fields_name'], 'fields.id=data.fields_id')
                    // ->where([
                    //     ['data.main_id', '=', $value['id']],
                    // ])
                    // ->cache('modelarticledata' . $value['id'], null, 'DETAILS')
                    // ->select()
                    // ->toArray();
                    // foreach ($fields as $val) {
                    //    $value[$val['fields_name']] = $val['data'];
                    // }

                    // 标签
                    $result['tags'] = (new ModelTagsArticle)->view('tags_article', ['tags_id'])
                        ->view('tags', ['name'], 'tags.id=tags_article.tags_id')
                        ->where([
                            ['tags_article.article_id', '=', $result['id']],
                        ])
                        ->select()
                        ->toArray();
                }

                $this->cache->tag('DETAILS')->set($cache_key, $result);
                return $result;
            } else {
                return $this->cache->get($cache_key);
            }
        } else {
            return [
                'debug' => false,
                'cache' => false,
                'msg'   => $this->lang->get('param error'),
                'data'  => $this->request->param('', [], 'trim')
            ];
        }
    }

    /**
     * 更新浏览量
     * @access protected
     * @param
     * @return array
     */
    protected function hits()
    {
        if ($id = (int)$this->request->param('id/f')) {
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

            return (new ModelArticle)->where($map)
                ->cache(__METHOD__ . $id, 60, 'DETAILS')
                ->value('hits');
        } else {
            return [
                'debug' => false,
                'cache' => false,
                'msg'   => $this->lang->get('param error'),
                'data'  => $this->request->param('', [], 'trim')
            ];
        }
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

        $result = (new ModelArticle)->view('article', ['id', 'category_id', 'title', 'keywords', 'description', 'access_id', 'update_time'])
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

        $result = (new ModelArticle)->view('article', ['id', 'category_id', 'title', 'keywords', 'description', 'access_id', 'update_time'])
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
