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
use app\common\model\Category as ModelCategory;
use app\common\library\Siteinfo;

class Details extends BaseController
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
     * 详情页
     * @access public
     * @return void
     */
    public function index()
    {
        $result = (new ModelCategory)->view('category', ['id'])
            ->view('model', ['name' => 'theme_name'], 'model.id=category.model_id')
            ->where([
                ['category.is_show', '=', 1],
                ['category.id', '=', $this->request->param('cid/d')],
            ])
            ->cache(true)
            ->find();
        if ($result && $result = $result->toArray()) {
            return $this->fetch($result['theme_name'] . '_details');
        } else {
            return miss(404);
        }
    }
}