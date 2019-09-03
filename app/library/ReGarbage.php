<?php

/**
 *
 * 删除运行垃圾文件
 *
 * @package   NICMS
 * @category  app\library
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace app\library;

class ReGarbage
{

    /**
     * 删除运行垃圾文件
     * @access public
     * @param
     * @return bool
     */
    public function run(): void
    {
        $lock = app()->getRuntimePath() . 'temp' . DIRECTORY_SEPARATOR . md5(__DIR__ . 'remove_garbage') . '.lock';
        clearstatcache();
        if (is_file($lock) && filemtime($lock) >= strtotime('-1 days')) {
            return;
        }
        if ($fp = @fopen($lock, 'w+')) {
            if (flock($fp, LOCK_EX | LOCK_NB)) {
                // app('log')->record('[REGARBAGE] 删除垃圾信息', 'info');
                $runtime_path = app()->getRuntimePath();
                $this->remove($runtime_path . 'cache', 7);
                // $this->remove($runtime_path . 'compile', 30);
                $this->remove($runtime_path . 'log', 7);
                $this->remove($runtime_path . 'temp', 1);

                $root_path = app()->getRootPath() . 'public' . DIRECTORY_SEPARATOR;
                $this->remove($root_path . 'sitemaps', 1);
                $this->remove($root_path . 'storage', 30);

                fwrite($fp, '清除垃圾数据' . date('Y-m-d H:i:s'));
                flock($fp, LOCK_UN);
            }
            fclose($fp);
        }
    }

    /**
     * 清理目录中的垃圾信息
     * @access public
     * @param  string $_dir
     * @param  int    $_expire
     * @return void
     */
    public function remove(string $_dir, int $_expire): void
    {
        $dir = $this->getDirAllFile($_dir . DIRECTORY_SEPARATOR . '*', $_expire);
        while ($dir->valid()) {
            $filename = $dir->current();
            if (is_dir($filename)) {
                // @rmdir($filename);
            } elseif (is_file($filename) && false === strpos($filename, '_skl')) {
                @unlink($filename);
            }
            $dir->next();
        }
    }

    /**
     * 获得目录中的所有文件与目录
     * @access private
     * @param  array $_path
     * @param  int   $_expire
     * @return
     */
    private function getDirAllFile(string $_path, int $_expire)
    {
        $day = strtotime('-' . $_expire . ' days');
        $dir = (array) glob($_path);
        foreach ($dir as $files) {
            if (is_file($files) && filemtime($files) <= $day) {
                yield $files;
            } elseif (is_dir($files . DIRECTORY_SEPARATOR)) {
                $sub = $this->getDirAllFile($files . DIRECTORY_SEPARATOR . '*', $_expire);
                if (!$sub->valid()) {
                    yield $files;
                }

                while ($sub->valid()) {
                    $filename = $sub->current();
                    if (is_file($filename) && filemtime($filename) <= $day) {
                        yield $filename;
                    } elseif (is_dir($filename . DIRECTORY_SEPARATOR)) {
                        yield $filename;
                    }
                    $sub->next();
                }
            }
        }
    }
}
