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
use app\common\library\tools\Participle;
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
        $map = [];

        // 安栏目查询,为空查询所有
        if ($category_id = $this->request->param('cid/d', 0, 'abs')) {
            $map[] = ['article.category_id', '=', $category_id];
        }

        // 安模型查询,为空查询所有
        if ($model_id = $this->request->param('mid/d', 0, 'abs')) {
            $map[] = ['model_id', '=', $model_id];
        }

        // 搜索
        if ($search_key = $this->request->param('key')) {
            $search_key = (new Participle)->words($search_key, 3);
            if (!empty($search_key)) {
                $map[] = ['article.title', 'regexp', implode('|', $search_key)];
            }
        }

        $date_format = $this->request->param('date_format', 'Y-m-d H:i:s');

        $query_limit = $this->request->param('limit/d', 20, 'abs');
        $query_limit = 100 > $query_limit && 10 < $query_limit ? intval($query_limit / 10) * 10 : 20;

        $query_page = $this->request->param('page/d', 1, 'abs');
        if ($query_page > $this->cache->get('admin content recycle last_page' . $query_limit, $query_page)) {
            return [
                'debug' => false,
                'cache' => true,
                'msg'   => 'error',
            ];
        }

        $total = $this->cache->get('admin content recycle total', false);
        $total = is_bool($total) ? (bool) $total : (int) $total;

        $result = ModelArticle::view('article', ['id', 'category_id', 'title', 'attribute', 'username', 'access_id', 'hits', 'update_time'])
            ->view('category', ['name' => 'cat_name'], 'category.id=article.category_id')
            ->view('model', ['id' => 'model_id', 'name' => 'model_name'], 'model.id=category.model_id')
            ->view('type', ['id' => 'type_id', 'name' => 'type_name'], 'type.id=article.type_id', 'LEFT')
            ->view('level', ['name' => 'access_name'], 'level.id=article.access_id', 'LEFT')
            ->view('user', ['username' => 'author'], 'user.id=article.user_id', 'LEFT')
            ->where('article.delete_time', '<>', '0')
            ->where('model_id', '<=', '4')
            ->where('article.lang', '=', $this->lang->getLangSet())
            ->where($map)
            ->order('article.is_pass ASC, article.attribute DESC, article.sort_order DESC, article.update_time DESC')
            ->paginate([
                'list_rows' => $query_limit,
                'path' => 'javascript:paging([PAGE]);',
            ], $total);

        $list = $result->toArray();

        if (!$this->cache->has('admin content recycle total')) {
            $this->cache->set('admin content recycle total', $list['total'], 28800);
        }

        if (!$this->cache->has('admin content recycle last_page' . $query_limit)) {
            $this->cache->set('admin content recycle last_page' . $query_limit, $list['last_page'], 28800);
        }

        $list['total'] = number_format($list['total']);
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
     * 还原
     * @access public
     * @return array
     */
    public function recover(): array
    {
        $this->actionLog('admin content remove');

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
     * 物理删除
     * @access public
     * @return array
     */
    public function remove(): array
    {
        $this->actionLog('admin content remove');

        if (!$id = $this->request->param('id/d', 0, 'abs')) {
            return [
                'debug' => false,
                'cache' => false,
                'code'  => 40001,
                'msg'   => 'error'
            ];
        }

        ModelArticle::transaction(function () use ($id) {
            // 删除文章模块数据
            $thumb = ModelArticle::where('id', '=', $id)->value('thumb');

            if (!empty($thumb)) {
                UploadLog::remove($thumb);
            }

            ModelArticle::where('id', '=', $id)->limit(1)->delete();

            ModelArticleContent::where('article_id', '=', $id)->limit(1)->delete();


            // 删除下载模块数据
            $file_url = ModelArticleFile::where('article_id', '=', $id)->value('file_url');
            if (!empty($file_url)) {
                UploadLog::remove($file_url);
            }
            ModelArticleFile::where('article_id', '=', $id)->limit(1)->delete();


            // 删除相册模块数据
            $image_url = ModelArticleImage::where('article_id', '=', $id)->value('image_url');
            $image_url = unserialize($image_url);
            foreach ($image_url as $value) {
                UploadLog::remove($value);
            }
            ModelArticleImage::where('article_id', '=', $id)->delete();


            // 删除标签数据
            ModelArticleTags::where('article_id', '=', $id)->delete();

            // 删除评论
            ModelDiscuss::where('article_id', '=', $id)->delete();

            // 删除自定义字段数据
            ModelFieldsExtend::where('article_id', '=', $id)->delete();
        });

        return [
            'debug' => false,
            'cache' => false,
            'msg'   => 'success'
        ];
    }
}
