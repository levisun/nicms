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
use app\common\library\Siteinfo;

class Index extends BaseController
{

    /**
     * 初始化
     * @access public
     * @return void
     */
    public function initialize()
    {
        $result = (new Siteinfo)->query('book');
        $this->view->config([
            'view_theme' => $result['theme'],
            // 'tpl_replace_string' => [
            //     '__NAME__'        => $result['name'],
            //     '__TITLE__'       => $result['title'],
            //     '__KEYWORDS__'    => $result['keywords'],
            //     '__DESCRIPTION__' => $result['description'],
            //     '__FOOTER_MSG__'  => $result['footer'],
            //     '__COPYRIGHT__'   => $result['copyright'],
            //     '__SCRIPT__'      => $result['script'],
            // ]
        ]);
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
        return $this->fetch('list');
    }

    /**
     * 列表页
     * @access public
     * @return
     */
    public function catalog()
    {
        return $this->fetch('catalog');
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
