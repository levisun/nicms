<?php

/**
 *
 * 控制层
 * admin
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
use gather\Book;

class Search extends BaseController
{

    /**
     * 初始化
     * @access public
     * @return void
     */
    public function initialize()
    {
        // $result = Siteinfo::query();
        // $this->view->config([
        //     'view_theme' => $result['theme'],
        //     'tpl_replace_string' => [
        //         '__NAME__'        => $result['name'],
        //         '__TITLE__'       => $result['title'],
        //         '__KEYWORDS__'    => $result['keywords'],
        //         '__DESCRIPTION__' => $result['description'],
        //         '__FOOTER_MSG__'  => $result['footer'],
        //         '__COPYRIGHT__'   => $result['copyright'],
        //         '__SCRIPT__'      => $result['script'],
        //     ]
        // ]);
    }

    /**
     * 详情页
     * @access public
     * @param  string $name 分层名
     * @return void
     */
    public function index()
    {
        // $query = $this->request->param('q');
        // $content = (new Book)->search($query);

        return $this->fetch('search');
    }
}
