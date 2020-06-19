<?php

/**
 *
 * 删除运行垃圾文件
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

class ReGarbage
{

    /**
     * 清理目录中的垃圾信息
     * @access public
     * @static
     * @param  string $_dir
     * @param  int    $_time
     * @return void
     */
    public static function clear(string $_dir, int $_expire): void
    {
        $_dir = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $_dir) . DIRECTORY_SEPARATOR;
        $timestamp = (0 === $_expire) ? $_expire : strtotime('-' . $_expire . ' days');

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
     * 删除上传目录中的空目录
     * @access public
     * @static
     * @param  string $_dir
     * @return void
     */
    public static function upload(string $_dir = ''): void
    {
        $_dir = $_dir ?: public_path('storage/uploads');

        if ($files = glob($_dir . '*')) {
            foreach ($files as $file) {
                if (is_dir($file)) {
                    self::upload($file . DIRECTORY_SEPARATOR);
                }
            }
        } else {
            @rmdir($_dir);
        }
    }

    /**
     * 保证网站根目录整洁
     * @access public
     * @static
     * @return void
     */
    public static function public_dir(): void
    {
        $dir = public_path();
        $files = is_dir($dir) ? scandir($dir) : [];
        foreach ($files as $dir_file) {
            if (in_array($dir_file, ['.', '..'])) {
                continue;
            }

            // 跳过目录
            elseif (is_dir($dir . $dir_file) && in_array($dir_file, ['screen', 'static', 'storage', 'theme'])) {
                continue;
            }

            // 文件
            elseif (is_file($dir . $dir_file)) {
                // 跳过文件
                if (in_array($dir_file, ['index.php', 'robots.txt', 'sitemap.xml', 'favicon.ico'])) {
                    continue;
                }

                // 跳过配置文件
                $ext = strtolower(pathinfo($dir . $dir_file, PATHINFO_EXTENSION));
                if (in_array($ext, ['htaccess', 'nginx', 'ini', 'env', 'yaml'])) {
                    continue;
                }

                $name = (int) pathinfo($dir . $dir_file, PATHINFO_FILENAME);
                if ($ext == 'html' && $name) {
                    continue;
                }
            }

            if (is_dir($dir . $dir_file)) {
                self::clear($dir . $dir_file, 0);
            } elseif (is_file($dir . $dir_file)) {
                @unlink($dir . $dir_file);
            }
        }
    }
}
