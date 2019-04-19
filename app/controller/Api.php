<?php
/**
 *
 * 控制层
 * Api
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

use think\facade\Request;
use app\library\Async;
use app\library\Ip;

class Api extends Async
{

    /**
     * 构造方法
     * @access public
     * @param  App  $app  应用对象
     * @return void
     */
    public function __construct()
    {
        if (!Request::server('HTTP_REFERER', null)) {
            $this->error('request error');
        }
    }

    /**
     * 查询接口
     * @access public
     * @param  string $name API分层名
     * @return void
     */
    public function query(string $name = 'cms')
    {
        if ($name == 'ip') {
            $ip = Ip::info();
            $this->success('success', $ip);
        } elseif (Request::isGet() && $name) {
            $this->setModule($name)->run();
        } else {
            $this->error('request error');
        }
    }

    /**
     * 操作接口
     * @access public
     * @param  string $name API分层名
     * @return void
     */
    public function handle(string $name = 'cms')
    {
        if (Request::isPost() && $name) {
            $this->setModule($name)->run();
        } else {
            $this->error('request error');
        }
    }

    /**
     * 上传接口
     * @access public
     * @param
     * @return void
     */
    public function upload(string $name = 'cms')
    {
        if (Request::isPost() && $name && !empty($_FILES)) {
            $this->setModule($name)->run();
        } else {
            $this->error('request error');
        }
    }
}
