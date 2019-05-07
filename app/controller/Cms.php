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
use app\library\Filter;
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
            'footer_msg'  => Siteinfo::footer(),
            'copyright'   => Siteinfo::copyright(),
            'script'      => Siteinfo::script(),
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
        echo count($_POST) + count($_FILES);
        echo ini_get('max_input_vars');
        // $this->fetch('index');
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
        $name = Filter::str($name);

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
        $name = Filter::str($name);

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
