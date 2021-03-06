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

use app\common\library\tools\File;

class ClearGarbage
{

    /**
     * 清理目录中的垃圾信息
     * @access public
     * @static
     * @param  string $_dir
     * @param  string $_expire -1 month or -1 day or -1 hour or ...
     * @return void
     */
    public static function clear(string $_dir, string $_expire = ''): void
    {
        $_dir = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $_dir) . DIRECTORY_SEPARATOR;
        $timestamp = $_expire ? strtotime($_expire) : 0;

        clearstatcache();
        $glob = File::glob($_dir);
        while ($glob->valid()) {
            $filename = $glob->current();
            $glob->next();

            if (is_file($filename) && 0 === $timestamp) {
                @unlink($filename);
            } elseif (is_file($filename) && filemtime($filename) <= $timestamp) {
                @unlink($filename);
            } elseif (is_dir($filename)) {
                @rmdir($filename);
            }
        }
    }

    /**
     * 清除过期无效缓存
     * @access public
     * @static
     * @return void
     */
    public static function clearCache(string $_dir = '')
    {
        $_dir = $_dir ?: runtime_path('cache');
        $glob = File::glob($_dir);
        clearstatcache();
        while ($glob->valid()) {
            $filename = $glob->current();
            $glob->next();

            if (is_file($filename) && strtotime('-12 hour') > filemtime($filename)) {
                @unlink($filename);
            } elseif (is_file($filename) && strtotime('-1 hour') > filemtime($filename)) {
                if ($content = @file_get_contents($filename)) {
                    $expire = (int) substr($content, 8, 12);
                    if (0 != $expire && time() - $expire > filemtime($filename)) {
                        @unlink($filename);
                    }
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
                '404.html',
                '502.html',
                'dead.txt',
                'favicon.ico',
                'index.php',
                'robots.txt',
                'sitemap.xml',
            ],
            'ext' => [
                'htaccess',
                'nginx',
                'ini',
                'env',
                'yaml',
            ],
        ];

        if ($files = glob(public_path() . '*')) {
            foreach ($files as $dir_file) {
                if (!is_dir($dir_file) && is_file($dir_file)) {
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
     * @return void
     */
    public static function uploadEmptyDirectory(): void
    {
        $glob = File::glob(public_path('storage/uploads'));
        $dir = '';
        while ($glob->valid()) {
            $filename = $glob->current();
            $glob->next();

            if (is_dir($filename)) {
                $dir = $filename;
            } elseif ($dir && is_file($filename) && false === strpos($filename, $dir)) {
                @rmdir($dir);
                $dir = '';
            }
        }
        if ($dir) {
            @rmdir($dir);
        }
    }
}
