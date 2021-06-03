<?php

/**
 *
 * API接口层
 * 系统信息
 *
 * @package   NICMS
 * @category  app\admin\logic\settings
 * @author    失眠小枕头 [312630173@qq.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace app\admin\logic\settings;

use app\common\controller\BaseLogic;
use app\common\library\tools\File;
use app\common\model\Visit as ModelVisit;

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
                    $this->lang->get('dashboard.sys version')   => 'NICMS ' . $this->config->get('app.version') .
                        '[TP' . $this->app->version() . ']',
                    $this->lang->get('dashboard.sys os')        => PHP_OS,
                    $this->lang->get('dashboard.sys sapi')      => php_sapi_name(),
                    $this->lang->get('dashboard.sys debug')     => $this->config->get('app.debug') ? 'Yes' : 'No',
                    $this->lang->get('dashboard.sys env')       => 'PHP' . PHP_VERSION,
                    $this->lang->get('dashboard.sys db')        => 'Mysql ' . $db_version,
                    $this->lang->get('dashboard.sys GD')        => $gd,
                    $this->lang->get('dashboard.sys timezone')  => $this->config->get('app.default_timezone'),
                    $this->lang->get('dashboard.sys timeout')   => ini_get('max_execution_time'),
                    $this->lang->get('dashboard.sys lang')      => $this->config->get('lang.default_lang'),
                    $this->lang->get('dashboard.sys copyright') => '失眠小枕头 [312630173@qq.com]',
                    $this->lang->get('dashboard.sys upgrade')   => '',
                ],
            ];

            $this->cache->set(__METHOD__, $result);
        }

        $result['total'] = $this->total();

        return [
            'debug' => false,
            'cache' => true,
            'msg'   => 'success',
            'data'  => $result
        ];
    }

    private function total()
    {
        $ip = ModelVisit::where('ip', '<>', '')
            ->where('date', '=', strtotime(date('Y-m-d')))
            ->cache(1440)
            ->count();

        $pv = ModelVisit::fieldRaw('sum(count) as count')
            ->where('name', 'LIKE', 'http%')
            ->where('date', '=', strtotime(date('Y-m-d')))
            ->cache(1440)
            ->find();
        $pv = $pv ? $pv->toArray() : ['count' => 0];
        $pv = (int) $pv['count'];

        $uv = ModelVisit::fieldRaw('sum(count) as count')
            ->where('name', '=', '')
            ->where('date', '=', strtotime(date('Y-m-d')))
            ->cache(1440)
            ->find();
        $uv = $uv ? $uv->toArray() : ['count' => 0];
        $uv = (int) $uv['count'];

        return [
            'session' => format_hits(count((array) glob(runtime_path('session') . '*'))),
            'cache'   => 0,
            'access'  => [
                'ip' => format_hits($ip),
                'pv' => format_hits($pv),
                'uv' => format_hits($uv),
            ]
        ];
    }
}
