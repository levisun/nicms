<?php

/**
 *
 * API接口层
 * 数据库备份
 *
 * @package   NICMS
 * @category  app\service\admin\extend
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace app\service\admin\extend;

use app\library\DataMaintenance;
use app\library\Base64;
use app\service\BaseService;

class Databack extends BaseService
{
    protected $authKey = 'admin_auth_key';

    /**
     * 查询
     * @access public
     * @param
     * @return array
     */
    public function query(): array
    {
        if ($result = $this->authenticate(__METHOD__)) {
            return $result;
        }

        $path = app('config')->get('filesystem.disks.local.root') .
            DIRECTORY_SEPARATOR . 'backup' . DIRECTORY_SEPARATOR;

        $file = (array) glob($path . '*');
        rsort($file);

        $date_format = $this->request->param('date_format', 'Y-m-d H:i:s');

        foreach ($file as $key => $value) {
            if (basename($value) == 'sys_auto') {
                unset($file[$key]);
            } else {
                $date = filectime($value);
                $date = date($date_format, $date);

                $value = basename($value);

                $child = (array) glob($path . $value . DIRECTORY_SEPARATOR . '*');
                $size = 0;
                foreach ($child as $v) {
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
        if ($result = $this->authenticate(__METHOD__, 'databack backup')) {
            return $result;
        }

        try {
            (new DataMaintenance)->backup();
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
        if ($result = $this->authenticate(__METHOD__, 'databack backup remove')) {
            return $result;
        }

        $id = $this->request->param('id');
        if ($id && $id = Base64::decrypt($id)) {
            $path = app('config')->get('filesystem.disks.local.root') .
                DIRECTORY_SEPARATOR . 'backup' . DIRECTORY_SEPARATOR . $id . DIRECTORY_SEPARATOR;
            $file = (array) glob($path . '*');
            foreach ($file as $value) {
                if (is_file($value)) {
                    unlink($value);
                }
            }
            @rmdir($path);
            $msg = 'remove success';
        } else {
            $msg = 'remove error';
        }

        return [
            'debug' => false,
            'cache' => false,
            'msg'   => $msg,
        ];
    }

    /**
     * 还原
     * @access public
     * @param
     * @return array
     */
    public function reduction()
    {
        if ($result = $this->authenticate(__METHOD__, 'databack backup reduction')) {
            return $result;
        }

        // $id = $this->request->param('id');
        // $id = Base64::decrypt($id);

        // (new DataMaintenance)->reduction($id);
    }
}
