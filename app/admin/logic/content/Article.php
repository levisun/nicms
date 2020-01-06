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
use app\common\model\Fields as ModelFields;
use app\common\model\FieldsExtend as ModelFFieldsExtend;
use app\common\model\Level as ModelLevel;
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
            // ['article.is_pass', '=', '1'],
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
                    'editor' => url('content/content/editor/' . $value['id']),
                    'remove' => url('content/content/remove/' . $value['id']),

                    // 栏目链接
                    'cat_url' => url('list/' . $value['category_id']),
                    // 文章链接
                    'url' => url('details/' . $value['category_id'] . '/' . $value['id']),
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
            'msg'   => 'content data',
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
        $this->actionLog(__METHOD__, 'admin category added');

        $receive_data = [
            'title'       => $this->request->param('title'),
            'keywords'    => $this->request->param('keywords'),
            'description' => $this->request->param('description'),
            'category_id' => $this->request->param('category_id/d'),
            'model_id'    => $this->request->param('model_id/d'),
            'type_id'     => $this->request->param('type_id/d', 0),
            'admin_id'    => $this->request->param('admin_id/d'),
            'user_id'     => $this->request->param('user_id/d', 0),
            'is_pass'     => $this->request->param('is_pass/d', 0),
            'is_com'      => $this->request->param('is_com/d', 0),
            'is_top'      => $this->request->param('is_top/d', 0),
            'is_hot'      => $this->request->param('is_hot/d', 0),
            'sort_order'  => $this->request->param('sort_order/d', 0),
            'username'    => $this->request->param('username'),
            'access_id'   => $this->request->param('access_id/d', 0),
            'show_time'   => $this->request->param('show_time', time()),
            'update_time' => time(),
            'create_time' => time(),
            'lang'        => $this->lang->getLangSet()
        ];

        if ($result = $this->validate(__METHOD__, $receive_data)) {
            return $result;
        }

        (new ModelArticle)->transaction(function () use ($receive_data) {
            $article = new ModelArticle;
            $article->save($receive_data);
            if (1 === $receive_data['model_id'] || 4 === $receive_data['model_id']) {
                # code...
            } elseif (2 === $receive_data['model_id']) {
                # code...
            } elseif (3 === $receive_data['model_id']) {
                # code...
            }
        });


        $this->cache->tag('cms nav')->clear();
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
        }

        $result['access_list'] = (new ModelLevel)
            ->field('id, name')
            ->order('id DESC')
            ->select()
            ->toArray();

        return [
            'debug' => false,
            'cache' => false,
            'msg'   => 'content data',
            'data'  => $result
        ];
    }
}
