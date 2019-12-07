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
        $result = (new Siteinfo)->query();
        $this->view->config([
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
     * @return
     */
    public function index()
    {
        $book = new \gather\Book('https://www.jx.la/book/');
        $data = $book->getCat('159462');

        foreach ($data as $value) {
            $value['content'] = $book->getContent($value['uri']);
            $value['show_time'] = time();
            $value['book_id'] = 1;
            unset($value['uri']);
            $value = \app\common\library\DataFilter::content($value);
            $value['hash'] = hash_hmac('sha256', $value['content'], sha1($value['content']));

            $id = (new \app\common\model\BookArticle)->where([
                ['hash', '=', $value['hash']]
            ])
            ->value('id');
            if (!$id) {
                (new \app\common\model\BookArticle)->save($value);
            }
        }
        return ;

        // return $this->fetch('index');
    }
}
