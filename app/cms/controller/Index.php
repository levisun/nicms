<?php

/**
 *
 * 控制层
 * admin
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
        $result = Siteinfo::query();
        $this->view->config([
            'app_name'   => 'cms',
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
     * 主页
     * @access public
     * @param  string $service
     * @param  string $logic
     * @param  string $action
     * @return
     */
    public function index()
    {
        return (new \app\common\library\Download)->url('/storage/uploads/u1/314b7/5dbb9cee847f0.png');

        return $this->fetch('index');
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
