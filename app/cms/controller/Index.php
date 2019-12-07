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
        $client = new \GuzzleHttp\Client([
            'base_uri' => 'https://www.jx.la/book/',
        ]);

        $response = $client->request('GET', '159462');
        if (200 == $response->getStatusCode()) {
            $body = $response->getBody();
            $content = $body->getContents();
            preg_match_all('/<a style="" href="\/book\/(.*?)">(.*?)<\/a>/si', $content, $matches);
            if (!empty($matches[1])) {
                foreach ($matches[1] as $key => $url) {
                    $response = $client->request('GET', $url);
                    if (200 == $response->getStatusCode()) {
                        $body = $response->getBody();
                        $content = $body->getContents();
                        preg_match('/<div id="content">(.*?)<\/div>/si', $content, $mat);
                        if (!empty($mat[1])) {
                            $content = trim($mat[1]);
                            $content = str_replace(['　', '</br>'], '', $content);
                            $pattern = [
                                '/<script>(.*?)<\/script>/si',
                                '/([ \s]+)/si',
                            ];
                            $content = preg_replace($pattern, '', $content);
                            $content = explode('<br/>', $content);
                            $content = array_map(function($value){
                                $value = trim($value);
                                $value = htmlspecialchars_decode($value, ENT_QUOTES);
                                $value = strip_tags($value);
                                return $value;
                            }, $content);
                            $content = array_filter($content);
                            $content = implode('<br/>', $content);
                        }
                        halt($content);
                    }
                }
            }


            # code...
        }
        // return $this->fetch('index');
    }
}
