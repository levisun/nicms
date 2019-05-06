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

class Base
{

    /**
     * 上传
     * @access public
     * @param
     * @return string|array
     */
    public function upload()
    {
        // 用户权限校验
        if (Request::isPost() && !empty($_FILES) && session('?user_auth_key')) {
            $input_name = Request::param('input_name', 'upload');
            $result = (new Upload)->save($input_name);
        } else {
            $result = 'upload error';
        }

        return $result;
    }
}
