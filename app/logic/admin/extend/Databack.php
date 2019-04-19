<?php
/**
 *
 * API接口层
 * 数据库备份
 *
 * @package   NICMS
 * @category  app\logic\admin\extend
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */
declare (strict_types = 1);

namespace app\logic\admin\extend;

use think\facade\Config;
use think\facade\Lang;
use think\facade\Request;
use app\library\Backup;
use app\library\Base64;
use app\logic\admin\Base;

class Databack extends Base
{

    /**
     * 查询
     * @access public
     * @param
     * @return array
     */
    public function query(): array
    {
        $file = (array) glob(app()->getRuntimePath() . 'backup' . DIRECTORY_SEPARATOR . '*');
        sort($file);

        $date_format = Request::post('date_format', 'Y-m-d H:i:s');

        foreach ($file as $key => $value) {
            if (basename($value) == 'sys_auto') {
                unset($file[$key]);
            } else {
                $date = filectime($value);
                $date = date($date_format, $date);

                $value = basename($value);

                $child = (array) glob(app()->getRuntimePath() . 'backup' . DIRECTORY_SEPARATOR . $value . DIRECTORY_SEPARATOR . '*');
                $size = 0;
                foreach ($child as $k => $v) {
                    $size += filesize($v);
                }
                $size = number_format($size / 1024 / 1024, 2) . 'MB';

                $file[$key] = [
                    'id'   => Base64::encrypt($value),
                    'name' => $value,
                    'date' => $date,
                    'size' => $size,
                ];
            }
        }

        return [
            'debug' => false,
            'cache' => false,
            'msg'   => 'databack data',
            'data'  => [
                'list'  => $file,
                'total' => count($file)
            ]
        ];
    }

    /**
     * 备份数据库
     * @access public
     * @param
     * @return array
     */
    public function backup(): array
    {
        $this->__actionLog(__METHOD__, 'databack backup');

        try {
            (new Backup)->save();
            $msg = 'databack success';
        } catch (Exception $e) {
            $msg = 'databack error';
        }

        return [
            'debug' => false,
            'cache' => false,
            'msg'   => $msg,
        ];
    }

    /**
     * 删除
     * @access public
     * @param
     * @return array
     */
    public function remove(): array
    {
        $this->__actionLog(__METHOD__, 'databack backup remove');

        $id = Request::post('id');
        $id = Base64::decrypt($id);

        $file = (array) glob(app()->getRuntimePath() . 'backup' . DIRECTORY_SEPARATOR . $id . DIRECTORY_SEPARATOR . '*');
        foreach ($file as $key => $value) {
            if (is_file($value)) {
                unlink($value);
            }
        }
        if (is_dir(app()->getRuntimePath() . 'backup' . DIRECTORY_SEPARATOR . $id)) {
            rmdir(app()->getRuntimePath() . 'backup' . DIRECTORY_SEPARATOR . $id);
        }

        return [
            'debug' => false,
            'cache' => false,
            'msg'   => 'remove success',
        ];
    }

    public function reduction()
    {
        $this->__actionLog(__METHOD__, 'databack backup reduction');

        $id = Request::post('id');
        $id = Base64::decrypt($id);

        $file = (array) glob(app()->getRuntimePath() . 'backup' . DIRECTORY_SEPARATOR . $id . DIRECTORY_SEPARATOR . '*');
        foreach ($file as $key => $value) {
            if (is_file($value)) {
                $sql = (new Backup)->read($value);
                echo $sql;die();
            }
        }
    }
}
