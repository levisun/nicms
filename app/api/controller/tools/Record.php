<?php

/**
 *
 * 控制层
 * 访问记录API
 *
 * @package   NICMS
 * @category  app\api\controller\tools
 * @author    失眠小枕头 [312630173@qq.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace app\api\controller\tools;

use think\Response;
use app\common\controller\BaseApi;
use app\common\library\tools\Ipv4;
use app\common\model\Visit as ModelVisit;

class Record extends BaseApi
{

    public function index()
    {
        if (!$this->validate->referer()) {
            return miss(404, false);
        }

        $user_agent = strtolower($this->request->server('HTTP_USER_AGENT'));
        $ip = (new Ipv4)->get($this->request->ip());
        $has = ModelVisit::where('ip', '=', $ip['ip'])
            ->where('user_agent', '=', md5($user_agent))
            ->where('date', '=', strtotime(date('Y-m-d')))
            ->value('ip');
        if ($has) {
            ModelVisit::where('ip', '=', $ip['ip'])
                ->where('user_agent', '=', md5($user_agent))
                ->where('date', '=', strtotime(date('Y-m-d')))
                ->inc('count', 1)
                ->limit(1)
                ->update();
        } else {
            ModelVisit::create([
                'ip'         => $ip['ip'],
                'ip_attr'    => isset($ip['country']) ? $ip['country'] .  $ip['region'] . $ip['city'] .  $ip['area'] : '',
                'user_agent' => md5($user_agent),
                'date'       => strtotime(date('Y-m-d'))
            ]);
        }

        if ($referer = $this->request->param('url')) {
            $has = ModelVisit::where('name', '=', $referer)
                ->where('date', '=', strtotime(date('Y-m-d')))
                ->value('name');
            if ($has) {
                ModelVisit::where('name', '=', $referer)
                    ->where('date', '=', strtotime(date('Y-m-d')))
                    ->inc('count', 1)
                    ->limit(1)
                    ->update();
            } else {
                ModelVisit::create([
                    'name' => $referer,
                    'date' => strtotime(date('Y-m-d'))
                ]);
            }
        }


        $timestamp = $this->request->time() + 3600 * 6;
        return Response::create()->allowCache(true)
            ->cacheControl('max-age=30,must-revalidate')
            ->expires(gmdate('D, d M Y H:i:s', $timestamp + 30) . ' GMT')
            ->lastModified(gmdate('D, d M Y H:i:s', $timestamp + 30) . ' GMT')
            ->contentType('application/javascript');
    }
}
