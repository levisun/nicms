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

use think\Response;
use think\exception\HttpResponseException;
use think\facade\Config;
use think\facade\Env;
use think\facade\Lang;
use think\facade\Request;
use app\library\Siteinfo;
use app\library\Template;
use app\model\Category as ModelCategory;

class Cms extends Template
{

    /**
     * 构造方法
     * @access public
     * @param  App  $app  应用对象
     * @return void
     */
    public function __construct()
    {
        $this->setTheme('cms/' . Siteinfo::theme());
        parent::__construct();

        $this->setReplace([
            'name'        => Siteinfo::name(),
            'title'       => Siteinfo::title(),
            'keywords'    => Siteinfo::keywords(),
            'description' => Siteinfo::description(),
            'bottom_msg'  => Siteinfo::bottom(),
            'copyright'   => Siteinfo::copyright(),
            'script'      => Siteinfo::script(),
        ]);
    }

    /**
     * CMS
     * @access public
     * @param
     * @return mixed HTML文档
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
     * @return mixed        HTML文档
     */
    public function lists(string $name = 'article', int $cid = 0)
    {
        $this->fetch('list_' . $name);
    }

    /**
     * 详情页
     * @access public
     * @param  string $name 分层名
     * @param  int    $cid  栏目ID
     * @param  int    $id   文章ID
     * @return mixed        HTML文档
     */
    public function details(string $name = 'article', int $cid = 0, int $id = 0)
    {
        $this->fetch('details_' . $name);
    }

    /**
     * 搜索页
     * @access public
     * @param  string $name 分层名
     * @param  int    $cid  栏目ID
     * @param  int    $id   文章ID
     * @return mixed        HTML文档
     */
    public function search()
    {
        $this->fetch('search');
    }
}
