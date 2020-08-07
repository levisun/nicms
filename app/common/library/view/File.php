<?php

/**
 *
 * 模板文件
 *
 * @package   NICMS
 * @category  app\common\library\view
 * @author    失眠小枕头 [312630173@qq.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace app\common\library\view;

use think\Response;
use think\exception\HttpResponseException;
use think\facade\Request;

class File
{
    private static $includeFile = [];

    public static function getIncludeFile(): array
    {
        return self::$includeFile;
    }

    /**
     * 获得模板(包含路径)
     * @access public
     * @static
     * @return string
     */
    public static function getTheme(string &$_view_path, string &$_template = ''): string
    {
        if (is_file($_view_path . self::getMobilePath($_view_path) . $_template)) {
            $path = $_view_path . self::getMobilePath($_view_path) . $_template;
            self::$includeFile[$path] = time();
            return $path;
        } else {
            if (app()->isDebug()) {
                $error = 'template not exists:' . $_template;
                $response = Response::create($error, 'html', 200);
                throw new HttpResponseException($response);
            } else {
                miss(403, false, true);
            }
        }
    }

    /**
     * 判断移动端, 返回移动目录与微信目录
     * @access private
     * @static
     * @return string
     */
    private static function getMobilePath(string &$_view_path)
    {
        if (Request::isMobile()) {
            // 微信端模板
            if (is_wechat() && is_dir($_view_path . 'wechat' . DIRECTORY_SEPARATOR)) {
                return 'wechat' . DIRECTORY_SEPARATOR;
            } elseif (is_dir($_view_path . 'mobile' . DIRECTORY_SEPARATOR)) {
                return 'mobile' . DIRECTORY_SEPARATOR;
            }
        }
    }
}
