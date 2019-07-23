<?php

/**
 *
 * API接口层
 * 系统信息
 *
 * @package   NICMS
 * @category  app\service\admin\settings
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace app\service\admin\settings;

use app\service\BaseService;

class Info extends BaseService
{
    protected $authKey = 'admin_auth_key';

    public function query()
    {
        if ($result = $this->authenticate(__METHOD__)) {
            return $result;
        }
        if (!$this->cache->has(__METHOD__) || !$result = $this->cache->get(__METHOD__)) {
            $result = \think\facade\Db::query('SELECT version()');
            $db_version = $result[0]['version()'];

            $result = [
                'sysinfo' => [
                    $this->lang->get('sys version')   => 'nicms' . env('app.version'),
                    $this->lang->get('sys os')        => PHP_OS,
                    $this->lang->get('sys env')       => 'PHP' . PHP_VERSION . ' ' . php_sapi_name(),
                    $this->lang->get('sys db')        => 'Mysql' . $db_version,
                    'GD'                       => '',
                    $this->lang->get('sys timezone')  => $this->config->get('app.default_timezone'),
                    $this->lang->get('sys copyright') => '失眠小枕头 [levisun.mail@gmail.com]',
                    $this->lang->get('sys upgrade')   => '',
                ],
            ];

            $this->cache->set(__METHOD__, $result);
        }


        return [
            'debug' => false,
            'cache' => true,
            'msg'   => 'info data',
            'data'  => $result
        ];
    }
}
