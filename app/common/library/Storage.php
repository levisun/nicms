<?php

/**
 *
 * 存储
 *
 * @package   NICMS
 * @category  app\common\library
 * @author    失眠小枕头 [312630173@qq.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2021
 */

declare(strict_types=1);

namespace app\common\library;

use think\facade\Request;
use think\Image as ThinkImage;
use app\common\library\Base64;

class Storage
{
    private static $mime_type = [
        'js'   => 'application/javascript',
        'css'  => 'text/css',

        'bmp'  => 'image/bmp',
        'gif'  => 'image/gif',
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png'  => 'image/png',
        'webp' => 'image/webp',

        'mp3'  => 'audio/mpeg',
        'mp4'  => 'video/mp4',

        'pdf'  => 'application/pdf',
    ];

    public static function images()
    {
        // 访问来源合法性
        $referer = parse_url(Request::server('HTTP_REFERER'), PHP_URL_HOST);
        if (!$referer || false === stripos($referer, Request::rootDomain())) {
            trace('MISS ' . Request::ip(), 'warning');
            // return miss(404);
        }

        // 文件路径
        $file_path = str_replace('/', DIRECTORY_SEPARATOR, trim(Request::baseUrl(), '\/'));
        // 文件后缀并判断合法性
        $extension = pathinfo($file_path, PATHINFO_EXTENSION);
        if (!in_array($extension, ['gif', 'jpg', 'jpeg', 'png', 'bmp', 'webp'])) {
            trace('MISS ' . Request::ip(), 'warning');
            return miss(404);
        }

        // 模板目录
        $theme_dir = public_path('theme');
        // 静态资源目录
        $static_dir = public_path('static');
        // 上传存储目录
        $storage_dir = public_path('storage/uploads');

        if (is_file($theme_dir . $file_path)) {
            $file_path = $theme_dir . $file_path;
        } elseif (is_file($static_dir . $file_path)) {
            $file_path = $static_dir . $file_path;
        } elseif (is_file($storage_dir . $file_path)) {
            $file_path = $storage_dir . $file_path;
        } else {
            trace('MISS ' . Request::ip(), 'warning');
            return miss(404);
        }

        $width = Request::param('width/d', 0);
        $height = Request::param('height/d', 0);
        if ($width || $height) {
            $file_path = self::thumb($file_path, $width, $height);
        }

        return self::response($file_path);
    }

    public static function static()
    {
        // 访问来源合法性
        $referer = parse_url(Request::server('HTTP_REFERER'), PHP_URL_HOST);
        if (!$referer || false === stripos($referer, Request::rootDomain())) {
            trace('MISS ' . Request::ip(), 'warning');
            // return miss(404);
        }

        // 文件路径
        $file_path = str_replace('/', DIRECTORY_SEPARATOR, trim(Request::baseUrl(), '\/'));
        // 文件后缀并判断合法性
        $extension = pathinfo($file_path, PATHINFO_EXTENSION);
        if (!in_array($extension, ['js', 'css'])) {
            trace('MISS ' . Request::ip(), 'warning');
            return miss(404);
        }

        // 模板目录
        $theme_dir = public_path('theme');
        // 静态资源目录
        $static_dir = public_path('static');
        // 上传存储目录
        $storage_dir = public_path('storage/uploads');

        if (is_file($theme_dir . $file_path)) {
            return self::response($theme_dir . $file_path);
        } elseif (is_file($static_dir . $file_path)) {
            return self::response($static_dir . $file_path);
        } else {
            trace('MISS ' . Request::ip(), 'warning');
            return miss(404);
        }
    }

    /**
     * 缩略图
     * @access public
     * @static
     * @param  string $_file
     * @param  int    $_size 整十数
     * @return string
     */
    private static function thumb(string $_img, int $_width = 100, int $_height = 0): string
    {
        $_width = intval($_width / 10) * 10;
        $_width = 800 > $_width ? $_width : 800;
        $_width = 10 < $_width ? $_width : 10;

        if ($_height) {
            $_height = intval($_height / 10) * 10;
            $_height = 800 > $_height ? $_height : 800;
            $_height = 10 < $_height ? $_height : 10;
        } else {
            $_height = $_width;
        }

        $_img = trim($_img, '\/.');
        $_img = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $_img);

        $thumb_file = md5($_img . $_width) . '.' . pathinfo($_img, PATHINFO_EXTENSION);

        $path = runtime_path('thumb/' . substr($thumb_file, 0, 2));
        if (!is_dir($path)) mkdir($path, 0755, true);

        if (!is_file($path . $thumb_file)) {
            @ini_set('memory_limit', '128M');
            $image = ThinkImage::open($_img);
            if ($image->width() > $_width || $image->height() > $_height) {
                $_width = $image->width() > $_width ? $_width : $image->width();
                $_height = $image->height() > $_height ? $_height : $image->height();
                $image->thumb($_width, $_height, ThinkImage::THUMB_SCALING);
                $image->save($path . $thumb_file);
                unset($image);
            } else {
                return $_img;
            }
        }

        return $path . $thumb_file;
    }

    private static function response(string $_file_path)
    {
        // SPL扩展
        // 直接输出避免内存溢出
        $fileObject = new \SplFileObject($_file_path, 'r');
        while (!$fileObject->eof()) {
            echo $fileObject->fgetc();
        }

        return \think\Response::create()->allowCache(true)
            ->contentType(self::$mime_type[pathinfo($_file_path, PATHINFO_EXTENSION)])
            ->cacheControl('max-age=2592000,must-revalidate')
            ->lastModified(gmdate('D, d M Y H:i:s') . ' GMT')
            ->expires(gmdate('D, d M Y H:i:s', time() + 2592000) . ' GMT');
    }
}
