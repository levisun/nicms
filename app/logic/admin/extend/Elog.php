<?php
/**
 *
 * API接口层
 * 错误日志
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

use think\facade\Request;
use app\library\Base64;
use app\logic\admin\Base;

class Elog extends Base
{

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

        $file = (array)glob(app()->getRuntimePath() . 'log' . DIRECTORY_SEPARATOR . '*');
        rsort($file);

        $date_format = Request::param('date_format', 'Y-m-d H:i:s');

        foreach ($file as $key => $value) {
            $date = date($date_format, filectime($value));

            $size = filesize($value);
            $size = number_format($size / 1024, 2) . 'KB';

            $file[$key] = [
                'id'   => Base64::encrypt(basename($value)),
                'name' => pathinfo($value, PATHINFO_FILENAME),
                'date' => $date,
                'size' => $size,
            ];
        }

        return [
            'debug' => false,
            'cache' => false,
            'msg'   => 'error log data',
            'data'  => [
                'list'  => $file,
                'total' => count($file)
            ]
        ];
    }

    /**
     * 查看
     * @access public
     * @param
     * @return array
     */
    public function find(): array
    {
        if ($result = $this->authenticate(__METHOD__, 'see error log')) {
            return $result;
        }

        if ($id = Request::param('id')) {
            $id = Base64::decrypt($id);

            $file = app()->getRuntimePath() . 'log' . DIRECTORY_SEPARATOR . $id;
            if (is_file($file)) {
                $data = file_get_contents($file);
                $data = str_replace(app()->getRootPath(), 'ROOT_PATH', $data);
                $data = preg_replace_callback('/mysql\:host=[0-9A-Za-z_.=;]+;/si', function($matches){
                    return 'ROOT ';
                }, $data);
            } else {
                $data = 'null';
            }

            $msg = 'error log data';
            $data = [
                'title'   => pathinfo($id, PATHINFO_FILENAME),
                'content' => $data
            ];
        } else {
            $msg = 'log data error';
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
