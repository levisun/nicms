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
        Log::record('[REGARBAGE] 删除垃圾信息', 'alert');
        $runtime_path = app()->getRuntimePath();
        $root_path = app()->getRootPath();

        $this->remove($runtime_path . 'cache', 4);
        $this->remove($runtime_path . 'compile', 72);
        $this->remove($runtime_path . 'lock', 24);
        $this->remove($runtime_path . 'log', 72);
        $this->remove($root_path . 'public' . DIRECTORY_SEPARATOR . 'sitemaps', 24);

        $dir = (array)glob($root_path . 'public' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . '*');
        foreach ($dir as $path) {
            $date = (int)date('Ym');
            $this->remove($path . DIRECTORY_SEPARATOR . $date, 168);
            --$date;
            $this->remove($path . DIRECTORY_SEPARATOR . $date, 168);
        }

        unset($runtime_path, $root_path, $dir, $date);
    }

    /**
     * 清理目录中的垃圾信息
     * @access private
     * @param  string $_dir
     * @param  int    $_expire
     * @return void
     */
    private function remove(string $_dir, int $_expire): void
    {
        $dir = $this->getDirAllFile($_dir . DIRECTORY_SEPARATOR . '*', $_expire);
        while ($dir->valid()) {
            $filename = $dir->current();
            Log::record(pathinfo($filename, PATHINFO_BASENAME), 'alert');
            if (is_dir($filename)) {
                @rmdir($filename);
            } elseif (false === strpos($filename, '_skl_')) {
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
        $dir = (array)glob($_path);
        $hour = strtotime('-' . $_expire . ' hour');
        foreach ($dir as $files) {
            if (is_file($files) && filemtime($files) <= $hour) {
                yield $files;
            } elseif (is_dir($files . DIRECTORY_SEPARATOR)) {
                $sub = $this->getDirAllFile($files . DIRECTORY_SEPARATOR . '*', $_expire);
                if (!$sub->valid()) {
                    yield $files;
                }

                while ($sub->valid()) {
                    $filename = $sub->current();
                    if (is_file($filename) && filemtime($files) <= $hour) {
                        yield $filename;
                    }
                    $sub->next();
                }
            }
        }
    }
}
