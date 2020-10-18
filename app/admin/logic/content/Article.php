<?php

/**
 *
 * API接口层
 * 文章
 *
 * @package   NICMS
 * @category  app\admin\logic\content
 * @author    失眠小枕头 [312630173@qq.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace app\admin\logic\content;

use app\common\controller\BaseLogic;
use app\common\library\Filter;
use app\common\library\UploadLog;
use app\common\model\Article as ModelArticle;
use app\common\model\ArticleContent as ModelArticleContent;
use app\common\model\ArticleFile as ModelArticleFile;
use app\common\model\ArticleImage as ModelArticleImage;
use app\common\model\ArticleTags as ModelArticleTags;
use app\common\model\FieldsExtend as ModelFieldsExtend;
use app\common\model\Tags as ModelTags;

class Article extends BaseLogic
{
    protected $authKey = 'admin_auth_key';

    /**
     * 查询
     * @access public
     * @return array
     */
    public function query(): array
    {
        $map = [
            ['article.delete_time', '=', '0'],
            ['model_id', '<=', '4'],
            ['article.lang', '=', $this->lang->getLangSet()]
        ];

        // 安栏目查询,为空查询所有
        if ($category_id = $this->request->param('cid/d', 0, 'abs')) {
            $map[] = ['article.category_id', '=', $category_id];
        }

        // 安模型查询,为空查询所有
        if ($model_id = $this->request->param('mid/d', 0, 'abs')) {
            $map[] = ['model_id', '=', $model_id];
        }

        // 安审核条件查询,为空查询所有
        if ($is_pass = $this->request->param('pass/d', 0, 'abs')) {
            $is_pass = $is_pass >= 1 ? 1 : 0;
            $map[] = ['article.is_pass', '=', $is_pass];
        }

        // 搜索
        if ($search_key = $this->request->param('key')) {
            $search_key = words($search_key, 3);
            if (!empty($search_key)) {
                $map[] = ['article.title', 'regexp', implode('|', $search_key)];
            }
        }

        $query_limit = $this->request->param('limit/d', 20, 'abs');
        $query_limit = $query_limit <= 0 ? 20 : $query_limit;
        $query_limit = $query_limit > 100 ? 20 : $query_limit;

        $date_format = $this->request->param('date_format', 'Y-m-d H:i:s');

        $result = ModelArticle::view('article', ['id', 'category_id', 'title', 'is_pass', 'attribute', 'username', 'access_id', 'hits', 'sort_order', 'update_time'])
            ->view('category', ['name' => 'cat_name'], 'category.id=article.category_id')
            ->view('model', ['id' => 'model_id', 'name' => 'model_name'], 'model.id=category.model_id')
            ->view('type', ['id' => 'type_id', 'name' => 'type_name'], 'type.id=article.type_id', 'LEFT')
            ->view('level', ['name' => 'access_name'], 'level.id=article.access_id', 'LEFT')
            ->view('user', ['username' => 'author'], 'user.id=article.user_id', 'LEFT')
            ->where($map)
            ->order('article.is_pass ASC, article.attribute DESC, article.sort_order DESC, article.update_time DESC')
            ->paginate([
                'list_rows' => $query_limit,
                'path' => 'javascript:paging([PAGE]);',
            ]);

        $list = $result->toArray();
        $list['total'] = number_format($list['total']);
        $list['render'] = $result->render();

        foreach ($list['data'] as $key => $value) {
            $value['url'] = [
                'editor' => url('content/article/editor/' . $value['id']),
                'remove' => url('content/article/remove/' . $value['id']),

                // 栏目链接
                'cat_url' => $this->config->get('app.app_host') . url('list/' . $value['category_id']),
                // 文章链接
                'url' => $this->config->get('app.app_host') . url('details/' . $value['category_id'] . '/' . $value['id']),
            ];

            // 时间格式
            $value['update_time'] = date($date_format, (int) $value['update_time']);
            // 作者
            $value['author'] = $value['author'] ?: $value['username'];
            unset($value['username']);

            $list['data'][$key] = $value;
        }

        return [
            'debug' => false,
            'cache' => false,
            'msg'   => 'success',
            'data'  => [
                'list'         => $list['data'],
                'total'        => $list['total'],
                'per_page'     => $list['per_page'],
                'current_page' => $list['current_page'],
                'last_page'    => $list['last_page'],
                'page'         => $list['render'],
            ]
        ];
    }

    /**
     * 添加
     * @access public
     * @return array
     */
    public function added()
    {
        $this->actionLog(__METHOD__, 'admin content added');

        $thumb = $this->request->param('thumb', '');
        UploadLog::update($thumb, 1);

        $receive_data = [
            'title'       => $this->request->param('title'),
            'keywords'    => $this->request->param('keywords'),
            'description' => $this->request->param('description'),
            'thumb'       => $thumb,
            'category_id' => $this->request->param('category_id/d'),
            'model_id'    => $this->request->param('model_id/d'),
            'type_id'     => $this->request->param('type_id/d', 0, 'abs'),
            'admin_id'    => $this->uid,
            'user_id'     => $this->request->param('user_id/d', 0, 'abs'),
            'is_pass'     => $this->request->param('is_pass/d', 0, 'abs'),
            'attribute'   => $this->request->param('attribute/d', 0, 'abs'),
            'sort_order'  => $this->request->param('sort_order/d', 0, 'abs'),
            'username'    => $this->request->param('username', ''),
            'access_id'   => $this->request->param('access_id/d', 0, 'abs'),
            'show_time'   => $this->request->param('show_time', date('Y-m-d'), 'strtotime'),
            'update_time' => time(),
            'create_time' => time(),
            'lang'        => $this->lang->getLangSet()
        ];

        if ($result = $this->validate(__METHOD__, $receive_data)) {
            return $result;
        }

        ModelArticle::transaction(function () use ($receive_data) {
            $model_id = $receive_data['model_id'];
            unset($receive_data['model_id']);

            $article = new ModelArticle;
            $article->save($receive_data);

            $receive_data['model_id'] = $model_id;
            unset($model);

            // 自定义字段
            if ($fiels = $this->request->param('fields/a', false)) {
                foreach ($fiels as $key => $value) {
                    $fiels_save[] = [
                        'article_id' => $article->id,
                        'fields_id'  => $key,
                        'data'       => $value,
                    ];
                }
                (new ModelFieldsExtend)->saveAll($fiels_save);
            }

            // 文章,单页
            if (1 === $receive_data['model_id'] || 4 === $receive_data['model_id']) {
                ModelArticleContent::create([
                    'article_id' => $article->id,
                    'origin'     => $this->request->param('origin', ''),
                    'content'    => $this->request->param('content', '', '\app\common\library\Filter::encode')
                ]);
            }
            // 相册
            elseif (2 === $receive_data['model_id']) {
                $image_url = $this->request->param('image_url/a', '');
                foreach ($image_url as $key => $value) {
                    UploadLog::update($value, 1);
                }
                ModelArticleImage::create([
                    'article_id'   => $article->id,
                    'image_url'    => serialize($image_url),
                    'image_width'  => $this->request->param('image_width/d', 0, 'abs'),
                    'image_height' => $this->request->param('image_height/d', 0, 'abs'),
                ]);
            }
            // 下载
            elseif (3 === $receive_data['model_id']) {
                ModelArticleFile::create([
                    'article_id' => $article->id,
                    'file_url'   => $this->request->param('file_url'),
                    'file_size'  => $this->request->param('file_size'),
                    'file_ext'   => $this->request->param('file_ext'),
                    'file_name'  => $this->request->param('file_name'),
                    'file_mime'  => $this->request->param('file_mime'),
                    'uhash'      => $this->request->param('uhash'),
                    'md5file'    => $this->request->param('md5file'),
                ]);
            }

            // 清除缓存
            $this->cache->tag('cms article list' . $receive_data['category_id'])->clear();
        });

        return [
            'debug' => false,
            'cache' => false,
            'msg'   => 'success',
        ];
    }

    /**
     * 查询
     * @access public
     * @return array
     */
    public function find(): array
    {
        $result = [];
        if ($id = $this->request->param('id/d', 0, 'abs')) {
            $result = ModelArticle::view('article', ['id', 'title', 'keywords', 'description', 'category_id', 'type_id', 'is_pass', 'attribute', 'sort_order', 'hits', 'username', 'admin_id', 'user_id', 'show_time', 'create_time', 'update_time', 'delete_time', 'access_id', 'lang'])
                ->view('category', ['name' => 'cat_name'], 'category.id=article.category_id')
                ->view('model', ['id' => 'model_id', 'name' => 'model_name', 'table_name'], 'model.id=category.model_id')
                ->view('type', ['id' => 'type_id', 'name' => 'type_name'], 'type.id=article.type_id', 'LEFT')
                ->view('level', ['name' => 'access_name'], 'level.id=article.access_id', 'LEFT')
                ->view('user', ['username' => 'author'], 'user.id=article.user_id', 'LEFT')
                ->where([
                    ['article.id', '=', $id],
                ])
                ->find();

            if ($result && $result = $result->toArray()) {
                $result['show_time'] = $result['show_time'] ? date('Y-m-d', $result['show_time']) : date('Y-m-d');

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
                        $result[$key] = $value;
                    }
                }
                if (isset($result['content'])) {
                    $result['content'] = Filter::decode($result['content']);
                }

                // 附加字段数据
                $fields = ModelFieldsExtend::view('fields_extend', ['data'])
                    ->view('fields', ['id'], 'fields.id=fields_extend.fields_id')
                    ->where([
                        ['fields.category_id', '=', $result['category_id']],
                        ['fields_extend.article_id', '=', $result['id']],
                    ])
                    ->select()
                    ->toArray();
                foreach ($fields as $key => $value) {
                    $result['fields'][$value['id']] = $value['data'];
                }

                // 标签
                $result['tags'] = ModelArticleTags::view('article_tags', ['tags_id'])
                    ->view('tags', ['name'], 'tags.id=article_tags.tags_id')
                    ->where([
                        ['article_tags.article_id', '=', $result['id']],
                    ])
                    ->select()
                    ->toArray();
            }
        }

        return [
            'debug' => false,
            'cache' => false,
            'msg'   => 'success',
            'data'  => $result
        ];
    }

    /**
     * 编辑
     * @access public
     * @return array
     */
    public function editor(): array
    {
        $this->actionLog(__METHOD__, 'admin content editor');

        if (!$id = $this->request->param('id/d', 0, 'abs')) {
            return [
                'debug' => false,
                'cache' => false,
                'code'  => 40001,
                'msg'   => 'error'
            ];
        }

        // 删除旧图片
        $old_thumb = ModelArticle::where([
            ['id', '=', $id]
        ])->value('thumb');
        $thumb = $this->request->param('thumb', '');
        if ($old_thumb !== $thumb) {
            UploadLog::remove($old_thumb);
            UploadLog::update($thumb, 1);
        }

        $receive_data = [
            'title'       => $this->request->param('title'),
            'keywords'    => $this->request->param('keywords'),
            'description' => $this->request->param('description'),
            'thumb'       => $thumb,
            'category_id' => $this->request->param('category_id/d'),
            'model_id'    => $this->request->param('model_id/d'),
            'type_id'     => $this->request->param('type_id/d', 0, 'abs'),
            'admin_id'    => $this->uid,
            'user_id'     => $this->request->param('user_id/d', 0, 'abs'),
            'is_pass'     => $this->request->param('is_pass/d', 0, 'abs'),
            'attribute'      => $this->request->param('attribute/d', 0, 'abs'),
            'sort_order'  => $this->request->param('sort_order/d', 0, 'abs'),
            'username'    => $this->request->param('username', ''),
            'access_id'   => $this->request->param('access_id/d', 0, 'abs'),
            'show_time'   => $this->request->param('show_time', date('Y-m-d'), 'strtotime'),
            'update_time' => time(),
        ];

        if ($result = $this->validate(__METHOD__, $receive_data)) {
            return $result;
        }

        ModelArticle::transaction(function () use ($receive_data, $id) {
            $model_id = $receive_data['model_id'];
            unset($receive_data['model_id']);

            ModelArticle::update($receive_data, ['id' => $id]);

            $receive_data['model_id'] = $model_id;
            unset($model);

            // 自定义字段
            if ($fiels = $this->request->param('fields/a', false)) {
                foreach ($fiels as $key => $value) {
                    $has = ModelFieldsExtend::where([
                        ['article_id', '=', $id],
                        ['fields_id', '=', $key]
                    ])->value('id');
                    if ($has) {
                        ModelFieldsExtend::update([
                            'data' => $value
                        ], ['article_id' => $id, 'fields_id' => $key]);
                    } else {
                        ModelFieldsExtend::create([
                            'article_id' => $id,
                            'fields_id'  => $key,
                            'data'       => $value,
                        ]);
                    }
                }
            }

            // 文章,单页
            if (1 === $receive_data['model_id'] || 4 === $receive_data['model_id']) {


                ModelArticleContent::update([
                    'origin'  => $this->request->param('origin', ''),
                    'content' => $this->request->param('content', '', '\app\common\library\Filter::encode')
                ], ['article_id' => $id]);
            }
            // 相册
            elseif (2 === $receive_data['model_id']) {
                // 删除旧图片
                $old_img = ModelArticleImage::where([
                    ['article_id', '=', $id]
                ])->value('image_url');
                $old_img = unserialize($old_img);
                $image_url = $this->request->param('image_url/a', '');
                foreach ($old_img as $value) {
                    if (!in_array($value, $image_url)) {
                        UploadLog::remove($value);
                    }
                }
                foreach ($image_url as $value) {
                    UploadLog::update($value, 1);
                }

                ModelArticleImage::update([
                    'image_url'    => serialize($this->request->param('image_url/a', '')),
                    'image_width'  => $this->request->param('image_width/d', 0, 'abs'),
                    'image_height' => $this->request->param('image_height/d', 0, 'abs'),
                ], ['article_id' => $id]);
            }
            // 下载
            elseif (3 === $receive_data['model_id']) {
                // 删除旧文件
                $old_file_url = ModelArticleFile::where([
                    ['article_id', '=', $id]
                ])->value('file_url');
                $file_url = $this->request->param('file_url', '');
                if ($old_file_url !== $file_url) {
                    UploadLog::remove($old_file_url);
                    UploadLog::update($file_url, 1);
                }

                ModelArticleFile::update([
                    'file_url'   => $file_url,
                    'file_size'  => $this->request->param('file_size', ''),
                    'file_ext'   => $this->request->param('file_ext', ''),
                    'file_name'  => $this->request->param('file_name', ''),
                    'file_mime'  => $this->request->param('file_mime', ''),
                    'uhash'      => $this->request->param('uhash', ''),
                    'md5file'    => $this->request->param('md5file', ''),
                ], ['article_id' => $id]);
            }

            // 清除缓存
            $this->cache->tag('cms article list' . $receive_data['category_id'])->clear();
            $this->cache->delete('article details' . $id);
        });

        return [
            'debug' => false,
            'cache' => false,
            'msg'   => 'success',
        ];
    }

    /**
     * 逻辑删除
     * @access public
     * @return array
     */
    public function remove(): array
    {
        $this->actionLog(__METHOD__, 'admin content recycle');

        if (!$id = $this->request->param('id/d', 0, 'abs')) {
            return [
                'debug' => false,
                'cache' => false,
                'code'  => 40001,
                'msg'   => 'error'
            ];
        }

        $category_id = ModelArticle::where([
            ['id', '=', $id]
        ])->value('category_id');

        if ($category_id) {
            ModelArticle::update([
                'delete_time' => time()
            ], ['id' => $id]);

            // 清除缓存
            $this->cache->tag('cms article list' . $category_id)->clear();
            $this->cache->delete('article details' . $id);
        }

        return [
            'debug' => false,
            'cache' => false,
            'msg'   => 'success'
        ];
    }

    /**
     * 排序
     * @access public
     * @return array
     */
    public function sort(): array
    {
        $this->actionLog(__METHOD__, 'admin content sort');

        $sort_order = $this->request->param('sort_order/a');
        if (empty($sort_order)) {
            return [
                'debug' => false,
                'cache' => false,
                'code'  => 40001,
                'msg'   => 'error'
            ];
        }

        $list = [];
        foreach ($sort_order as $key => $value) {
            if ($value) {
                $list[] = ['id' => (int) $key, 'sort_order' => (int) $value];
            }
        }
        if (!empty($list)) {
            (new ModelArticle)->saveAll($list);
        }

        return [
            'debug' => false,
            'cache' => false,
            'msg'   => 'success'
        ];
    }
}
