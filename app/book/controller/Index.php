<?php

/**
 *
 * 控制层
 * book
 *
 * @package   NICMS
 * @category  app\book\controller
 * @author    失眠小枕头 [312630173@qq.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace app\book\controller;

use app\common\controller\BaseController;
use app\book\controller\SiteInfo;

class Index extends BaseController
{

    /**
     * 初始化
     * @access public
     * @return void
     */
    public function initialize()
    {
        if ($this->request->get('rt') === date('Ymd')) {
            $this->cookie->set('rt', date('Ymd'));
        } elseif (false === $this->cookie->has('rt')) {
            $this->redirect(env('app.host'));
        }

        // 初始化视图
        $result = (new SiteInfo)->query();

        $this->view->config([
            'view_path' => $result['view_path'],
            'tpl_replace_string' => [
                '__APP_NAME__'    => config('app.app_name'),
                '__STATIC__'      => config('app.static_host') . 'static/',
                '__URL__'         => $this->request->baseUrl(true),
                '__LANG__'        => app('lang')->getLangSet(),
                '__API_HOST__'    => config('app.api_host'),
                '__IMG_HOST__'    => config('app.img_host'),
                '__STATIC_HOST__' => config('app.static_host'),

                '__NAME__'        => $result['name'],
                '__FOOTER_MSG__'  => $result['footer'],
                '__COPYRIGHT__'   => $result['copyright'],
                '__SCRIPT__'      => $result['script'],

                '__THEME__'       => $result['url'],
                '__CSS__'         => $result['url'] . 'css/',
                '__IMG__'         => $result['url'] . 'img/',
                '__JS__'          => $result['url'] . 'js/',
            ]
        ]);

        $this->view->engine()->assign([
            'TITLE'       => $result['title'],
            'KEYWORDS'    => $result['keywords'],
            'DESCRIPTION' => $result['description'],
            'URL'         => $this->request->baseUrl(true),
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
        return $this->fetch('category');
    }

    /**
     * 列表页
     * @access public
     * @return
     */
    public function book()
    {
        return $this->fetch('book_article_list');
    }

    /**
     * 详情页
     * @access public
     * @return
     */
    public function article()
    {
        return $this->fetch('book_article');
    }

    /**
     * 搜索
     * @access public
     * @return
     */
    public function search()
    {
        $query = $this->request->param('q');
        if (false !== filter_var($query, FILTER_VALIDATE_URL)) {
            $uri = parse_url($query, PHP_URL_PATH);
            $book_id = call_user_func([
                $this->app->make('\app\book\logic\book\Spider'),
                'book'
            ], $uri);

            if ($book_id) {
                $result = call_user_func([
                    $this->app->make('\app\book\logic\book\Spider'),
                    'article'
                ], $book_id);
            }
        }

        return $this->fetch('search');
    }
}
