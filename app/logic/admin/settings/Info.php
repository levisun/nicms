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
use app\logic\admin\Base;

class Info extends Base
{

    public function query()
    {
        if ($result = $this->authenticate(__METHOD__)) {
            return $result;
        }

        $result = \think\facade\Db::query('SELECT version()');
        $db_version = $result[0]['version()'];

        $result = [
            'sysinfo' => [
                Lang::get('sys version')   => 'nicms' . env('app.version'),
                Lang::get('sys os')        => PHP_OS,
                Lang::get('sys env')       => 'PHP' . PHP_VERSION . ' ' . php_sapi_name(),
                Lang::get('sys db')        => 'Mysql' . $db_version,
                'GD'                       => '',
                Lang::get('sys timezone')  => Config::get('app.default_timezone'),
                Lang::get('sys copyright') => '失眠小枕头 [levisun.mail@gmail.com]',
                Lang::get('sys upgrade')   => '',
            ],
        ];

        return [
            'debug' => false,
            'cache' => true,
            'msg'   => 'info data',
            'data'  => $result
        ];
    }
}
