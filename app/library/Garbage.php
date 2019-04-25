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

use think\App;
use think\facade\Log;
use think\facade\Request;
use app\library\Base64;

class Garbage
{

     /**
     * 删除运行垃圾文件
     * @access public
     * @param
     * @return bool
     */
    public function run()
    {
        Log::record('[GARBAGE] 删除垃圾信息', 'alert');

        $this->remove(app()->getRuntimePath() . 'cache', 4);
        $this->remove(app()->getRuntimePath() . 'concurrent', 24);
        $this->remove(app()->getRuntimePath() . 'log', 72);
        $this->remove(app()->getRootPath() . 'public' . DIRECTORY_SEPARATOR . 'sitemaps', 72);
        $this->remove(app()->getRootPath() . 'public' . DIRECTORY_SEPARATOR . 'uploads', 168);
    }

    /**
     * 清理目录中的垃圾信息
     * @access private
     * @param  string $_dir
     * @param  int    $_expire
     * @return void
     */
    private function remove($_dir, int $_expire): void
    {
        $dirOrFile = (array) glob($_dir . DIRECTORY_SEPARATOR . '*');
        $dirOrFile = $this->getAllFile($dirOrFile, $_expire);

        if (!empty($dirOrFile)) {
            // 随机抽取1000条信息
            shuffle($dirOrFile);
            $dirOrFile = array_slice($dirOrFile, 0, 1000);

            clearstatcache();
            foreach ($dirOrFile as $path) {
                if (is_file($path) && pathinfo($path, PATHINFO_BASENAME) == '.gitignore') {
                    continue;
                }
                if (is_file($path) && stripos($path, '_skl_') === false) {
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
            if (is_file($path)) {
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
