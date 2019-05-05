<?php
/**
 *
 * API接口层
 * 权限判断
 *
 * @package   NICMS
 * @category  app\logic\admin
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */
declare (strict_types = 1);

namespace app\logic\admin;

use think\facade\Request;
use app\library\Common;

class Base extends Common
{

    /**
     * 构造
     */
    public function __construct()
    {
        if (!Request::isPost()) {
            return [
                'debug' => false,
                'cache' => false,
                'msg'   => 'request error'
            ];
        }
    }

    /**
     * 上传
     * @access public
     * @param
     * @return string|array
     */
    public function upload()
    {
        if ($result = $this->authenticate(__METHOD__)) {
            return $result;
        }

        return $this->__upload('admin_auth_key');
    }

    /**
     * 数据验证
     * @access protected
     * @param  string  $_validate
     * @param  array  $_data
     * @return bool|string
     */
    protected function validate(string $_validate, array $_data = [])
    {
        return parent::__validate($_validate, $_data, 'validate\admin');
    }

    /**
     * 记录操作日志
     * @access protected
     * @param  string $_method
     * @param  string $_msg
     * @return void
     */
    protected function writeLog(string $_method, string $_msg): void
    {
        parent::__writeLog($_method, $_msg, 'admin_auth_key');
    }

    /**
     * 权限验证
     * @access protected
     * @param  string  $_logic
     * @param  string  $_controller
     * @param  string  $_action
     * @return bool|array
     */
    protected function authenticate(string $_method, string $_msg = '')
    {
        return parent::__authenticate($_method, 'admin_auth_key');
    }
}
