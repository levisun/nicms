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
use app\common\model\Discuss as ModelDiscuss;
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
        $model = ModelArticle::view('article', ['id', 'category_id', 'title', 'is_pass', 'attribute', 'author', 'access_id', 'hits', 'sort_order', 'update_time'])
            ->view('category', ['name' => 'cat_name'], 'category.id=article.category_id')
            ->view('model', ['id' => 'model_id', 'name' => 'model_name'], 'model.id=category.model_id')
            ->view('type', ['id' => 'type_id', 'name' => 'type_name'], 'type.id=article.type_id', 'LEFT')
            ->view('level', ['name' => 'access_name'], 'level.id=article.access_id', 'LEFT')
            ->view('user', ['username'], 'user.id=article.user_id', 'LEFT')
            ->order('article.is_pass ASC, article.attribute DESC, article.sort_order DESC, article.update_time DESC')
            ->where('article.delete_time', '<>', '0')
            ->where('model_id', '<=', '4')
            ->where('article.lang', '=', $this->lang->getLangSet());

        // 安模型查询,为空查询所有
        if ($model_id = $this->request->param('model_id/d', 0, 'abs')) {
            $model->where('article.model_id', '=', $model_id);
            $map[] = ['model_id', '=', $model_id];
        }

        // 安栏目查询,为空查询所有
        if ($category_id = $this->request->param('category_id/d', 0, 'abs')) {
            $model->where('article.category_id', '=', $category_id);
        }

        // 安审核条件查询,为空查询所有
        if ($is_pass = $this->request->param('pass/d', 0, 'abs')) {
            $is_pass = $is_pass >= 1 ? 1 : 0;
            $model->where('article.is_pass', '=', $is_pass);
        }

        // 搜索
        if ($search_key = $this->request->param('key', null, '\app\common\library\Filter::participle')) {
            $search_key = array_slice($search_key, 0, 3);
            $search_key = array_map(function ($value) {
                return '%' . $value . '%';
            }, $search_key);
            $model->where('article.title', 'like', $search_key, 'OR');
        }

        $result = $model->paginate([
            'list_rows' => $this->getQueryLimit(),
            'path' => 'javascript:paging([PAGE]);',
        ], true);

        if ($result && $list = $result->toArray()) {
            $list['render'] = $result->render();

            $date_format = $this->request->param('date_format', 'Y-m-d H:i:s');
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
                'per_page'     => $list['per_page'],
                'current_page' => $list['current_page'],
                'page'         => $list['render'],
            ]
        ];
    }

    /**
     * 还原
     * @access public
     * @return array
     */
    public function recover(): array
    {
        if (!$id = $this->request->param('id/d', 0, 'abs')) {
            return [
                'debug' => false,
                'cache' => false,
                'code'  => 40001,
                'msg'   => 'error'
            ];
        }

        $category_id = ModelArticle::where('id', '=', $id)->value('category_id');

        if ($category_id) {
            ModelArticle::where('id', '=', $id)->limit(1)->update([
                'delete_time' => 0
            ]);

            $this->actionLog('admin content remove ID:' . $id);

            // 清除缓存
            $this->cache->tag('cms article list' . $category_id)->clear();
        }

        return [
            'debug' => false,
            'cache' => false,
            'msg'   => 'success'
        ];
    }

    /**
     * 物理删除
     * @access public
     * @return array
     */
    public function remove(): array
    {
        if (!$id = $this->request->param('id/d', 0, 'abs')) {
            return [
                'debug' => false,
                'cache' => false,
                'code'  => 40001,
                'msg'   => 'error'
            ];
        }

        ModelArticle::transaction(function () use ($id) {
            // 删除下载模块数据
            $file_url = ModelArticleFile::where('article_id', '=', $id)->value('file_url');
            if (!empty($file_url)) {
                UploadLog::remove($file_url);
            }
            ModelArticleFile::where('article_id', '=', $id)->limit(1)->delete();


            // 删除相册模块数据
            $image_url = ModelArticleImage::where('article_id', '=', $id)->value('image_url');
            $image_url = $image_url ? unserialize($image_url) : [];
            foreach ($image_url as $value) {
                UploadLog::remove($value);
            }
            ModelArticleImage::where('article_id', '=', $id)->delete();


            // 删除文章模块数据
            $thumb = ModelArticle::where('id', '=', $id)->value('thumb');

            if (!empty($thumb)) {
                UploadLog::remove($thumb);
            }

            if ($content = ModelArticleContent::where('article_id', '=', $id)->value('content')) {
                $content = Filter::htmlDecode($content);
                preg_replace_callback('/<img[^<>]*src["\']+([^<>]+)["\']+[^<>]*>', function ($src) {
                    if (0 !== stripos($src, 'http')) {
                        @unlink(public_path() . $src[1]);
                    }
                }, $content);
            }
            ModelArticle::where('id', '=', $id)->limit(1)->delete();
            ModelArticleContent::where('article_id', '=', $id)->limit(1)->delete();


            // 删除标签数据
            ModelArticleTags::where('article_id', '=', $id)->delete();

            // 删除评论
            ModelDiscuss::where('article_id', '=', $id)->delete();

            // 删除自定义字段数据
            ModelFieldsExtend::where('article_id', '=', $id)->delete();

            $this->actionLog('admin content remove ID:' . $id);
        });

        return [
            'debug' => false,
            'cache' => false,
            'msg'   => 'success'
        ];
    }
}
