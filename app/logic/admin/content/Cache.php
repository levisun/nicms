<?php
/**
 *
 * API接口层
 * 缓存
 *
 * @package   NICMS
 * @category  app\logic\admin\content
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */
declare (strict_types = 1);

namespace app\logic\admin\content;

use think\facade\Lang;
use app\logic\admin\Base;

class Cache extends Base
{

    /**
     * 清除数据缓存
     * @access public
     * @param
     * @return array
     */
    public function reCache(): array
    {
        if ($result = $this->authenticate(__METHOD__, 'admin content cache reomve')) {
            return $result;
        }

        $dir = (array)glob(app()->getRuntimePath() . 'cache' . DIRECTORY_SEPARATOR . '*');
        foreach ($dir as $path) {
            $path = (array)glob($path . DIRECTORY_SEPARATOR . '*');
            array_map('unlink', $path);
        }

        return [
            'debug' => false,
            'cache' => false,
            'msg'   => Lang::get('remove cache success')
        ];
    }

    /**
     * 清除模板编译
     * @access public
     * @param
     * @return array
     */
    public function reCompile(): array
    {
        if ($result = $this->authenticate(__METHOD__, 'admin content compile reomve')) {
            return $result;
        }

        $dir = (array)glob(app()->getRuntimePath() . 'compile' . DIRECTORY_SEPARATOR . '*');
        foreach ($dir as $path) {
            $path = (array)glob($path . DIRECTORY_SEPARATOR . '*');
            array_map('unlink', $path);
        }

        return [
            'debug' => false,
            'cache' => false,
            'msg'   => Lang::get('remove compile success')
        ];
    }
}
