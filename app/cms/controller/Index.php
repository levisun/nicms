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

use think\exception\HttpResponseException;
use app\common\controller\BaseController;
use app\common\library\Base64;
use app\common\library\Siteinfo;
use app\common\model\Category as ModelCategory;

class Index extends BaseController
{
    private $model_name = '';

    /**
     * 初始化
     * @access public
     * @return void
     */
    public function initialize()
    {
        $result = Siteinfo::query('cms');
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

        if ($cid = $this->request->param('cid/d', 0)) {
            // 获得栏目对应模板
            $this->model_name = ModelCategory::view('category', ['id'])
                ->view('model', ['name' => 'theme_name'], 'model.id=category.model_id')
                ->where([
                    ['category.is_show', '=', 1],
                    ['category.id', '=', $cid],
                ])
                ->cache('theme_' . (string) $cid)
                ->value('model.name');
            // 栏目不存在抛出404错误
            if (!$this->model_name) {
                $response = miss(404);
                throw new HttpResponseException($response);
            }

            // 获得面包屑
            $breadcrumb = call_user_func([
                $this->app->make('\app\cms\logic\nav\Breadcrumb'),
                'query'
            ]);
            $this->assign('breadcrumb', $breadcrumb['data']);

            // 获得侧导航
            $sidebar = call_user_func([
                $this->app->make('\app\cms\logic\nav\Sidebar'),
                'query'
            ]);
            $this->assign('sidebar', $sidebar['data']);
        }
    }

    /**
     * 主页
     * @access public
     * @return
     */
    public function index()
    {
        return $this->fetch('index');
    }

    /**
     * 列表页
     * @access public
     * @return
     */
    public function category()
    {
        $result = call_user_func([
            $this->app->make('\app\cms\logic\article\Category'),
            'query'
        ]);
        return $this->fetch($this->model_name . '_list', $result['data']);
    }

    /**
     * 详情页
     * @access public
     * @return
     */
    public function details()
    {
        $result = call_user_func([
            $this->app->make('\app\cms\logic\article\Details'),
            'query'
        ]);
        return $this->fetch($this->model_name . '_details', $result['data']);
    }

    /**
     * 友链
     * @access public
     * @return
     */
    public function link()
    {
        $result = call_user_func([
            $this->app->make('\app\cms\logic\link\Catalog'),
            'query'
        ]);
        return $this->fetch($this->model_name, $result['data']);
    }

    /**
     * 反馈
     * @access public
     * @return
     */
    public function feedback()
    {
        return $this->fetch($this->model_name);
    }

    /**
     * 留言
     * @access public
     * @return
     */
    public function message()
    {
        return $this->fetch($this->model_name);
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
