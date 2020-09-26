<?php

/**
 *
 * API接口层
 * 数据库备份
 *
 * @package   NICMS
 * @category  app\admin\logic\extend
 * @author    失眠小枕头 [312630173@qq.com]
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
        $date_format = $this->request->param('date_format', 'Y-m-d H:i:s');

        if ($file = glob(runtime_path('backup') . '*')) {
            rsort($file);

            foreach ($file as $key => $value) {
                $file[$key] = [
                    'id'   => Base64::encrypt(basename($value), date('Ymd')),
                    'name' => basename($value),
                    'date' => date($date_format, filectime($value)),
                    'size' => number_format(filesize($value) / 1024 / 1024, 2) . 'MB',
                ];
            }
        } else {
            $file = [];
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
     * 还原
     * @access public
     * @return array
     */
    public function restores(): array
    {
        $this->actionLog(__METHOD__, 'databack backup restores');

        $msg = 'error';
        $id = $this->request->param('id');
        if ($id && $id = Base64::decrypt($id, date('Ymd'))) {
            $path = runtime_path('backup') . $id;
            if (is_file($path)) {
                (new DataManage)->restores($id);
                $msg = 'success';
            }
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
            $path = runtime_path('backup') . $id;
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
