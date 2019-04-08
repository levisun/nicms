<?php
/**
 *
 * API接口层
 * 文章列表
 *
 * @package   NICMS
 * @category  app\logic\cms\search
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */
declare (strict_types = 1);

namespace app\logic\cms\search;

use think\facade\Cache;
use think\facade\Config;
use think\facade\Lang;
use think\facade\Request;
use app\library\Base64;
use app\model\Article as ModelArticle;

class Article
{

    /**
     * 查询列表
     * @access public
     * @param
     * @return array
     */
    public function lists(): array
    {
        $map = [
            ['article.is_pass', '=', '1'],
            ['article.show_time', '<=', time()],
            ['article.lang', '=', Lang::detect()]
        ];

        if ($key = Request::param('key', null)) {
            $map[] = ['article.title', 'like', $key];
        } else {
            return [
                'debug' => false,
                'cache' => false,
                'msg'   => Lang::get('param error'),
                'data'  => Request::param('', [], 'trim')
            ];
        }

        $query_limit = (int) Request::param('limit/f', 20);
        $query_page = (int) Request::param('page/f', 1);

        $cache_key = md5(count($map) . $key . $query_limit . $query_page);
        $cache_key .= Request::isMobile() ? 'mobile' : '';
        if (!Cache::has($cache_key) || APP_DEBUG) {
            $result =
            ModelArticle::view('article article', ['id', 'category_id', 'title', 'keywords', 'description', 'access_id', 'update_time'])
            ->view('article_content article_content', ['thumb'], 'article_content.article_id=article.id', 'LEFT')
            ->view('category category', ['name' => 'cat_name'], 'category.id=article.category_id')
            ->view('model model', ['name' => 'action_name'], 'model.id=category.model_id')
            ->view('level level', ['name' => 'level_name'], 'level.id=article.access_id', 'LEFT')
            ->view('type type', ['id' => 'type_id', 'name' => 'type_name'], 'type.id=article.type_id', 'LEFT')
            ->where($map)
            ->order('article.is_top DESC, article.is_hot DESC , article.is_com DESC, article.sort_order DESC, article.id DESC')
            ->paginate($query_limit);
            $list = $result->toArray();
            $list['render'] = $result->render();

            $date_format = Request::param('date_format', 'Y-m-d');
            $img_size = Request::isMobile() ? 200 : 300;

            foreach ($list['data'] as $key => $value) {
                $value['flag'] = Base64::flag($value['category_id'] . $value['id'], 7);
                $value['cat_url'] = url('list/' . $value['action_name'] . '/' . $value['category_id']);
                $value['url'] = url('details/' . $value['action_name'] . '/' . $value['category_id'] . '/' . $value['id']);
                $value['update_time'] = date($date_format, $value['update_time']);

                $value['thumb'] = imgUrl($value['thumb'], $img_size);


                // 附加字段数据
                // $fields =
                // ModelArticleData::view('article_data data', ['data'])
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
                $value['tags'] =
                ModelTagsArticle::view('tags_article article', ['tags_id'])
                ->view('tags tags', ['name'], 'tags.id=article.tags_id')
                ->where([
                    ['article.article_id', '=', $value['id']],
                ])
                ->cache(__METHOD__ . 'tags' . $value['id'], null, 'LISTS')
                ->select()
                ->toArray();

                $list['data'][$key] = $value;
            }

            Cache::tag('LISTS')->set($cache_key, $list);
        } else {
            $list = Cache::get($cache_key);
        }

        return [
            'debug' => false,
            'msg'   => Lang::get('success'),
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
}
