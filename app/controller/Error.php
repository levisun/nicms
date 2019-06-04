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
     * @param
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
     * @return void
     */
    public function index(): void
    {
        $this->fetch('error');
    }

    public function _404(): void
    {
        $this->fetch('404');
    }

    /**
     * 服务器繁忙
     * @access public
     * @param
     * @return void
     */
    public function _500(): void
    {
        $this->fetch('500');
    }
}
