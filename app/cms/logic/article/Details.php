<?php

/**
 *
 * API接口层
 * 文章内容
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
use app\common\library\Filter;
use app\common\model\Article as ModelArticle;
use app\common\model\Category as ModelCategory;
use app\common\model\ArticleTags as ModelArticleTags;
use app\common\model\FieldsExtend as ModelFieldsExtend;

class Details extends BaseLogic
{

    /**
     * 查询内容
     * @access public
     * @return array
     */
    public function query(): array
    {
        if ($id = $this->request->param('id', 0, '\app\common\library\Base64::url62decode')) {
            $map = [
                ['article.id', '=', $id],
            ];
        }
        // 单页
        elseif ($category_id = $this->request->param('category_id', 0, '\app\common\library\Base64::url62decode')) {
            $map = [
                ['article.category_id', '=', $category_id],
            ];
        }

        $id = $category_id = false;
        if ($id = $this->request->param('id', 0, '\app\common\library\Base64::url62decode')) {
            $map = [
                ['article.id', '=', $id],
            ];
        }
        // 单页
        elseif ($category_id = $this->request->param('category_id/d', 0, '\app\common\library\Base64::url62decode')) {
            $map = [
                ['article.category_id', '=', $category_id],
            ];
        }

        if ($id || $category_id) {
            if (!$this->cache->has($this->getCacheKey()) || !$result = $this->cache->get($this->getCacheKey())) {
                $result = ModelArticle::view('article', ['id', 'category_id', 'title', 'keywords', 'description', 'thumb', 'username', 'access_id', 'hits', 'update_time'])
                    ->view('category', ['name' => 'cat_name'], 'category.id=article.category_id')
                    ->view('model', ['id' => 'model_id', 'name' => 'model_name', 'table_name'], 'model.id=category.model_id')
                    ->view('type', ['id' => 'type_id', 'name' => 'type_name'], 'type.id=article.type_id', 'LEFT')
                    ->view('level', ['name' => 'access_name'], 'level.id=article.access_id', 'LEFT')
                    ->view('user', ['username' => 'author'], 'user.id=article.user_id', 'LEFT')
                    ->where('article.is_pass', '=', 1)
                    ->where('article.delete_time', '=', 0)
                    ->where('article.access_id', '=', $this->user_role_id)
                    ->where('article.show_time', '<', time())
                    ->where('article.lang', '=', $this->lang->getLangSet())
                    ->where($map)
                    ->find();

                if ($result && $result = $result->toArray()) {
                    // 缩略图
                    $result['thumb'] = File::imgUrl($result['thumb']);
                    // 栏目链接
                    $result['cat_url'] = url('list/' . Base64::url62encode($result['category_id']));
                    // 文章链接
                    $result['url'] = url('details/' . Base64::url62encode($result['category_id']) . '/' . Base64::url62encode($result['id']));
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
                        $result['next'] = $this->next((int) $result['id'], (int) $result['category_id']);
                        $result['prev'] = $this->prev((int) $result['id'], (int) $result['category_id']);
                    }

                    // 附加字段数据
                    $fields = ModelFieldsExtend::view('fields_extend', ['data'])
                        ->view('fields', ['name' => 'fields_name'], 'fields.id=fields_extend.fields_id')
                        ->where('fields_extend.article_id', '=', $result['id'])
                        ->where('fields.category_id', '=', $result['category_id'])
                        ->select()
                        ->toArray();
                    foreach ($fields as $value) {
                        $result[$value['fields_name']] = $value['data'];
                    }

                    // 标签
                    $result['tags'] = ModelArticleTags::view('article_tags', ['tags_id'])
                        ->view('tags', ['name'], 'tags.id=article_tags.tags_id')
                        ->where('article_tags.article_id', '=', $result['id'])
                        ->select()
                        ->toArray();
                    foreach ($result['tags'] as $key => $tag) {
                        $tag['url'] = url('tags/' . Base64::url62encode($tag['tags_id']));
                        $result['tags'][$key] = $tag;
                    }

                    // table_name
                    $model = \think\helper\Str::studly($result['table_name']);
                    unset($result['table_name']);
                    $content = $this->app->make('\app\common\model\\' . $model);
                    $content = $content->where('article_id', '=', $result['id'])->find();
                    if ($content && $content = $content->toArray()) {
                        unset($content['id'], $content['article_id']);
                        foreach ($content as $key => $value) {
                            // 图片
                            if ('image_url' === $key) {
                                $value = unserialize($value);
                                foreach ($value as $path) {
                                    $result[$key][] = $path ? File::imgUrl($path) : '';
                                }
                                $result[$key] = array_unique($result[$key]);
                                $result[$key] = array_filter($result[$key]);
                            }
                            // 文章内容
                            elseif ('content' === $key) {
                                $result[$key] = Filter::htmlDecode($value, true);
                            }
                            // 下载文件
                            elseif ('file_url' === $key) {
                                $result[$key] = $value
                                    ? $this->config->get('app.api_host') . 'download.do?file=' . File::pathEncode($value)
                                    : '';
                            }
                            // 版权
                            elseif ('origin' === $key) {
                                $result['copyright'] = $value
                                    ? '来源：<a href="' . $value . '" rel="nofollow" target="_blank">' . parse_url($value, PHP_URL_HOST) . '</a>'
                                    : '未经允许不得转载';
                            }
                            // 其他
                            else {
                                $result[$key] = $value;
                            }
                        }
                    }

                    $result['id'] = Base64::url62encode($result['id']);
                    $result['category_id'] = Base64::url62encode($result['category_id']);

                    $this->cache->tag('cms article details')->set($this->getCacheKey(), $result);
                }
            }
        }

        return [
            'debug' => false,
            'cache' => isset($result) ? 28800    : false,
            'msg'   => isset($result) ? 'details' : 'error',
            'data'  => isset($result) ? $result : []
        ];
    }

    /**
     * 更新浏览量
     * @access public
     * @return array
     */
    public function hits(): array
    {
        if ($id = $this->request->param('id', 0, '\app\common\library\Base64::url62decode')) {

            // 更新浏览数
            ModelArticle::where('id', '=', $id)
                ->inc('hits', 1, 60)
                ->update();

            $result = ModelArticle::where('id', '=', $id)->value('hits', 0);
        }

        return [
            'debug'  => false,
            'cache'  => 60,
            'msg'    => isset($result) ? 'article hits' : 'article hits error',
            'data'   => isset($result) ? ['hits' => $result] : []
        ];
    }

    /**
     * 下一篇
     * @access private
     * @param  int      $_article_id
     * @param  int      $_category_id
     * @return array
     */
    private function next(int $_article_id, int $_category_id)
    {
        $next_id = ModelArticle::where('is_pass', '=', 1)
            ->where('category_id', 'in', $this->child($_category_id))
            ->where('show_time', '<', time())
            ->where('id', '>', $_article_id)
            ->where('lang', '=', $this->lang->getLangSet())
            ->min('id');
        // ->order('is_top DESC, is_hot DESC, is_com DESC, sort_order DESC, update_time DESC')

        $result = ModelArticle::view('article', ['id', 'category_id', 'title', 'keywords', 'description', 'access_id', 'update_time'])
            ->view('category', ['name' => 'cat_name'], 'category.id=article.category_id')
            ->view('model', ['name' => 'model_name'], 'model.id=category.model_id')
            ->where('article.is_pass', '=', 1)
            ->where('article.show_time', '<', time())
            ->where('article.id', '=', $next_id)
            ->find();

        if (null !== $result && $result = $result->toArray()) {
            $result['id'] = Base64::url62encode($result['id']);
            $result['category_id'] = Base64::url62encode($result['category_id']);
            $result['flag'] = Base64::flag($result['category_id'] . $result['id'], 7);
            $result['url'] = url('details/' . $result['category_id'] . '/' . $result['id']);
            $result['cat_url'] = url('list/' . $result['category_id']);
        } else {
            $result = [
                'title'   => $this->lang->get('not next'),
                'url'     => url('details/' . Base64::url62encode($_category_id) . '/' . Base64::url62encode($_article_id)),
                'cat_url' => url('list/' . Base64::url62encode($_category_id)),
            ];
        }

        return $result;
    }

    /**
     * 上一篇
     * @access private
     * @param  int      $_article_id
     * @param  int      $_category_id
     * @return array
     */
    private function prev(int $_article_id, int $_category_id)
    {
        $prev_id = ModelArticle::where('is_pass', '=', 1)
            ->where('category_id', 'in', $this->child($_category_id))
            ->where('show_time', '<', time())
            ->where('id', '<', $_article_id)
            ->where('lang', '=', $this->lang->getLangSet())
            ->max('id');

        $result = ModelArticle::view('article', ['id', 'category_id', 'title', 'keywords', 'description', 'access_id', 'update_time'])
            ->view('category', ['name' => 'cat_name'], 'category.id=article.category_id')
            ->view('model', ['name' => 'model_name'], 'model.id=category.model_id')
            ->where('article.is_pass', '=', 1)
            ->where('article.show_time', '<', time())
            ->where('article.id', '=', $prev_id)
            ->find();

        if (null !== $result && $result = $result->toArray()) {
            $result['id'] = Base64::url62encode($result['id']);
            $result['category_id'] = Base64::url62encode($result['category_id']);
            $result['flag'] = Base64::flag($result['category_id'] . $result['id'], 7);
            $result['url'] = url('details/' . $result['category_id'] . '/' . $result['id']);
            $result['cat_url'] = url('list/' . $result['category_id']);
        } else {
            $result = [
                'title'   => $this->lang->get('not prev'),
                'url'     => url('details/' . Base64::url62encode($_category_id) . '/' . Base64::url62encode($_article_id)),
                'cat_url' => url('list/' . Base64::url62encode($_category_id)),
            ];
        }

        return $result;
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
