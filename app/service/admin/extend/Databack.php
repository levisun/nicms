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

use app\library\DataManage;
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

        $path = app()->getRuntimePath() . 'backup' . DIRECTORY_SEPARATOR;

        $file = (array) glob($path . '*');
        rsort($file);

        $date_format = $this->request->param('date_format', 'Y-m-d H:i:s');

        foreach ($file as $key => $value) {
            if (basename($value) == 'sys_auto') {
                unset($file[$key]);
            } else {
                $file[$key] = [
                    'id'   => Base64::encrypt($value, date('Ymd')),
                    'name' => basename($value),
                    'date' => date($date_format, filectime($value)),
                    'size' => number_format(filesize($value) / 1024 / 1024, 2) . 'MB',
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
            (new DataManage)->backup();
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
        if ($id && $id = Base64::decrypt($id, date('Ymd'))) {
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
}
