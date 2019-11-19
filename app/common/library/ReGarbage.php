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
     * @return $this
     */
    public function remove(string $_dir, int $_expire)
    {
        $_dir = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, trim($_dir, " \/,._-\t\n\r\0\x0B"));
        $_dir .= DIRECTORY_SEPARATOR;

        $day = strtotime('-' . $_expire . ' days');
        $this->clear($_dir, $day);
    }

    /**
     * 删除文件
     * @access private
     * @param  string $_dir
     * @param  int    $_time
     * @return void
     */
    private function clear(string $_dir, int $_time): void
    {
        $files = is_dir($_dir) ? scandir($_dir) : [];

        foreach ($files as $file) {
            if ('.' != $file && '..' != $file && is_dir($_dir . $file)) {
                $this->clear($_dir . $file . DIRECTORY_SEPARATOR, $_time);
                @rmdir($_dir . $file);
            } elseif (is_file($_dir . $file) && filemtime($_dir . $file) <= $_time) {
                @unlink($_dir . $file);
            }
        }
    }
}
