<?php

/**
 *
 * 控制层
 *
 * @package   NICMS
 * @category  app\cms\controller
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace app\cms\controller;

use app\common\controller\BaseController;
use app\common\library\Siteinfo;
use app\common\model\Category as ModelCategory;

class Index extends BaseController
{

    /**
     * 初始化
     * @access public
     * @return void
     */
    public function initialize()
    {
        $result = (new Siteinfo)->query('cms');
        $this->view->config([
            'view_theme' => $result['theme'],
            'tpl_replace_string' => [
                '__NAME__'        => $result['name'],
                '__TITLE__'       => $result['title'],
                '__KEYWORDS__'    => $result['keywords'],
                '__DESCRIPTION__' => $result['description'],
                '__FOOTER_MSG__'  => $result['footer'],
                '__COPYRIGHT__'   => $result['copyright'],
                '__SCRIPT__'      => $result['script'],
            ]
        ]);
    }

    /**
     * 主页
     * @access public
     * @return
     */
    public function index()
    {
        $str = '<img target="_blank" href=https://mmbiz.weixin.com/mmbiz_png/pIvGTkGLzRLHhbHSib8ndxqs4ib9iaYXwAS0kM91krjdWp8x4N61egwibickSbw3ibvYOjbHUBtAry3CbIic5xgAPsC8A/640?wx_fmt=png&tp=webp&wxfrom=5&wx_lazy=1&wx_co=1 width="100" />';
        return \app\common\library\DataFilter::element($str);
        // return $this->fetch('index');
    }

    /**
     * 列表页
     * @access public
     * @return
     */
    public function category()
    {
        $result = (new ModelCategory)->view('category', ['id'])
            ->view('model', ['name' => 'theme_name'], 'model.id=category.model_id')
            ->where([
                ['category.is_show', '=', 1],
                ['category.id', '=', $this->request->param('cid/d')],
            ])
            ->cache(true)
            ->find();
        if ($result && $result = $result->toArray()) {
            return $this->fetch($result['theme_name'] . '_list');
        } else {
            return miss(404);
        }
    }

    /**
     * 详情页
     * @access public
     * @return
     */
    public function details()
    {
        $result = (new ModelCategory)->view('category', ['id'])
            ->view('model', ['name' => 'theme_name'], 'model.id=category.model_id')
            ->where([
                ['category.is_show', '=', 1],
                ['category.id', '=', $this->request->param('cid/d')],
            ])
            ->cache(true)
            ->find();
        if ($result && $result = $result->toArray()) {
            return $this->fetch($result['theme_name'] . '_details');
        } else {
            return miss(404);
        }
    }

    /**
     * 搜索页
     * @access public
     * @return
     */
    public function search()
    {
        return $this->fetch('search');
    }

    /**
     * 跳转页
     * @access public
     * @return
     */
    public function go()
    {
        if ($url = $this->request->param('url', false)) {
            return \think\Response::create(base64_decode($url), 'redirect', 302);
        } else {
            return miss(404);
        }
    }
}
