<?php
/**
 *
 * API接口层
 * 文件上传
 *
 * @package   NICMS
 * @category  app\service\cms\upload
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */
declare (strict_types = 1);

namespace app\service\cms\upload;

use think\facade\Cache;
use think\facade\Config;
use think\facade\Lang;
use think\facade\Request;
use app\library\Base64;
use app\library\Upload;


class File extends BaseService
{
    public function save(): array
    {
        // 用户权限校验
        if (session('?member_auth_key')) {
            $input_name = Request::param('input_name', 'upload');
            $result = (new Upload)->save($input_name);
        } else {
            $result = Lang::get('upload error');
        }

        return [
            'debug' => false,
            'cache' => false,
            'msg'   => is_array($result) ? Lang::get('success') : Lang::get('error'),
            'data'  => $result
        ];
    }
}
