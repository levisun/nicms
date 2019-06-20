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
use app\library\Filter;
use app\library\Siteinfo;
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
        $theme = $this->config->get('app.cdn_host') . '/view/cms/' . Siteinfo::theme() . '/';
        $this->setTheme(Siteinfo::theme())
            ->setReplace([
                '__THEME__'       => $theme,
                '__CSS__'         => $theme . 'css/',
                '__IMG__'         => $theme . 'img/',
                '__JS__'          => $theme . 'js/',
                '__NAME__'        => Siteinfo::name(),
                '__TITLE__'       => Siteinfo::title(),
                '__KEYWORDS__'    => Siteinfo::keywords(),
                '__DESCRIPTION__' => Siteinfo::description(),
                '__FOOTER_MSG__'  => Siteinfo::footer(),
                '__COPYRIGHT__'   => Siteinfo::copyright(),
                '__SCRIPT__'      => Siteinfo::script(),
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
        $this->verification($name);
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
        $this->verification($name);
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
}
