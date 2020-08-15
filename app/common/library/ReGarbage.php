<?php

/**
 *
 * 删除运行垃圾文件
 *
 * @package   NICMS
 * @category  app\common\library
 * @author    失眠小枕头 [312630173@qq.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace app\common\library;

class ReGarbage
{

    /**
     * 清理目录中的垃圾信息
     * @access public
     * @static
     * @param  string $_dir
     * @param  string $_expire '-1 month' or '-1 day' or '-1 hour' or ...
     * @return void
     */
    public static function clear(string $_dir, string $_expire = ''): void
    {
        $_dir = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $_dir) . DIRECTORY_SEPARATOR;
        $timestamp = $_expire ? strtotime($_expire) : 0;

        if ($files = glob($_dir . '*')) {
            foreach ($files as $file) {
                if (is_dir($file)) {
                    self::clear($file . DIRECTORY_SEPARATOR, $_expire);
                    @rmdir($file);
                } elseif (is_file($file) && 0 === $timestamp) {
                    @unlink($file);
                } elseif (is_file($file) && filemtime($file) <= $timestamp) {
                    @unlink($file);
                }
            }
        }
    }

    /**
     * 保证网站根目录整洁
     * @access public
     * @static
     * @return void
     */
    public static function publicDirTidy(): void
    {
        $keep = [
            'file' => [
                'index.php',
                'robots.txt',
                'sitemap.xml',
                'favicon.ico',
            ],
            'ext' => [
                'htaccess',
                'nginx',
                'html',
                'ini',
                'env',
                'yaml',
            ],
        ];

        $dir = public_path();
        if ($files = glob($dir . '*')) {
            foreach ($files as $dir_file) {
                // 跳过目录
                if (is_dir($dir_file)) {
                    continue;
                }
                // 文件
                elseif (is_file($dir_file)) {
                    // 跳过文件
                    $name = strtolower(pathinfo($dir_file, PATHINFO_BASENAME));
                    if (in_array($name, $keep['file'])) {
                        continue;
                    }

                    $ext = strtolower(pathinfo($dir_file, PATHINFO_EXTENSION));
                    if (in_array($ext, $keep['ext'])) {
                        continue;
                    }

                    @unlink($dir_file);
                }
            }
        }
    }

    /**
     * 删除上传目录中的空目录
     * @access public
     * @static
     * @param  string $_dir
     * @return void
     */
    public static function uploadEmptyDirectory(string $_dir = ''): void
    {
        $_dir = $_dir ? $_dir : public_path('storage/uploads');

        if ($files = glob($_dir . '*')) {
            foreach ($files as $file) {
                if (is_dir($file)) {
                    self::uploadEmptyDirectory($file . DIRECTORY_SEPARATOR);
                }
            }
        } else {
            @rmdir($_dir);
        }
    }
}
