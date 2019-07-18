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
declare (strict_types = 1);

namespace app\controller;

use app\controller\BaseController;
use app\library\Siteinfo;
use app\model\Article as ModelArticle;
use app\model\Category as ModelCategory;


class Cms extends BaseController
{

    /**
     * 构造方法
     * @access public
     * @param
     * @return void
     */
    public function initialize()
    {
        $this->authenticate();

        $result = Siteinfo::query();
        $this->view->view_theme = $result['theme'];
        // $theme = $this->config->get('app.cdn_host') . '/view/cms/' . $result['theme'] . '/';
        // $this->setTheme($result['theme'])
        //     ->setReplace([
        //         '__THEME__'       => $theme,
        //         '__CSS__'         => $theme . 'css/',
        //         '__IMG__'         => $theme . 'img/',
        //         '__JS__'          => $theme . 'js/',
        //         '__NAME__'        => $result['name'],
        //         '__TITLE__'       => $result['title'],
        //         '__KEYWORDS__'    => $result['keywords'],
        //         '__DESCRIPTION__' => $result['description'],
        //         '__FOOTER_MSG__'  => $result['footer'],
        //         '__COPYRIGHT__'   => $result['copyright'],
        //         '__SCRIPT__'      => $result['script'],
        //     ]);
    }

    /**
     * CMS
     * @access public
     * @param
     * @return void
     */
    public function index()
    {
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
        if (null !== $cid) {
            $count = (new ModelCategory)
                ->where([
                    ['is_show', '=', 1],
                    ['lang', '=', $this->lang->getLangSet()]
                ])
                ->cache('verification category' . $this->lang->getLangSet())
                ->count();
            if ($cid < 1 || $cid > $count) {
                $this->redirect('404');
            }
        }

        $id = $this->request->param('id/f', null);
        if (null !== $id) {
            $count = (new ModelArticle)
                ->where([
                    ['is_pass', '=', '1'],
                    ['show_time', '<=', time()],
                    ['lang', '=', $this->lang->getLangSet()]
                ])
                ->cache('verification article' . $this->lang->getLangSet())
                ->count();
            if ($cid < 1 || $id > $count) {
                $this->redirect('404');
            }
        }
    }
}
