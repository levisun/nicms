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
use app\common\model\Article as ModelArticle;
use app\common\model\IpInfo as ModelIpInfo;
use app\common\model\Searchengine as ModelSearchengine;
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
                    $this->lang->get('sys version')   => 'NICMS ' . $this->config->get('app.version') .
                        '[TP' . $this->app->version() . ']',
                    $this->lang->get('sys os')        => PHP_OS,
                    $this->lang->get('sys sapi')      => php_sapi_name(),
                    $this->lang->get('sys debug')     => $this->config->get('app.debug') ? 'Yes' : 'No',
                    $this->lang->get('sys env')       => 'PHP' . PHP_VERSION,
                    $this->lang->get('sys db')        => 'Mysql ' . $db_version,
                    $this->lang->get('sys GD')        => $gd,
                    $this->lang->get('sys timezone')  => $this->config->get('app.default_timezone'),
                    $this->lang->get('sys timeout')   => ini_get('max_execution_time'),
                    $this->lang->get('sys api')       => $this->config->get('app.api_host'),
                    $this->lang->get('sys cdn')       => $this->config->get('app.cdn_host'),
                    $this->lang->get('sys lang')      => $this->config->get('lang.default_lang'),
                    $this->lang->get('sys copyright') => '失眠小枕头 [levisun.mail@gmail.com]',
                    $this->lang->get('sys upgrade')   => '',
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
        $ip = (new ModelIpInfo)->count();
        $session_path = app()->getRootPath() . 'runtime' . DIRECTORY_SEPARATOR . 'session' . DIRECTORY_SEPARATOR;
        $browse = (new ModelVisit)
            ->field('max(count) as count')->where([
                ['date', '=', strtotime(date('Y-m-d'))]
            ])
            ->value('count', 0);
        $searchengine = (new ModelSearchengine)
            ->field('max(count) as count')->where([
                ['date', '=', strtotime(date('Y-m-d'))]
            ])
            ->value('count', 0);
        return [
            'ip'      => number_format($ip),
            'session' => number_format(count((array) glob($session_path . '*'))),
            'access'  => [
                'browse'       => number_format($browse),
                'searchengine' => number_format($searchengine),
            ]
        ];


        // IP统计
        $ip_total = (new ModelIpInfo)->count();

        // 会话统计
        $path = app()->getRootPath() . 'runtime' . DIRECTORY_SEPARATOR . 'session' . DIRECTORY_SEPARATOR;
        $session_count = count((array) glob($path . '*'));

        // 访问统计
        $access_total = (new ModelVisit)
            ->field('max(count) as count')
            ->where([
                ['date', '=', strtotime(date('Y-m-d'))]
            ])
            ->find();

        // 文章统计
        $article_total = (new ModelArticle)
            ->where([
                ['lang', '=', $this->lang->getLangSet()],
                ['update_time', '>=', strtotime(date('Y-m-d'))],
            ])
            ->count();
        $article_pass_total = (new ModelArticle)
            ->where([
                ['lang', '=', $this->lang->getLangSet()],
                ['is_pass', '=', '1'],
                ['update_time', '>=', strtotime(date('Y-m-d'))],
            ])
            ->count();

        return [
            'ip_total' => $ip_total,
            'session_count' => number_format($session_count),
            'access_total'  => $access_total['count'] ? number_format($access_total['count']) : 0,
            'article_count' => [
                'total' => $article_total,
                'pass' => $article_pass_total,
            ]
        ];
    }
}
