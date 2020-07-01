<?php

/**
 *
 * 控制层
 * book
 *
 * @package   NICMS
 * @category  app\book\controller
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace app\book\controller;

use app\common\controller\BaseController;

class Index extends BaseController
{

    /**
     * 初始化
     * @access public
     * @return void
     */
    public function initialize()
    {
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
     * 栏目页
     * @access public
     * @return
     */
    public function category()
    {
        return $this->fetch('category');
    }

    /**
     * 列表页
     * @access public
     * @return
     */
    public function book()
    {
        return $this->fetch('list');
    }

    /**
     * 详情页
     * @access public
     * @return
     */
    public function article()
    {
        return $this->fetch('article');
    }

    /**
     * 搜索
     * @access public
     * @return
     */
    public function search()
    {
        // $query = $this->request->param('q');
        // $content = (new Book)->search($query);

        return $this->fetch('search');
    }
}
