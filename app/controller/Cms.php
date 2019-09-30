<?php

/**
 *
 * 控制层
 * CMS
 *
 * @package   NICMS
 * @category  app\controller
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace app\controller;

use app\controller\BaseController;
use app\library\Siteinfo;
use app\model\Article as ModelArticle;
use app\model\Category as ModelCategory;


class Cms extends BaseController
{

    /**
     * 初始化
     * @access public
     * @param
     * @return void
     */
    public function initialize()
    {
        $this->app->event->listen('HttpEnd', function () {
            // 生成访问日志
            (new Accesslog)->record();
            // 生成网站地图
            1 === mt_rand(1, 9) and (new Sitemap)->create();
        });

        $this->authenticate();

        $result = Siteinfo::query();
        $this->view->config([
            'app_name'   => 'admin',
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
     * CMS
     * @access public
     * @param
     * @return void
     */
    public function index()
    {
        echo '<img src="//api.nicms.com/verify.do">';
        return ;
        $this->fetch('index');
    }

    /**
     * 列表页
     * @access public
     * @param  string $name 分层名
     * @param  int    $cid  栏目ID
     * @return void
     */
    public function lists(string $name)
    {
        $this->fetch('list_' . $name);
    }

    /**
     * 详情页
     * @access public
     * @param  string $name 分层名
     * @param  int    $cid  栏目ID
     * @param  int    $id   文章ID
     * @return void
     */
    public function details(string $name)
    {
        $this->fetch('details_' . $name);
    }

    /**
     * 搜索页
     * @access public
     * @param  string $name 分层名
     * @param  int    $cid  栏目ID
     * @param  int    $id   文章ID
     * @return void
     */
    public function search()
    {
        $this->fetch('search');
    }

    /**
     * 操作验证权限
     * @access private
     * @param
     * @return void
     */
    protected function authenticate(): void
    {
        $cid = $this->request->param('cid/f', null);
        if (null !== $cid && 0 >= $cid) {
            $this->redirect('404');
        }



        // if (null !== $cid && 0 >= $cid) {
        //     $this->redirect('404');
        // }

        // if (null !== $cid) {
        //     $count = (new ModelCategory)
        //         ->where([
        //             ['is_show', '=', 1],
        //             ['lang', '=', $this->lang->getLangSet()]
        //         ])
        //         ->cache('verification category' . $this->lang->getLangSet())
        //         ->count();
        //     if ($cid < 1 || $cid > $count) {
        //         $this->redirect('404');
        //     }
        // }

        // $id = $this->request->param('id/f', null);
        // if (null !== $id) {
        //     $count = (new ModelArticle)
        //         ->where([
        //             ['is_pass', '=', '1'],
        //             ['show_time', '<=', time()],
        //             ['lang', '=', $this->lang->getLangSet()]
        //         ])
        //         ->cache('verification article' . $this->lang->getLangSet())
        //         ->count();
        //     if ($cid < 1 || $id > $count) {
        //         $this->redirect('404');
        //     }
        // }
    }
}
