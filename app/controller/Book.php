<?php
/**
 *
 * 控制层
 * Book
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
use app\library\Rbac;

class Book extends BaseController
{
    private $api = 'https://api.zhuishushenqi.com/';
    private $category = 'cats/lv2';
    private $list = 'book/by-categories?major=';
    private $info = 'book/';
    private $atoc = 'atoc?view=summary&book=';
    private $details = 'http://chapter2.zhuishushenqi.com/chapter/';
    private $search = 'book/fuzzy-search?query=';

    /**
     * 构造方法
     * @access public
     * @param  App  $app  应用对象
     * @return void
     */
    public function __construct()
    {
        $this->view->config([
            'app_name'   => 'book',
            'view_theme' => $this->app->env->get('book.theme', 'default')
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

    public function api()
    {
        $url = 'https://api.zhuishushenqi.com';

        $params = Request::param();

        switch ($params['method']) {
            case 'category':
                // http://api.zhuishushenqi.com/cats/lv2
                $url .= '/cats/lv2';
                break;

            case 'book_list':
                // http://api.zhuishushenqi.com/book/by-categories
                $url .= '/book/by-categories';
                unset($params['method']);
                $url .= $params ? '?' . http_build_query($params) : '';
                break;

            case 'book_info':
                // http://api.zhuishushenqi.com/book/by-categories
                $url .= '/book/' . $params['id'];
                break;

            case 'book_index':
                // http://api.zhuishushenqi.com/atoc?view=summary&book=:id
                $res = $this->get($url. '/atoc?view=summary&book=' . $params['id']);
                $url .= '/atoc/' . $res[0]['_id'] . '?view=chapters';
                break;

            case 'book_details':
                // http://api.zhuishushenqi.com/chapter/:id
                $url .= '/chapter/' . urldecode($params['id']);
                break;

            default:
                # code...
                break;
        }

        $result = $this->get($url);
        $response = Response::create($result, 'json');
        throw new HttpResponseException($response);
    }

    private function get($url)
    {
        $snoopy = new \Snoopy;
        $snoopy->fetch($url);
        return json_decode($snoopy->results, true);
    }
}
