<?php

/**
 *
 * 模板编译
 *
 * @package   NICMS
 * @category  view
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace view;

class Taglib
{
    protected $params = [];
    protected $config = [];


    /**
     * 架构函数
     * @access public
     * @param  array  $_config
     * @return void
     */
    public function __construct(array $_params, array $_config)
    {
        $this->params = $_params;
        $this->config = $_config;
    }

    public function handle(): string
    {
        return '';
    }
}
