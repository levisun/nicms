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
     * @param  string $_dir
     * @param  int    $_expire
     * @return this
     */
    public function remove(string $_dir, int $_expire)
    {
        $dir = $this->getDirAllFile($_dir . DIRECTORY_SEPARATOR . '*', $_expire);
        while ($dir->valid()) {
            $filename = $dir->current();
            if (is_dir($filename)) {
                // @rmdir($filename);
            } elseif (is_file($filename)) {
                @unlink($filename);
            }
            $dir->next();
        }

        return $this;
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
