<?php

/**
 *
 * API接口层
 * 文章内容
 *
 * @package   NICMS
 * @category  app\cms\logic\article
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
use app\common\library\DataFilter;
use app\common\library\Download;
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
        $id = $cid = false;
        if ($id = $this->request->param('id/d', 0, 'abs')) {
            $map = [
                ['article.id', '=', $id],
                ['article.is_pass', '=', 1],
                ['article.delete_time', '=', 0],
                ['article.show_time', '<', time()],
                ['article.lang', '=', $this->lang->getLangSet()]
            ];
        }
        // 单页
        elseif ($cid = $this->request->param('cid/d', 0, 'abs')) {
            $map = [
                ['article.category_id', '=', $cid],
                ['article.is_pass', '=', 1],
                ['article.delete_time', '=', 0],
                ['article.show_time', '<', time()],
                ['article.lang', '=', $this->lang->getLangSet()]
            ];
        }

        if ($id || $cid) {
            $cache_key = md5('article details' . $id . $cid);
            if (!$this->cache->has($cache_key) || !$result = $this->cache->get($cache_key)) {
                $result = ModelArticle::view('article', ['id', 'category_id', 'title', 'keywords', 'description', 'username', 'access_id', 'hits', 'update_time'])
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
                        $result['next'] = $this->next((int) $result['id'], (int) $result['category_id']);
                        $result['prev'] = $this->prev((int) $result['id'], (int) $result['category_id']);
                    }

                    // 附加字段数据
                    $fields = ModelFieldsExtend::view('fields_extend', ['data'])
                        ->view('fields', ['name' => 'fields_name'], 'fields.id=fields_extend.fields_id')
                        ->where([
                            ['fields_extend.article_id', '=', $result['id']],
                            ['fields.category_id', '=', $result['category_id']],
                        ])
                        ->select()
                        ->toArray();
                    foreach ($fields as $value) {
                        $result[$value['fields_name']] = $value['data'];
                    }

                    // 标签
                    $result['tags'] = ModelArticleTags::view('article_tags', ['tags_id'])
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
                        ['article_id', '=', $result['id']]
                    ])->find();
                    if ($content && $content = $content->toArray()) {
                        unset($content['id'], $content['article_id']);
                        foreach ($content as $key => $value) {
                            switch ($key) {
                                    // 缩略图
                                case 'thumb':
                                    $result[$key] = Image::path($value);
                                    break;

                                    // 图片
                                case 'image_url':
                                    $value = unserialize($value);
                                    foreach ($value as $v) {
                                        $result[$key][] = $v ? Image::path($v) : '';
                                    }
                                    $result[$key] = array_unique($result[$key]);
                                    $result[$key] = array_filter($result[$key]);
                                    break;

                                    // 文章内容
                                case 'content':
                                    $value = DataFilter::decode($value);
                                    $value = preg_replace_callback('/(src=")([a-zA-Z0-9&=#,_:?.\/]+)(")/si', function ($matches) {
                                        return $matches[2]
                                            ? 'src="' . Image::path($matches[2]) . '"'
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

                    $this->cache->tag(['cms', 'cms article details'])->set($cache_key, $result);
                }
            }
        }

        return [
            'debug' => false,
            'cache' => $result ? 28800    : false,
            'msg'   => $result ? 'details' : 'error',
            'data'  => $result ?: []
        ];
    }

    /**
     * 更新浏览量
     * @access public
     * @return array
     */
    public function hits(): array
    {
        if ($id = $this->request->param('id/d', 0, 'abs')) {
            $map = [
                ['id', '=', $id],
            ];

            // 更新浏览数
            ModelArticle::where($map)
                ->inc('hits', 1, 60)
                ->update();

            $result = ModelArticle::where($map)->value('hits', 0);
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
        $next_id = ModelArticle::where([
            ['is_pass', '=', 1],
            ['category_id', 'in', $this->child($_category_id)],
            ['show_time', '<', time()],
            ['id', '>', $_article_id],
            ['lang', '=', $this->lang->getLangSet()]
        ])->min('id');
        // ->order('is_top DESC, is_hot DESC, is_com DESC, sort_order DESC, update_time DESC')

        $result = ModelArticle::view('article', ['id', 'category_id', 'title', 'keywords', 'description', 'access_id', 'update_time'])
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
        } else {
            $result = [
                'title'   => $this->lang->get('not next'),
                'url'     => url('details/' . $_category_id . '/' . $_article_id),
                'cat_url' => url('list/' . $_category_id),
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
        $prev_id = ModelArticle::where([
            ['is_pass', '=', 1],
            ['category_id', 'in', $this->child($_category_id)],
            ['show_time', '<', time()],
            ['id', '<', $_article_id],
            ['lang', '=', $this->lang->getLangSet()]
        ])->max('id');

        $result = ModelArticle::view('article', ['id', 'category_id', 'title', 'keywords', 'description', 'access_id', 'update_time'])
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
        } else {
            $result = [
                'title'   => $this->lang->get('not prev'),
                'url'     => url('details/' . $_category_id . '/' . $_article_id),
                'cat_url' => url('list/' . $_category_id),
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
