<?php

/**
 *
 * API接口层
 * 数据库备份
 *
 * @package   NICMS
 * @category  app\admin\logic\extend
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace app\admin\logic\extend;

use app\common\controller\BaseLogic;
use app\common\library\DataManage;
use app\common\library\Base64;

class Databack extends BaseLogic
{
    protected $authKey = 'admin_auth_key';

    /**
     * 查询
     * @access public
     * @return array
     */
    public function query(): array
    {
        $path = app()->getRootPath() . 'runtime' . DIRECTORY_SEPARATOR . 'backup' . DIRECTORY_SEPARATOR;

        $file = (array) glob($path . '*');
        rsort($file);

        $date_format = $this->request->param('date_format', 'Y-m-d H:i:s');

        foreach ($file as $key => $value) {
            if (basename($value) == 'sys_auto') {
                unset($file[$key]);
            } else {
                $file[$key] = [
                    'id'   => Base64::encrypt(basename($value), date('Ymd')),
                    'name' => basename($value),
                    'date' => date($date_format, filectime($value)),
                    'size' => number_format(filesize($value) / 1024 / 1024, 2) . 'MB',
                ];
            }
        }

        return [
            'debug' => false,
            'cache' => false,
            'msg'   => 'success',
            'data'  => [
                'list'  => $file,
                'total' => count($file)
            ]
        ];
    }

    /**
     * 备份数据库
     * @access public
     * @return array
     */
    public function backup(): array
    {
        $this->actionLog(__METHOD__, 'databack backup');

        try {
            (new DataManage)->backup();
            $msg = 'success';
        } catch (\Exception $e) {
            $msg = 'error';
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
     * @return array
     */
    public function remove(): array
    {
        $this->actionLog(__METHOD__, 'databack backup remove');

        $msg = 'error';
        $id = $this->request->param('id');
        if ($id && $id = Base64::decrypt($id, date('Ymd'))) {
            $path = app()->getRootPath() . 'runtime' . DIRECTORY_SEPARATOR . 'backup' . DIRECTORY_SEPARATOR . $id;
            if (is_file($path)) {
                unlink($path);
                $msg = 'success';
            }
        }

        return [
            'debug' => false,
            'cache' => false,
            'msg'   => $msg,
        ];
    }
}
