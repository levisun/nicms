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

use think\facade\Log;

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
        // 过滤前后字符与空格
        $_dir = DataFilter::filter($_dir);

        $_dir = DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $_dir) . DIRECTORY_SEPARATOR;

        $day = 0 === $_expire ? $_expire : strtotime('-' . $_expire . ' days');
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
            if ('.' === $file || '..' === $file) {
                continue;
            } elseif (is_dir($_dir . $file)) {
                $this->clear($_dir . $file . DIRECTORY_SEPARATOR, $_time);
                @rmdir($_dir . $file);
            } elseif (is_file($_dir . $file) && 0 === $_time) {
                @unlink($_dir . $file);
            } elseif (is_file($_dir . $file) && filemtime($_dir . $file) <= $_time) {
                @unlink($_dir . $file);
            }
        }
    }
}
