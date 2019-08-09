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
     * 构造方法
     * @access public
     * @param
     * @return void
     */
    public function initialize()
    {
        $this->authenticate();

        $result = Siteinfo::query();
        $this->view->view_theme = $this->request->controller(true) . DIRECTORY_SEPARATOR . $result['theme'];
        $this->view->setReplace([
            '__NAME__'        => $result['name'],
            '__TITLE__'       => $result['title'],
            '__KEYWORDS__'    => $result['keywords'],
            '__DESCRIPTION__' => $result['description'],
            '__FOOTER_MSG__'  => $result['footer'],
            '__COPYRIGHT__'   => $result['copyright'],
            '__SCRIPT__'      => $result['script'],
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
        // http://kaijiang.zhcw.com/zhcw/html/ssq/list.html
        $num = 1;
        // for ($r1=1; $r1 <= 28 ; $r1++) {
        //     for ($r2=$r1+1; $r2 <= 29 ; $r2++) {
        //         for ($r3=$r2+1; $r3 <= 30 ; $r3++) {
        //             for ($r4=$r3+1; $r4 <= 31 ; $r4++) {
        //                 for ($r5=$r4+1; $r5 <= 32 ; $r5++) {
        //                     for ($r6=$r5+1; $r6 <= 33 ; $r6++) {
        //                         for ($b=1; $b <= 16; $b++) {
        //                             $code = inet_pton($r1 . $r2 . $r3 . $r4 . $r5 . $r6 . $b);
        //                             $num++;
        //                             // echo $r1 . $r2 . $r3 . $r4 . $r5 . $r6 . $b . '<br>';
        //                         }
        //                     }
        //                 }
        //             }
        //         }
        //     }
        // }
        // echo $num;
        // echo '<br>';
        // echo $code;

        $code = '28293031323316';

        $str = 0;
        for ($i = 0; $i < strlen($code); $i++) {
            $str += (int) $code[$i];
        }
        echo $str;
        // echo bin2hex($str);

        // $d = unpack('H*', '');
        // $d = array_map(function($value){
        //     echo $value;
        //     return base_convert($value, 16, 2);
        // }, $d);
        // print_r($d);
        // echo array_sum($d);
        die();
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
