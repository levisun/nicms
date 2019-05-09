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
        $runtime_path = app()->getRuntimePath();
        $root_path = app()->getRootPath();

        $this->remove($runtime_path . 'cache', 4);
        $this->remove($runtime_path . 'concurrent', 4);
        $this->remove($runtime_path . 'compile', 72);
        $this->remove($runtime_path . 'log', 72);
        $this->remove($root_path . 'public' . DIRECTORY_SEPARATOR . 'sitemaps', 72);

        $sub_dir = (int)date('Ym');
        --$sub_dir;
        $this->remove($root_path . 'public' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . $sub_dir . DIRECTORY_SEPARATOR, 168);

        unset($runtime_path, $root_path);
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
            if (false === strpos($filename, '_skl_')) {
                Log::record('[REGARBAGE] ' . pathinfo($filename, PATHINFO_BASENAME) . ' 删除垃圾信息', 'alert');
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
        $days = strtotime('-' . $_expire . ' hour');
        foreach ($dir as $files) {
            if (is_file($files) && filemtime($files) <= $days) {
                yield $files;
            } elseif (is_dir($files . DIRECTORY_SEPARATOR)) {
                // yield $files;
                $sub = $this->getDirAllFile($files . DIRECTORY_SEPARATOR . '*', $_expire);
                while ($sub->valid()) {
                    $filename = $sub->current();
                    if (is_file($filename) && filemtime($filename) <= $days) {
                        yield $filename;
                    }
                    $sub->next();
                }
            }
        }
    }
}
