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

class Recycle extends BaseLogic
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
            ['article.delete_time', '<>', '0'],
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
            $query = DataFilter::word($search_key, 5);
            foreach ($query as $key => $value) {
                $query[$key] = '%' . $value . '%';
            }
            $map[] = ['title', 'like', $query, 'OR'];
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
                    'editor' => url('content/recycle/editor/' . $value['id']),
                    'remove' => url('content/recycle/remove/' . $value['id']),
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
     * 删除
     * @access public
     * @return array
     */
    public function remove(): array
    {
        $this->actionLog(__METHOD__, 'admin content remove');

        if (!$id = $this->request->param('id/d')) {
            return [
                'debug' => false,
                'cache' => false,
                'code'  => 40001,
                'msg'   => 'error'
            ];
        }

        (new ModelArticle)->transaction(function () use ($id) {
            (new ModelArticle)->where([
                ['id', '=', $id]
            ])->delete();

            // 删除文章模块数据
            $content = (new ModelArticleContent)->where([
                ['article_id', '=', $id]
            ])->column('thumb', 'content');
            !empty($content['thumb']) and $this->removeFile($content['thumb']);
            (new ModelArticleContent)->where([
                ['article_id', '=', $id]
            ])->delete();


            // 删除下载模块数据
            $file_url = (new ModelArticleFile)->where([
                ['article_id', '=', $id]
            ])->value('file_url');
            $file_url and $this->removeFile($file_url);
            (new ModelArticleFile)->where([
                ['article_id', '=', $id]
            ])->delete();


            // 删除相册模块数据
            $image_url = (new ModelArticleImage)->where([
                ['article_id', '=', $id]
            ])->value('image_url');
            $image_url = unserialize($image_url);
            foreach ($image_url as $value) {
                $this->removeFile($value);
            }
            (new ModelArticleImage)->where([
                ['article_id', '=', $id]
            ])->delete();


            // 删除标签数据
            (new ModelArticleTags)->where([
                ['article_id', '=', $id]
            ])->delete();

            // 删除自定义字段数据
            (new ModelFieldsExtend)->where([
                ['article_id', '=', $id]
            ])->delete();
        });

        return [
            'debug' => false,
            'cache' => false,
            'msg'   => 'success'
        ];
    }
}
