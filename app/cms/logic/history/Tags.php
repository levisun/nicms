<?php

/**
 *
 * API接口层
 * 历史记录
 *
 * @package   NICMS
 * @category  app\cms\logic\history
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace app\cms\logic\history;

use app\service\BaseService;
use app\model\ArticleTags as ModelArticleTags;
// use think\facade\Cache;
// use think\facade\Config;
// use think\facade\Lang;
// use think\facade\Request;
use app\model\TagsArticle as ModelTagsArticle;
// use app\library\Base64;

class Tags extends BaseService
{

    /**
     * 记录浏览的标签信息
     * @return [type] [description]
     */
    public function record()
    {
        if ($id = $this->request->param('id/d', 0)) {
            $tags = (new ModelArticleTags)
                ->view('article_tags', ['tags_id'])
                ->view('tags', ['name'], 'tags.id=article_tags.tags_id')
                ->where([
                    ['article_tags.article_id', '=', $id],
                ])
                ->select()
                ->toArray();
            if (!empty($tags)) {
                if (cookie('?__htags')) {
                    $user_tags = cookie('__htags');
                }



                if ($this->uid) {
                    # code...
                }
            }

            if (!empty($tags) && $this->uid) {
                # code...
            } elseif (!empty($tags)) {
                $this->cookie->has('__htags');
            }
        }
    }

    /**
     * 清除过期的记录
     * @return [type] [description]
     */
    public function remove()
    {
        # code...
    }
}
