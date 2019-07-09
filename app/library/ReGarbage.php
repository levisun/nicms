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
declare (strict_types = 1);

namespace app\library;

use think\facade\Log;

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
        $lock = app()->getRuntimePath() . 'remove_garbage.lock';
        clearstatcache();
        if (is_file($lock) && filemtime($lock) >= strtotime('-1 days')) {
            return;
        }
        if ($fp = @fopen($lock, 'w+')) {
            if (flock($fp, LOCK_EX | LOCK_NB)) {
                Log::record('[REGARBAGE] 删除垃圾信息', 'alert');
                $runtime_path = app()->getRuntimePath();
                $root_path = app()->getRootPath();

                $this->remove($runtime_path . 'cache', 3);
                $this->remove($runtime_path . 'compile', 7);
                $this->remove($runtime_path . 'log', 3);
                $this->remove($root_path . 'public' . DIRECTORY_SEPARATOR . 'sitemaps', 1);

                $dir = (array)glob($root_path . 'public' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . '*');
                foreach ($dir as $path) {
                    $date = (int)date('Ym');
                    $this->remove($path . DIRECTORY_SEPARATOR . $date, 192);
                    --$date;
                    $this->remove($path . DIRECTORY_SEPARATOR . $date, 192);
                }

                unset($runtime_path, $root_path, $dir, $date);

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
            Log::record(pathinfo($filename, PATHINFO_BASENAME), 'alert');
            if (is_dir($filename)) {
                @rmdir($filename);
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
        $dir = (array)glob($_path);
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
