<?php

/**
 *
 * 控制层
 *
 * @package   NICMS
 * @category  app\cms\controller
 * @author    失眠小枕头 [312630173@qq.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace app\cms\controller;

use app\common\controller\BaseController;
use app\common\model\Category as ModelCategory;
use app\cms\controller\SiteInfo;

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
        // 初始化视图
        $result = (new SiteInfo)->query();

        $this->view->config([
            'view_path' => $result['view_path'],
            'tpl_replace_string' => $result['tpl_replace_string'],
        ]);

        $this->view->engine()->assign([
            'TITLE'       => $result['title'],
            'KEYWORDS'    => $result['keywords'],
            'DESCRIPTION' => $result['description'],
            'URL'         => $this->request->baseUrl(true),
        ]);

        if ($category_id = $this->request->param('category_id', 0, '\app\common\library\Base64::url62decode')) {
            // 获得栏目对应模板
            $this->model_name = ModelCategory::view('category', ['id'])
                ->view('model', ['name' => 'theme_name'], 'model.id=category.model_id')
                ->where('category.is_show', '=', 1)
                ->where('category.id', '=', $category_id)
                ->cache('theme_' . (string) $category_id)
                ->value('model.name');

            // 栏目不存在抛出404错误
            if (!$this->model_name) {
                miss(404, true, true);
            }
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
        return $this->fetch($this->model_name . '_list');
    }

    /**
     * 详情页
     * @access public
     * @return
     */
    public function details()
    {
        return $this->fetch($this->model_name . '_details');
    }

    /**
     * 友链
     * @access public
     * @return
     */
    public function link()
    {
        return $this->fetch($this->model_name);
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
            return \think\Response::create($url, 'redirect', 302);
        } else {
            return miss(404, true);
        }
    }
}
