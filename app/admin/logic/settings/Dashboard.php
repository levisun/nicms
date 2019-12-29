<?php

/**
 *
 * API接口层
 * 系统信息
 *
 * @package   NICMS
 * @category  app\admin\logic\settings
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace app\admin\logic\settings;

use app\common\controller\BaseLogic;

class Dashboard extends BaseLogic
{
    protected $authKey = 'admin_auth_key';

    public function query()
    {
        if (!$this->cache->has(__METHOD__) || !$result = $this->cache->get(__METHOD__)) {
            $result = \think\facade\Db::query('SELECT version()');
            $db_version = $result[0]['version()'];

            $gd_info = gd_info();
            $gd  = strtr($gd_info['GD Version'], ['bundled (' => '', ' compatible)' => '']) . '(';
            $gd .= $gd_info['GIF Read Support'] ? 'GIF' : '';
            $gd .= $gd_info['JPEG Support'] ? ' JPEG' : '';
            $gd .= $gd_info['PNG Support'] ? ' PNG' : '';
            $gd .= $gd_info['WebP Support'] ? ' WebP' : '';
            $gd .= ')';

            $result = [
                'sysinfo' => [
                    $this->lang->get('sys version')   => 'NICMS ' . $this->config->get('app.version') .
                        '[TP' . $this->app->version() . ']',
                    $this->lang->get('sys os')        => PHP_OS,
                    $this->lang->get('sys sapi')      => php_sapi_name(),
                    $this->lang->get('sys debug')     => $this->config->get('app.debug') ? 'Yes' : 'No',
                    $this->lang->get('sys env')       => 'PHP' . PHP_VERSION,
                    $this->lang->get('sys db')        => 'Mysql ' . $db_version,
                    $this->lang->get('sys GD')        => $gd,
                    $this->lang->get('sys timezone')  => $this->config->get('app.default_timezone'),
                    $this->lang->get('sys api')       => $this->config->get('app.api_host'),
                    $this->lang->get('sys cdn')       => $this->config->get('app.cdn_host'),
                    $this->lang->get('sys lang')      => $this->config->get('lang.default_lang'),
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
