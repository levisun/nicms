<?php

/**
 *
 * API接口层
 * 错误日志
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
use app\common\library\Base64;

class Elog extends BaseLogic
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

        $salt = date('Ymd');

        $result = [];
        $length = [];
        if ($files = glob(runtime_path('log') . '*')) {
            foreach ($files as $value) {
                $result[] = [
                    'id'   => Base64::encrypt(basename($value), $salt),
                    'name' => pathinfo($value, PATHINFO_FILENAME),
                    'date' => date($date_format, filectime($value)),
                    'size' => number_format(filesize($value) / 1024, 2) . 'KB',
                ];
                $length[] = filectime($value);
            }
        }

        array_multisort($length, SORT_DESC, $result);

        return [
            'debug' => false,
            'cache' => true,
            'msg'   => 'success',
            'data'  => [
                'list'  => $result,
                'total' => count($result)
            ]
        ];
    }

    /**
     * 查看
     * @access public
     * @return array
     */
    public function find(): array
    {
        $this->actionLog('see error log');

        $id = $this->request->param('id');
        if ($id && $id = Base64::decrypt($id, date('Ymd'))) {
            $file = runtime_path('log') . $id;
            if (is_file($file)) {
                $data = file_get_contents($file);
                $data = str_replace(['<', '>'], ['[', ']'], $data);
                $data = str_replace($this->app->getRootPath(), 'ROOT_PATH', $data);
                $data = nl2br($data);
                $data = preg_replace_callback('/mysql\:host=[0-9A-Za-z_.=;]+;/si', function () {
                    return 'ROOT ';
                }, $data);
            } else {
                $data = 'null';
            }

            $msg = 'success';
            $data = [
                'title'   => pathinfo($id, PATHINFO_FILENAME),
                'content' => $data
            ];
        } else {
            $msg = 'success';
            $data = [
                'title'   => 'error',
                'content' => 'error',
            ];
        }

        return [
            'debug' => false,
            'cache' => false,
            'msg'   => $msg,
            'data'  => $data,
        ];
    }
}
