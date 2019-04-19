<?php
/**
 *
 * 控制层
 * 错误
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

use app\library\Template;

class Error extends Template
{

    /**
     * 构造方法
     * @access public
     * @param  App  $app  应用对象
     * @return void
     */
    public function __construct()
    {
        $this->setTheme('error/default');

        parent::__construct();
    }

    /**
     * 错误页
     * @access public
     * @param
     * @return mixed
     */
    public function index()
    {
        $this->fetch('error');
        // $this->tpl('error');
    }

    public function _404()
    {
        $this->tpl('404');
    }

    public function _500()
    {
        $this->tpl('500');
    }


    private function tpl(string $_code)
    {
        $tpl = app()->getRootPath() . 'public' . DIRECTORY_SEPARATOR .
                'template' . DIRECTORY_SEPARATOR .
                $_code . '.html';

        clearstatcache();

        // 页面缓存
        ob_start();
        ob_implicit_flush(0);

        echo file_get_contents($tpl);

        // 获取并清空缓存
        $content = ob_get_clean();

        echo $content;
    }
}
