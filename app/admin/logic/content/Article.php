<?php

/**
 *
 * API接口层
 * 文章
 *
 * @package   NICMS
 * @category  app\admin\logic\content
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace app\admin\logic\content;

use app\common\controller\BaseLogic;
use app\common\library\DataFilter;
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
        if ($category_id = $this->request->param('cid/d', 0)) {
            $map[] = ['article.category_id', '=', $category_id];
        }

        // 安模型查询,为空查询所有
        if ($model_id = $this->request->param('mid/d', 0)) {
            $map[] = ['model_id', '=', $model_id];
        }

        // 搜索
        if ($search_key = $this->request->param('key')) {
            $search_key = DataFilter::word($search_key, 3);
            if (!empty($search_key)) {
                $map[] = ['article.title', 'regexp', implode('|', $search_key)];
            }
        }

        $query_limit = $this->request->param('limit/d', 10);
        $date_format = $this->request->param('date_format', 'Y-m-d H:i:s');

        $result = (new ModelArticle)
            ->view('article', ['id', 'category_id', 'title', 'is_com', 'is_hot', 'is_top', 'username', 'access_id', 'hits', 'update_time'])
            ->view('category', ['name' => 'cat_name'], 'category.id=article.category_id')
            ->view('model', ['id' => 'model_id', 'name' => 'model_name'], 'model.id=category.model_id')
            ->view('type', ['id' => 'type_id', 'name' => 'type_name'], 'type.id=article.type_id', 'LEFT')
            ->view('level', ['name' => 'access_name'], 'level.id=article.access_id', 'LEFT')
            ->view('user', ['username' => 'author'], 'user.id=article.user_id', 'LEFT')
            ->where($map)
            ->order('article.is_pass ASC, article.is_top DESC, article.is_hot DESC , article.is_com DESC, article.sort_order DESC, article.update_time DESC')
            ->paginate([
                'list_rows' => $query_limit,
                'path' => 'javascript:paging([PAGE]);',
            ]);

        if ($result) {
            $list = $result->toArray();
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

        $receive_data = [
            'title'       => $this->request->param('title'),
            'keywords'    => $this->request->param('keywords'),
            'description' => $this->request->param('description'),
            'category_id' => $this->request->param('category_id/d'),
            'model_id'    => $this->request->param('model_id/d'),
            'type_id'     => $this->request->param('type_id/d', 0),
            'admin_id'    => $this->uid,
            'user_id'     => $this->request->param('user_id/d', 0),
            'is_pass'     => $this->request->param('is_pass/d', 0),
            'is_com'      => $this->request->param('is_com/d', 0),
            'is_top'      => $this->request->param('is_top/d', 0),
            'is_hot'      => $this->request->param('is_hot/d', 0),
            'sort_order'  => $this->request->param('sort_order/d', 0),
            'username'    => $this->request->param('username', ''),
            'access_id'   => $this->request->param('access_id/d', 0),
            'show_time'   => $this->request->param('show_time', date('Y-m-d'), 'strtotime'),
            'update_time' => time(),
            'create_time' => time(),
            'lang'        => $this->lang->getLangSet()
        ];

        if ($result = $this->validate(__METHOD__, $receive_data)) {
            return $result;
        }

        (new ModelArticle)->transaction(function () use ($receive_data) {
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
                $thumb = $this->request->param('thumb', '');
                $this->writeFileLog($thumb);
                (new ModelArticleContent)->save([
                    'article_id' => $article->id,
                    'thumb'      => $thumb,
                    'origin'     => $this->request->param('origin', ''),
                    'content'    => $this->request->param('content', '', '\app\common\library\DataFilter::encode')
                ]);
            }
            // 相册
            elseif (2 === $receive_data['model_id']) {
                $image_url = $this->request->param('image_url/a', '');
                foreach ($image_url as $key => $value) {
                    $this->writeFileLog($value);
                }
                (new ModelArticleImage)->save([
                    'article_id'   => $article->id,
                    'image_url'    => serialize($image_url),
                    'image_width'  => $this->request->param('image_width/d', 0),
                    'image_height' => $this->request->param('image_height/d', 0),
                ]);
            }
            // 下载
            elseif (3 === $receive_data['model_id']) {
                (new ModelArticleFile)->save([
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
        if ($id = $this->request->param('id/d')) {
            $result = (new ModelArticle)
                ->view('article', ['id', 'title', 'keywords', 'description', 'category_id', 'type_id', 'is_pass', 'is_com', 'is_top', 'is_hot', 'sort_order', 'hits', 'username', 'admin_id', 'user_id', 'show_time', 'create_time', 'update_time', 'delete_time', 'access_id', 'lang'])
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
                    $result['content'] = DataFilter::decode($result['content']);
                }

                // 附加字段数据
                $fields = (new ModelFieldsExtend)
                    ->view('fields_extend', ['data'])
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
                $result['tags'] = (new ModelArticleTags)
                    ->view('article_tags', ['tags_id'])
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

        if (!$id = $this->request->param('id/d')) {
            return [
                'debug' => false,
                'cache' => false,
                'code'  => 40001,
                'msg'   => 'error'
            ];
        }

        $receive_data = [
            'title'       => $this->request->param('title'),
            'keywords'    => $this->request->param('keywords'),
            'description' => $this->request->param('description'),
            'category_id' => $this->request->param('category_id/d'),
            'model_id'    => $this->request->param('model_id/d'),
            'type_id'     => $this->request->param('type_id/d', 0),
            'admin_id'    => $this->uid,
            'user_id'     => $this->request->param('user_id/d', 0),
            'is_pass'     => $this->request->param('is_pass/d', 0),
            'is_com'      => $this->request->param('is_com/d', 0),
            'is_top'      => $this->request->param('is_top/d', 0),
            'is_hot'      => $this->request->param('is_hot/d', 0),
            'sort_order'  => $this->request->param('sort_order/d', 0),
            'username'    => $this->request->param('username', ''),
            'access_id'   => $this->request->param('access_id/d', 0),
            'show_time'   => $this->request->param('show_time', date('Y-m-d'), 'strtotime'),
            'update_time' => time(),
        ];

        if ($result = $this->validate(__METHOD__, $receive_data)) {
            return $result;
        }

        (new ModelArticle)->transaction(function () use ($receive_data, $id) {
            $model_id = $receive_data['model_id'];
            unset($receive_data['model_id']);

            $article = new ModelArticle;
            $article->where([
                ['id', '=', $id]
            ])->data($receive_data)->update();

            $receive_data['model_id'] = $model_id;
            unset($model);

            // 自定义字段
            if ($fiels = $this->request->param('fields/a', false)) {
                foreach ($fiels as $key => $value) {
                    $has = (new ModelFieldsExtend)->where([
                        ['article_id', '=', $id],
                        ['fields_id', '=', $key]
                    ])->value('id');
                    if ($has) {
                        (new ModelFieldsExtend)->where([
                            ['article_id', '=', $id],
                            ['fields_id', '=', $key]
                        ])->data([
                            'data' => $value
                        ])->update();
                    } else {
                        (new ModelFieldsExtend)->save([
                            'article_id' => $id,
                            'fields_id'  => $key,
                            'data'       => $value,
                        ]);
                    }
                }
            }

            // 文章,单页
            if (1 === $receive_data['model_id'] || 4 === $receive_data['model_id']) {
                // 删除旧图片
                $old_thumb = (new ModelArticleContent)->where([
                    ['article_id', '=', $id]
                ])->value('thumb');
                $thumb = $this->request->param('thumb', '');
                if ($old_thumb !== $thumb) {
                    $this->removeFile($old_thumb);
                    $this->writeFileLog($thumb);
                }

                (new ModelArticleContent)->where([
                    ['article_id', '=', $id]
                ])->data([
                    'thumb'   => $thumb,
                    'origin'  => $this->request->param('origin', ''),
                    'content' => $this->request->param('content', '', '\app\common\library\DataFilter::encode')
                ])->update();
            }
            // 相册
            elseif (2 === $receive_data['model_id']) {
                // 删除旧图片
                $old_img = (new ModelArticleImage)->where([
                    ['article_id', '=', $id]
                ])->value('image_url');
                $old_img = unserialize($old_img);
                $image_url = $this->request->param('image_url/a', '');
                foreach ($old_img as $value) {
                    if (!in_array($value, $image_url)) {
                        $this->removeFile($value);
                    }
                }
                foreach ($image_url as $value) {
                    $this->writeFileLog($value);
                }

                (new ModelArticleImage)->where([
                    ['article_id', '=', $id]
                ])->data([
                    'image_url'    => serialize($this->request->param('image_url/a', '')),
                    'image_width'  => $this->request->param('image_width/d', 0),
                    'image_height' => $this->request->param('image_height/d', 0),
                ])->update();
            }
            // 下载
            elseif (3 === $receive_data['model_id']) {
                // 删除旧文件
                $old_file_url = (new ModelArticleFile)->where([
                    ['article_id', '=', $id]
                ])->value('file_url');
                $file_url = $this->request->param('file_url', '');
                if ($old_file_url !== $file_url) {
                    $this->removeFile($old_file_url);
                    $this->writeFileLog($file_url);
                }

                (new ModelArticleFile)->where([
                    ['article_id', '=', $id]
                ])->data([
                    'file_url'   => $file_url,
                    'file_size'  => $this->request->param('file_size', ''),
                    'file_ext'   => $this->request->param('file_ext', ''),
                    'file_name'  => $this->request->param('file_name', ''),
                    'file_mime'  => $this->request->param('file_mime', ''),
                    'uhash'      => $this->request->param('uhash', ''),
                    'md5file'    => $this->request->param('md5file', ''),
                ])->update();
            }

            // 清除缓存
            $this->cache->tag('cms article list' . $receive_data['category_id'])->clear();
            $this->cache->delete(md5('article details' . $id));
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

        if (!$id = $this->request->param('id/d')) {
            return [
                'debug' => false,
                'cache' => false,
                'code'  => 40001,
                'msg'   => 'error'
            ];
        }

        $category_id = (new ModelArticle)->where([
            ['id', '=', $id]
        ])->value('category_id');

        if ($category_id) {
            (new ModelArticle)->where([
                ['id', '=', $id]
            ])->data([
                'delete_time' => time()
            ])->update();

            // 清除缓存
            $this->cache->tag('cms article list' . $category_id)->clear();
            $this->cache->delete(md5('article details' . $id));
        }

        return [
            'debug' => false,
            'cache' => false,
            'msg'   => 'success'
        ];
    }
}
