<?php
/**
 *
 * API接口层
 * 系统信息
 *
 * @package   NICMS
 * @category  app\logic\admin\settings
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */
declare (strict_types = 1);

namespace app\logic\admin\settings;

use think\facade\Config;
use think\facade\Lang;
use think\facade\Request;
use app\logic\admin\Base;

class Info extends Base
{

    public function query()
    {
        $result = $this->__authenticate('settings', 'info', 'query');
        if ($result !== true) {
            return $result;
        }

        $result = [
            'sysinfo' => [
                [
                    'name'  => Lang::get('sys version'),
                    'value' => 'nicms' . env('app.version'),
                ],
                // 操作系统
                [
                    'name'  => Lang::get('sys os'),
                    'value' => PHP_OS,
                ],
                // 运行环境
                [
                    'name'  => Lang::get('sys env'),
                    'value' => 'PHP' . PHP_VERSION . '' . apache_get_version(),
                    // 'value' => Request::server('SERVER_SOFTWARE'),
                ],
                // 数据库类型与版本
                [
                    'name'  => Lang::get('sys db'),
                    'value' => 'Mysql',
                ],
                [
                    'name'  => 'GD',
                    // 'value' => $gd,
                ],
                [
                    'name'  => Lang::get('sys timezone'),
                    'value' => Config::get('app.default_timezone'),
                ],
                [
                    'name'  => Lang::get('sys copy'),
                    'value' => '失眠小枕头 [levisun.mail@gmail.com]',
                ],
                [
                    'name'  => Lang::get('sys upgrade'),
                    'value' => '',
                ]
            ],
        ];

        return ['msg'=>'123', 'data'=>$result];
    }
}
