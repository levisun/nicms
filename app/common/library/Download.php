<?php

/**
 *
 * 下载类
 *
 * @package   NICMS
 * @category  app\common\library
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace app\common\library;

use think\facade\Config;
use think\facade\Request;
use think\Response;
use app\common\library\Base64;
use app\common\library\DataFilter;

class Download
{
    private static $extension = [
        'doc', 'docx', 'gif', 'gz', 'jpeg', 'mp4', 'pdf', 'png', 'ppt', 'pptx', 'rar', 'xls', 'xlsx', 'zip', '7z',
        'webp',
    ];

    /**
     * 获得文件下载地址
     * @access public
     * @static
     * @param  string $_filename
     * @return string
     */
    public static function getUrl(string $_filename): string
    {
        $_filename = DataFilter::filter($_filename);
        $_filename = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $_filename);
        $_filename = str_replace(['storage' . DIRECTORY_SEPARATOR, 'uploads' . DIRECTORY_SEPARATOR], '', $_filename);
        $salt = md5(date('Ymd') . Request::server('HTTP_USER_AGENT'));
        $_filename = Base64::encrypt($_filename, $salt);
        return Config::get('app.api_host') . '/download.do?file=' . urlencode($_filename);
    }

    /**
     * 下载文件
     * @access public
     * @static
     * @param  string $_filename
     * @return void
     */
    public static function file(string $_filename): Response
    {
        $salt = md5(date('Ymd') . Request::server('HTTP_USER_AGENT'));
        $_filename = $_filename ? Base64::decrypt($_filename, $salt) : '';

        if ($_filename && false !== preg_match('/^[a-zA-Z0-9_\/\\\]+\.[a-zA-Z0-9]{2,4}$/u', $_filename)) {
            $_filename = DataFilter::filter($_filename);
            $_filename = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $_filename);

            $path = Config::get('filesystem.disks.public.root') . DIRECTORY_SEPARATOR .'uploads' . DIRECTORY_SEPARATOR;
            $path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);

            $ext = pathinfo($path . $_filename, PATHINFO_EXTENSION);
            if (is_file($path . $_filename) && in_array($ext, self::$extension)) {
                return Response::create($path . $_filename, 'file')
                    ->name(md5($_filename . $salt))
                    ->isContent(false)
                    ->expire(28800);
            }
        }
    }
}
