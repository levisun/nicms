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
        $this->remove($runtime_path . 'log', 72);
        $this->remove($root_path . 'public' . DIRECTORY_SEPARATOR . 'sitemaps', 72);

        $sub_dir = (int) date('Ym');
        --$sub_dir;
        $this->remove($root_path . 'public' . DIRECTORY_SEPARATOR . 'uploads' . $sub_dir . DIRECTORY_SEPARATOR, 168);

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
        $dirOrFile = (array) glob($_dir . DIRECTORY_SEPARATOR . '*');
        $dirOrFile = $this->getAllFile($dirOrFile, $_expire);

        if (!empty($dirOrFile)) {
            Log::record('[REGARBAGE] ' . pathinfo($_dir, PATHINFO_BASENAME) . ' 删除垃圾信息', 'alert');

            // 随机抽取1000条信息
            shuffle($dirOrFile);
            $dirOrFile = array_slice($dirOrFile, 0, 1000);

            clearstatcache();
            foreach ($dirOrFile as $path) {
                if (is_file($path) && pathinfo($path, PATHINFO_BASENAME) == '.gitignore') {
                    continue;
                }
                if (is_file($path) && false === stripos($path, '_skl_')) {
                    @unlink($path);
                } elseif (is_dir($path)) {
                    @rmdir($path);
                }
            }
        }
    }

    /**
     * 获得目录中的所有文件与目录
     * @access private
     * @param  array $_dirOrFile
     * @param  int   $_expire
     * @return array
     */
    private function getAllFile(array $_dirOrFile, int $_expire): array
    {
        $days = strtotime('-' . $_expire . ' hour');

        $all_files = [];
        foreach ($_dirOrFile as $key => $path) {
            if (is_file($path) && false === stripos($path, '_skl_')) {
                // 过滤未过期文件
                if (filectime($path) <= $days) {
                    $all_files[] = $path;
                }
            } elseif (is_dir($path . DIRECTORY_SEPARATOR)) {
                $temp = (array) glob($path . DIRECTORY_SEPARATOR . '*');
                if (!empty($temp)) {
                    $temp = $this->getAllFile($temp, $_expire);
                    $all_files = array_merge($all_files, $temp);
                    unset($temp);
                } else {
                    $all_files[] = $path;
                }
            }
        }

        return $all_files;
    }
}
